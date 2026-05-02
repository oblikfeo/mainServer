/**
 * Telegram-бот «Надежда»: webhook, меню (ТЗ п.4), приём рассылок (ТЗ п.3), поддержка (п.4), админ (п.5).
 * См. README.md — переменные окружения.
 */
import express from 'express';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import { Markup, Telegraf } from 'telegraf';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

const token = process.env.TELEGRAM_BOT_TOKEN;
const siteUrl = (process.env.TELEGRAM_LINK_SITE_URL ?? '').replace(/\/$/, '');
const internalToken = process.env.TELEGRAM_LINK_INTERNAL_API_TOKEN;
const incomingSecret = process.env.TELEGRAM_BOT_INCOMING_SECRET ?? '';
const supportGroupIdRaw = process.env.TELEGRAM_SUPPORT_GROUP_ID ?? '';
const supportGroupId = supportGroupIdRaw !== '' ? Number(supportGroupIdRaw) : null;
const webhookBase = (process.env.TELEGRAM_WEBHOOK_BASE_URL ?? '').replace(/\/$/, '');
const port = Number(process.env.PORT ?? 3850);
const LINK_TOKEN_LEN = 48;

const adminIds = new Set(
  (process.env.TELEGRAM_ADMIN_TELEGRAM_IDS ?? '')
    .split(',')
    .map((s) => Number(String(s).trim()))
    .filter((n) => Number.isFinite(n) && n > 0)
);

const templatesPath = path.join(__dirname, 'templates.json');
const templates = JSON.parse(fs.readFileSync(templatesPath, 'utf8'));

if (!token || !siteUrl || !internalToken) {
  console.error(
    'Нужны TELEGRAM_BOT_TOKEN, TELEGRAM_LINK_SITE_URL, TELEGRAM_LINK_INTERNAL_API_TOKEN'
  );
  process.exit(1);
}

const apiFetch = (relativePath, init = {}) =>
  fetch(`${siteUrl}/api${relativePath}`, {
    ...init,
    headers: {
      Authorization: `Bearer ${internalToken}`,
      Accept: 'application/json',
      'Content-Type': 'application/json',
      ...init.headers,
    },
  });

function applyVars(text, variables) {
  let out = String(text);
  const vars = variables && typeof variables === 'object' ? variables : {};
  for (const [k, v] of Object.entries(vars)) {
    out = out.split(`{${k}}`).join(v == null ? '' : String(v));
  }
  return out;
}

function mainKeyboard() {
  return Markup.keyboard([
    ['Личный кабинет'],
    ['Мои бонусы', 'Статус сети'],
    ['Поддержка'],
  ])
    .resize()
    .persistent();
}

const inSupportMode = new Set();
const pendingBroadcast = new Set();
/** @type {Map<number, number>} forwarded message id in group -> user private chat id */
const supportReplyMap = new Map();

const bot = new Telegraf(token);

async function tryMarkBlocked(telegramUserId) {
  await apiFetch('/internal/telegram/bot/mark-blocked', {
    method: 'POST',
    body: JSON.stringify({ telegram_user_id: telegramUserId }),
  }).catch(() => {});
}

async function safeSendMessage(chatId, text, extra = {}) {
  try {
    await bot.telegram.sendMessage(chatId, text, extra);
    return true;
  } catch (e) {
    const code = e?.response?.error_code;
    const desc = String(e?.response?.description ?? '');
    if (code === 403 || desc.toLowerCase().includes('blocked')) {
      await tryMarkBlocked(Number(chatId));
    }
    console.error('sendMessage', e?.response ?? e);
    return false;
  }
}

function buildInlineKeyboard(rows, variables) {
  return rows.map((row) =>
    row.map((cell) => {
      const text = applyVars(cell.text, variables);
      const urlKey = cell.url_var;
      const url = urlKey ? String(variables[urlKey] ?? '') : '';
      return { text, url };
    })
  );
}

async function sendTemplate(chatId, templateId, variables) {
  const t = templates[templateId];
  if (!t) {
    throw new Error(`unknown_template:${templateId}`);
  }
  const text = applyVars(t.text, variables);
  /** @type {Record<string, unknown>} */
  const extra = {};
  if (Array.isArray(t.inline_keyboard) && t.inline_keyboard.length > 0) {
    extra.reply_markup = {
      inline_keyboard: buildInlineKeyboard(t.inline_keyboard, variables),
    };
  }
  await safeSendMessage(chatId, text, extra);
}

bot.start(async (ctx) => {
  const payload = (ctx.startPayload ?? '').trim();
  const uid = ctx.from?.id;
  const chatId = ctx.chat?.id;
  if (!uid || !chatId) {
    return;
  }

  if (!payload) {
    await safeSendMessage(
      chatId,
      templates.stub_start_no_token.text,
      mainKeyboard()
    );
    return;
  }

  const claimRes = await apiFetch('/internal/telegram/link/claim', {
    method: 'POST',
    body: JSON.stringify({
      deeplink_token: payload,
      telegram_user_id: uid,
      telegram_chat_id: chatId,
      telegram_username: ctx.from?.username ?? null,
    }),
  });

  let claimData = {};
  try {
    claimData = await claimRes.json();
  } catch {
    claimData = {};
  }

  if (claimRes.ok && claimData.ok === true && claimData.message_for_chat) {
    await safeSendMessage(chatId, claimData.message_for_chat, mainKeyboard());
    return;
  }

  const isInvalidOrExpired =
    claimData.error === 'invalid_or_expired' ||
    claimData.error === 'invalid_or_expired_token';

  if (isInvalidOrExpired && payload.length < LINK_TOKEN_LEN) {
    await apiFetch('/internal/telegram/start/utm', {
      method: 'POST',
      body: JSON.stringify({
        telegram_user_id: uid,
        utm_param: payload,
      }),
    }).catch(() => {});
  }

  const msg =
    typeof claimData.message === 'string'
      ? claimData.message
      : 'Не удалось завершить привязку. Откройте Личный кабинет на сайте и запросите новую ссылку.';
  await safeSendMessage(chatId, msg, mainKeyboard());
});

bot.hears('Личный кабинет', async (ctx) => {
  inSupportMode.delete(ctx.chat.id);
  const r = await apiFetch('/internal/telegram/bot/mirror');
  let url = '';
  try {
    const j = await r.json();
    url = typeof j.url === 'string' ? j.url : '';
  } catch {
    url = '';
  }
  if (!url) {
    await safeReply(ctx, 'Ссылка на кабинет временно недоступна.', mainKeyboard());
    return;
  }
  const text = `Ваша актуальная ссылка для входа в Личный кабинет: ${url}. Ссылка индивидуальна, пожалуйста, не передавайте ее третьим лицам.`;
  await ctx.reply(
    text,
    Markup.inlineKeyboard([[Markup.button.url('Войти в кабинет', url)]])
  );
});

async function safeReply(ctx, text, markup) {
  await safeSendMessage(ctx.chat.id, text, markup);
}

bot.hears('Мои бонусы', async (ctx) => {
  inSupportMode.delete(ctx.chat.id);
  const uid = ctx.from?.id;
  if (!uid) {
    return;
  }
  const r = await apiFetch('/internal/telegram/bot/referral-summary', {
    method: 'POST',
    body: JSON.stringify({ telegram_user_id: uid }),
  });
  let j = {};
  try {
    j = await r.json();
  } catch {
    j = {};
  }
  if (!r.ok || !j.ok) {
    const m =
      typeof j.message === 'string'
        ? j.message
        : 'Не удалось загрузить данные. Привяжите Telegram в Личном кабинете на сайте.';
    await safeReply(ctx, m, mainKeyboard());
    return;
  }
  const lines = Array.isArray(j.lines) ? j.lines : [];
  const cabinetUrl = typeof j.cabinet_url === 'string' ? j.cabinet_url : '';
  const body = `${lines.join('\n')}${cabinetUrl ? `\n\nЛичный кабинет: ${cabinetUrl}` : ''}`;
  await safeReply(ctx, body, mainKeyboard());
});

bot.hears('Статус сети', async (ctx) => {
  inSupportMode.delete(ctx.chat.id);
  const r = await apiFetch('/internal/telegram/bot/network-status');
  let j = {};
  try {
    j = await r.json();
  } catch {
    j = {};
  }
  const ok = j.all_operational === true;
  const tid = ok ? 'network_ok' : 'network_down';
  const text = templates[tid]?.text ?? '';
  await safeReply(ctx, text, mainKeyboard());
});

bot.hears('Поддержка', async (ctx) => {
  inSupportMode.add(ctx.chat.id);
  const text = templates.support_mode_enter.text;
  await safeReply(ctx, text, mainKeyboard());
});

bot.on('message', async (ctx, next) => {
  if (!ctx.chat || ctx.chat.type !== 'private') {
    return next();
  }

  if (ctx.message?.text && /^(Личный кабинет|Мои бонусы|Статус сети|Поддержка)$/.test(ctx.message.text.trim())) {
    return next();
  }

  const chatId = ctx.chat.id;
  if (!inSupportMode.has(chatId)) {
    return next();
  }

  if (!supportGroupId) {
    await safeReply(
      ctx,
      'Поддержка недоступна (не настроен TELEGRAM_SUPPORT_GROUP_ID).',
      mainKeyboard()
    );
    return;
  }

  try {
    const forwarded = await ctx.forwardMessage(supportGroupId);
    const mid = forwarded.message_id;
    supportReplyMap.set(mid, chatId);
  } catch (e) {
    console.error('forwardMessage', e);
    await safeReply(ctx, 'Не удалось передать сообщение в поддержку.', mainKeyboard());
  }
});

bot.on('message', async (ctx, next) => {
  if (ctx.chat?.type !== 'supergroup' && ctx.chat?.type !== 'group') {
    return next();
  }
  if (!supportGroupId || ctx.chat.id !== supportGroupId) {
    return next();
  }
  const fromId = ctx.from?.id;
  if (!fromId || !adminIds.has(fromId)) {
    return next();
  }
  const reply = ctx.message?.reply_to_message;
  if (!reply) {
    return next();
  }
  const bridgeId = reply.message_id;
  const userChat = supportReplyMap.get(bridgeId);
  if (!userChat) {
    return next();
  }
  try {
    await ctx.copyMessage(userChat);
  } catch (e) {
    console.error('copyMessage to user', e);
    const code = e?.response?.error_code;
    if (code === 403 || String(e?.response?.description ?? '').toLowerCase().includes('blocked')) {
      await tryMarkBlocked(userChat);
    }
  }
});

bot.command('ndstats', async (ctx) => {
  const fromId = ctx.from?.id;
  if (!fromId || !adminIds.has(fromId)) {
    return;
  }
  const r = await apiFetch('/internal/telegram/admin/stats', {
    method: 'POST',
    body: JSON.stringify({ admin_telegram_user_id: fromId }),
  });
  let j = {};
  try {
    j = await r.json();
  } catch {
    j = {};
  }
  if (!r.ok) {
    await ctx.reply('Нет доступа или ошибка API.');
    return;
  }
  await ctx.reply(
    `Всего привязок Telegram: ${j.total_telegram_linked ?? '—'}\n` +
      `С активной подпиской: ${j.active_subscriptions_linked ?? '—'}\n` +
      `Заблокировали бота (помечено): ${j.bot_blocked ?? '—'}`
  );
});

bot.command('ndbroadcast', async (ctx) => {
  const fromId = ctx.from?.id;
  if (!fromId || !adminIds.has(fromId)) {
    return;
  }
  pendingBroadcast.add(fromId);
  await ctx.reply('Пришлите следующим сообщением текст для рассылки (без форматирования). /ndcancel — отмена.');
});

bot.command('ndcancel', async (ctx) => {
  const fromId = ctx.from?.id;
  if (fromId) {
    pendingBroadcast.delete(fromId);
  }
  await ctx.reply('Отменено.');
});

bot.on('message', async (ctx, next) => {
  if (ctx.chat?.type !== 'private') {
    return next();
  }
  const fromId = ctx.from?.id;
  if (!fromId || !pendingBroadcast.has(fromId)) {
    return next();
  }
  const text = ctx.message?.text;
  const photos = ctx.message?.photo;
  if (text?.startsWith('/')) {
    return next();
  }
  if (!text && (!photos || photos.length === 0)) {
    return next();
  }
  pendingBroadcast.delete(fromId);

  const r = await apiFetch('/internal/telegram/admin/linked-chat-ids', {
    method: 'POST',
    body: JSON.stringify({ admin_telegram_user_id: fromId }),
  });
  let j = {};
  try {
    j = await r.json();
  } catch {
    j = {};
  }
  if (!r.ok || !j.ok || !Array.isArray(j.telegram_chat_ids)) {
    await ctx.reply('Не удалось получить список чатов.');
    return;
  }
  let sent = 0;
  const caption = ctx.message?.caption ?? '';
  if (photos && photos.length > 0) {
    const fileId = photos[photos.length - 1].file_id;
    for (const id of j.telegram_chat_ids) {
      try {
        /** @type {Record<string, unknown>} */
        const photoPayload = {
          caption: caption || undefined,
        };
        if (ctx.message?.caption_entities?.length) {
          photoPayload.caption_entities = ctx.message.caption_entities;
        }
        await bot.telegram.sendPhoto(Number(id), fileId, photoPayload);
        sent += 1;
      } catch (e) {
        const code = e?.response?.error_code;
        if (code === 403 || String(e?.response?.description ?? '').toLowerCase().includes('blocked')) {
          await tryMarkBlocked(Number(id));
        }
        console.error('broadcast photo', e);
      }
    }
  } else if (text) {
    for (const id of j.telegram_chat_ids) {
      try {
        /** @type {Record<string, unknown>} */
        const payload = {
          reply_markup: mainKeyboard().reply_markup,
        };
        if (ctx.message?.entities?.length) {
          payload.entities = ctx.message.entities;
        }
        await bot.telegram.sendMessage(Number(id), text, payload);
        sent += 1;
      } catch (e) {
        const code = e?.response?.error_code;
        if (code === 403 || String(e?.response?.description ?? '').toLowerCase().includes('blocked')) {
          await tryMarkBlocked(Number(id));
        }
        console.error('broadcast text', e);
      }
    }
  }
  await ctx.reply(`Отправлено: ${sent} из ${j.telegram_chat_ids.length}.`);
});

const app = express();
app.use(express.json({ limit: '2mb' }));

app.post('/internal/notify', async (req, res) => {
  const auth = (req.get('authorization') ?? '').replace(/^Bearer\s+/i, '');
  if (!incomingSecret || auth !== incomingSecret) {
    return res.status(403).json({ ok: false });
  }
  const { telegram_chat_id, template_id, variables } = req.body ?? {};
  if (!telegram_chat_id || !template_id) {
    return res.status(400).json({ ok: false, error: 'bad_request' });
  }
  try {
    await sendTemplate(Number(telegram_chat_id), String(template_id), variables ?? {});
    return res.json({ ok: true });
  } catch (e) {
    console.error('notify', e);
    return res.status(400).json({ ok: false, error: String(e.message) });
  }
});

app.use(bot.webhookCallback('/telegram/webhook'));

app.listen(port, () => {
  console.error(`nadezhda-telegram-bot listening :${port}`);
  if (webhookBase) {
    const url = `${webhookBase}/telegram/webhook`;
    bot.telegram
      .setWebhook(url)
      .then(() => console.error('webhook set', url))
      .catch((e) => console.error('setWebhook', e));
  } else {
    console.error('TELEGRAM_WEBHOOK_BASE_URL пуст — setWebhook пропущен');
  }
});
