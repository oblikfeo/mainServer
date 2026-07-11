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
    ['Оплатить', 'Мои устройства'],
    ['Мои бонусы'],
    ['Поддержка'],
  ])
    .resize()
    .persistent();
}

/** Клавиатура до входа / регистрации — без «мёртвых» пунктов главного меню. */
function guestKeyboard() {
  return Markup.keyboard([
    ['У меня уже есть аккаунт', 'Новый пользователь'],
    ['↩️ Начать сначала', 'Поддержка'],
  ])
    .resize()
    .persistent();
}

const MENU_BUTTONS_RE = /^(Личный кабинет|Оплатить|Мои устройства|Мои бонусы|Поддержка)$/;
const GUEST_BUTTONS_RE = /^(У меня уже есть аккаунт|Новый пользователь|↩️ Начать сначала|Поддержка)$/;

const inSupportMode = new Set();
const pendingBroadcast = new Set();
/** @type {Map<number, number>} forwarded message id in group -> user private chat id */
const supportReplyMap = new Map();
/** @type {Map<number, string>} chat id -> referral/utm from /start before registration */
const pendingReferral = new Map();

const bot = new Telegraf(token);

bot.catch((err, ctx) => {
  console.error('telegraf handler error', err?.message ?? err, ctx?.updateType);
});

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

function truncate(value, max) {
  const str = String(value ?? '');
  return str.length > max ? `${str.slice(0, max - 1)}…` : str;
}

async function fetchPaymentCatalog() {
  const r = await apiFetch('/internal/telegram/bot/payment/catalog');
  let j = {};
  try {
    j = await r.json();
  } catch {
    j = {};
  }
  return { ok: r.ok, data: j };
}

async function fetchRenewSubscriptions(uid) {
  const r = await apiFetch('/internal/telegram/bot/payment/subscriptions', {
    method: 'POST',
    body: JSON.stringify({ telegram_user_id: uid }),
  });
  let j = {};
  try {
    j = await r.json();
  } catch {
    j = {};
  }
  return { ok: r.ok, data: j };
}

async function createPayment(uid, username, payload) {
  const r = await apiFetch('/internal/telegram/bot/payment/create', {
    method: 'POST',
    body: JSON.stringify({
      telegram_user_id: uid,
      telegram_username: username ?? null,
      ...payload,
    }),
  });
  let j = {};
  try {
    j = await r.json();
  } catch {
    j = {};
  }
  return { ok: r.ok, data: j };
}

async function checkPaymentStatus(uid, orderId) {
  const r = await apiFetch('/internal/telegram/bot/payment/status', {
    method: 'POST',
    body: JSON.stringify({ telegram_user_id: uid, order_id: orderId }),
  });
  let j = {};
  try {
    j = await r.json();
  } catch {
    j = {};
  }
  return { ok: r.ok, data: j };
}

function findPlan(catalog, planKey) {
  const plans = Array.isArray(catalog?.plans) ? catalog.plans : [];
  return plans.find((p) => p.plan === planKey) ?? null;
}

function buildPaymentMenuView() {
  return {
    text: '💳 Оплата подписки\n\nВыберите действие:',
    keyboard: [
      [{ text: '✨ Новая подписка', callback_data: 'py:1' }],
      [{ text: '🔄 Продлить подписку', callback_data: 'py:2' }],
    ],
  };
}

function buildNewPlanView(catalog) {
  const plans = Array.isArray(catalog?.plans) ? catalog.plans : [];
  const keyboard = plans.map((p) => [
    { text: String(p.label ?? p.plan), callback_data: `py:n:${p.plan}` },
  ]);
  keyboard.push([{ text: '← Назад', callback_data: 'py:0' }]);
  return {
    text: '✨ Новая подписка\n\nВыберите тариф:',
    keyboard,
  };
}

function buildPeriodView(catalog, planKey, renew, subscriptionId) {
  const plan = findPlan(catalog, planKey);
  const periods = renew
    ? Array.isArray(plan?.renew_periods)
      ? plan.renew_periods
      : []
    : Array.isArray(plan?.periods)
      ? plan.periods
      : [];
  const keyboard = periods.map((row) => [
    {
      text: `${row.period} · ${row.amount_rub} ₽`,
      callback_data: renew
        ? `py:rt:${subscriptionId}:${planKey}:${row.index}`
        : `py:nt:${planKey}:${row.index}`,
    },
  ]);
  keyboard.push([
    {
      text: '← Назад',
      callback_data: renew ? `py:2` : 'py:1',
    },
  ]);
  return {
    text: renew
      ? `🔄 Продление · ${plan?.label ?? planKey}\n\nВыберите срок:`
      : `✨ ${plan?.label ?? planKey}\n\nВыберите срок:`,
    keyboard,
  };
}

function buildRenewSubsView(items) {
  if (!Array.isArray(items) || items.length === 0) {
    return {
      text: 'У вас нет подписок для продления.\n\nОформите новую подписку.',
      keyboard: [[{ text: '← Назад', callback_data: 'py:0' }]],
    };
  }
  const keyboard = items.map((it) => {
    const created = String(it.created ?? '').trim() || String(it.public_code ?? '');
    const state = it.active ? 'активна' : 'неактивна';
    return [
      {
        text: `${created} · ${state}`,
        callback_data: `py:rs:${it.id}:${it.plan}`,
      },
    ];
  });
  keyboard.push([{ text: '← Назад', callback_data: 'py:0' }]);
  return {
    text: '🔄 Продление\n\nВыберите подписку:',
    keyboard,
  };
}

function buildPaymentMethodView(purpose, plan, periodIndex, subscriptionId) {
  const base =
    purpose === 'r'
      ? `py:x:r:${plan}:${periodIndex}:${subscriptionId}`
      : `py:x:n:${plan}:${periodIndex}`;
  return {
    text: 'Выберите способ оплаты:',
    keyboard: [
      [{ text: '📱 СБП', callback_data: `${base}:sbp` }],
      [{ text: '💳 Картой', callback_data: `${base}:card` }],
      [
        {
          text: '← Назад',
          callback_data:
            purpose === 'r'
              ? `py:rs:${subscriptionId}:${plan}`
              : `py:n:${plan}`,
        },
      ],
    ],
  };
}

function buildPayLinkView(data) {
  const url = String(data.pay_url ?? '');
  const orderId = String(data.order_id ?? '');
  const methodLabel = data.payment_method === 'card' ? 'Картой' : 'СБП';
  const keyboard = [
    [{ text: `💳 Оплатить (${methodLabel})`, url }],
    [{ text: '🔄 Проверить оплату', callback_data: `py:s:${orderId}` }],
    [{ text: '← В меню оплаты', callback_data: 'py:0' }],
  ];
  return {
    text:
      `${data.description ?? 'Оплата'}\n` +
      `Сумма: ${data.amount_rub ?? '—'} ₽\n\n` +
      'Нажмите «Оплатить», завершите платёж и вернитесь в бот.\n' +
      'После оплаты придёт уведомление (или нажмите «Проверить оплату»).',
    keyboard,
  };
}

async function editPayView(ctx, view) {
  try {
    await ctx.editMessageText(view.text, {
      reply_markup: { inline_keyboard: view.keyboard },
    });
  } catch {
    // message may be stale
  }
}

async function fetchDevices(uid) {
  const r = await apiFetch('/internal/telegram/bot/devices', {
    method: 'POST',
    body: JSON.stringify({ telegram_user_id: uid }),
  });
  let j = {};
  try {
    j = await r.json();
  } catch {
    j = {};
  }
  return { ok: r.ok, data: j };
}

const scopeOf = (it) => (it?.scope === 't' ? 't' : 's');

function findItem(j, scope, id) {
  const items = Array.isArray(j.items) ? j.items : [];
  return items.find((it) => scopeOf(it) === scope && Number(it.id) === id) ?? null;
}

/** Экран выбора подписки: короткий список кнопок, без «мусора». */
function buildSubscriptionsView(j) {
  const items = Array.isArray(j.items) ? j.items : [];
  if (items.length === 0) {
    return {
      text:
        'У вас пока нет подписок с устройствами.\n\n' +
        'Устройство появляется здесь после первого подключения через приложение Happ.',
      keyboard: [[{ text: '🔄 Обновить', callback_data: 'dv:l' }]],
    };
  }

  const keyboard = items.map((it) => {
    const scope = scopeOf(it);
    const id = Number(it.id);
    const created = String(it.created ?? '').trim();
    const label = created !== '' ? created : truncate(String(it.title ?? 'Подписка'), 24);
    const state = it.active ? 'активна' : 'неактивна';
    const bound = Number(it.bound ?? 0);
    const slots = Number(it.slots ?? 0);
    return [
      {
        text: `${label} · ${state} · ${bound}/${slots}`,
        callback_data: `dv:o:${scope}:${id}`,
      },
    ];
  });
  keyboard.push([{ text: '🔄 Обновить', callback_data: 'dv:l' }]);

  return {
    text: '📱 Мои устройства\n\nВыберите подписку, чтобы посмотреть и отвязать устройства.',
    keyboard,
  };
}

/** Экран одной подписки: устройства и кнопки отвязки. */
function buildDeviceDetailView(it) {
  const scope = scopeOf(it);
  const id = Number(it.id);
  const title = String(it.title ?? 'Подписка');
  const slots = Number(it.slots ?? 0);
  const devices = Array.isArray(it.devices) ? it.devices : [];

  if (devices.length === 0) {
    return {
      text: `📱 ${title}\n\nНа этой подписке нет привязанных устройств.`,
      keyboard: [[{ text: '← Назад к подпискам', callback_data: 'dv:l' }]],
    };
  }

  const keyboard = devices.map((d) => [
    {
      text: `❌ ${truncate(d.label || 'Устройство', 28)}`,
      callback_data: `dv:d:${scope}:${id}:${d.hash_prefix}`,
    },
  ]);
  keyboard.push([{ text: '🧹 Отвязать все', callback_data: `dv:c:${scope}:${id}` }]);
  keyboard.push([{ text: '← Назад к подпискам', callback_data: 'dv:l' }]);

  return {
    text: `📱 ${title}\nУстройств: ${devices.length} из ${slots}`,
    keyboard,
  };
}

async function editView(ctx, view) {
  try {
    await ctx.editMessageText(view.text, {
      reply_markup: { inline_keyboard: view.keyboard },
    });
  } catch {
    // Игнорируем "message is not modified" и устаревшие сообщения.
  }
}

async function renderSubscriptions(ctx, uid) {
  const { ok, data } = await fetchDevices(uid);
  if (!ok || !data.ok) {
    return;
  }
  await editView(ctx, buildSubscriptionsView(data));
}

async function renderDetail(ctx, uid, scope, id) {
  const { ok, data } = await fetchDevices(uid);
  if (!ok || !data.ok) {
    return;
  }
  const it = findItem(data, scope, id);
  await editView(ctx, it ? buildDeviceDetailView(it) : buildSubscriptionsView(data));
}

async function fetchBotStatus(uid) {
  const r = await apiFetch('/internal/telegram/bot/status', {
    method: 'POST',
    body: JSON.stringify({ telegram_user_id: uid }),
  });
  let data = {};
  try {
    data = await r.json();
  } catch {
    data = {};
  }
  return { ok: r.ok, data };
}

async function isUserLinked(uid) {
  const { ok, data } = await fetchBotStatus(uid);
  return ok && data.linked === true;
}

async function replyKeyboardForUser(uid) {
  return (await isUserLinked(uid)) ? mainKeyboard() : guestKeyboard();
}

async function ensureLinked(ctx) {
  const uid = ctx.from?.id;
  if (!uid) {
    return false;
  }
  if (await isUserLinked(uid)) {
    return true;
  }
  await safeReply(
    ctx,
    'Сначала войдите или создайте аккаунт — кнопки выбора ниже.',
    guestKeyboard()
  );
  return false;
}

async function logStartUtm(uid, param) {
  await apiFetch('/internal/telegram/start/utm', {
    method: 'POST',
    body: JSON.stringify({
      telegram_user_id: uid,
      utm_param: param,
    }),
  }).catch(() => {});
}

function onboardingKeyboard() {
  return Markup.inlineKeyboard([
    [Markup.button.callback('У меня уже есть аккаунт', 'onb:existing')],
    [Markup.button.callback('Новый пользователь', 'onb:new')],
  ]);
}

async function showOnboarding(chatId, referralParam) {
  if (referralParam) {
    pendingReferral.set(chatId, referralParam);
  }
  const text =
    templates.onboarding_welcome?.text ??
    'Добро пожаловать в сервис «Надежда»! Выберите, как продолжить:';
  await safeSendMessage(chatId, text, onboardingKeyboard());
  await safeSendMessage(
    chatId,
    'Кнопки внизу — чтобы вернуться к выбору в любой момент.',
    guestKeyboard()
  );
}

async function showExistingAccountHelp(chatId) {
  const text =
    templates.onboarding_existing?.text ??
    'Откройте nadezhda.space → Личный кабинет → Профиль → «Привязать Telegram» и перейдите по ссылке в этом боте.';
  await safeSendMessage(
    chatId,
    text,
    Markup.inlineKeyboard([
      [Markup.button.url('Открыть сайт', `${siteUrl}/login`)],
      [Markup.button.callback('← Назад к выбору', 'onb:back')],
    ])
  );
  await safeSendMessage(
    chatId,
    'После привязки откройте ссылку из профиля — меню обновится. Или нажмите «↩️ Начать сначала».',
    guestKeyboard()
  );
}

async function registerNewUser(ctx) {
  const uid = ctx.from?.id;
  const chatId = ctx.chat?.id;
  if (!uid || !chatId) {
    return;
  }

  if (await isUserLinked(uid)) {
    await welcomeReturningUser(chatId);
    return;
  }

  const storedReferral = pendingReferral.get(chatId);
  const startReferral = (ctx.startPayload ?? '').trim();
  let referralParam = storedReferral ?? null;
  if (!referralParam && startReferral && startReferral.length < LINK_TOKEN_LEN) {
    referralParam = startReferral;
  }
  pendingReferral.delete(chatId);

  try {
    const r = await apiFetch('/internal/telegram/register', {
      method: 'POST',
      body: JSON.stringify({
        telegram_user_id: uid,
        telegram_chat_id: chatId,
        telegram_username: ctx.from?.username ?? null,
        telegram_first_name: ctx.from?.first_name ?? null,
        referral_param: referralParam,
        offer_accepted: true,
      }),
    });

    let j = {};
    try {
      j = await r.json();
    } catch {
      j = {};
    }

    if (r.ok && j.ok && j.message_for_chat) {
      await safeSendMessage(chatId, j.message_for_chat, mainKeyboard());
      return;
    }

    const msg =
      typeof j.message === 'string'
        ? j.message
        : 'Не удалось создать аккаунт. Попробуйте позже или напишите в поддержку.';
    await safeSendMessage(chatId, msg, guestKeyboard());
  } catch (e) {
    console.error('registerNewUser', e);
    await safeSendMessage(chatId, 'Сервис временно недоступен. Попробуйте через минуту.', guestKeyboard());
  }
}

async function welcomeReturningUser(chatId) {
  const text =
    templates.welcome_returning?.text ??
    'С возвращением! Пользуйтесь меню ниже — личный кабинет, устройства, бонусы и поддержка.';
  await safeSendMessage(chatId, text, mainKeyboard());
}

bot.start(async (ctx) => {
  const payload = (ctx.startPayload ?? '').trim();
  const uid = ctx.from?.id;
  const chatId = ctx.chat?.id;
  if (!uid || !chatId) {
    return;
  }

  try {
    if (payload.length >= LINK_TOKEN_LEN) {
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

      console.warn('[tg-link/claim]', claimData.error ?? claimRes.status, {
        deeplinkLen: payload.length,
      });

      const msg =
        typeof claimData.message === 'string'
          ? claimData.message
          : 'Не удалось завершить привязку. Откройте Личный кабинет на сайте и запросите новую ссылку.';
      if (await isUserLinked(uid)) {
        await safeSendMessage(chatId, msg, mainKeyboard());
      } else {
        await safeSendMessage(chatId, msg, guestKeyboard());
        await showOnboarding(chatId, null);
      }
      return;
    }

    const { ok: statusOk, data: status } = await fetchBotStatus(uid);
    if (statusOk && status.linked) {
      if (payload.length > 0 && payload.length < LINK_TOKEN_LEN) {
        await logStartUtm(uid, payload);
      }
      await welcomeReturningUser(chatId);
      return;
    }

    if (payload.length > 0 && payload.length < LINK_TOKEN_LEN) {
      await logStartUtm(uid, payload);
      await showOnboarding(chatId, payload);
      return;
    }

    await showOnboarding(chatId, null);
  } catch (e) {
    console.error('bot.start handler', e);
    await safeSendMessage(
      chatId,
      'Сервис временно недоступен. Попробуйте через минуту.',
      guestKeyboard()
    );
  }
});

bot.action('onb:back', async (ctx) => {
  await ctx.answerCbQuery();
  const chatId = ctx.chat?.id;
  if (!chatId) {
    return;
  }
  await showOnboarding(chatId, pendingReferral.get(chatId) ?? null);
});

bot.hears('↩️ Начать сначала', async (ctx) => {
  inSupportMode.delete(ctx.chat?.id);
  const uid = ctx.from?.id;
  const chatId = ctx.chat?.id;
  if (!uid || !chatId) {
    return;
  }
  if (await isUserLinked(uid)) {
    await welcomeReturningUser(chatId);
    return;
  }
  await showOnboarding(chatId, pendingReferral.get(chatId) ?? null);
});

bot.hears('У меня уже есть аккаунт', async (ctx) => {
  inSupportMode.delete(ctx.chat?.id);
  const uid = ctx.from?.id;
  const chatId = ctx.chat?.id;
  if (!uid || !chatId) {
    return;
  }
  if (await isUserLinked(uid)) {
    await safeReply(ctx, 'Telegram уже привязан. Пользуйтесь меню ниже.', mainKeyboard());
    return;
  }
  await showExistingAccountHelp(chatId);
});

bot.hears('Новый пользователь', async (ctx) => {
  inSupportMode.delete(ctx.chat?.id);
  await registerNewUser(ctx);
});

bot.action('onb:existing', async (ctx) => {
  await ctx.answerCbQuery();
  const chatId = ctx.chat?.id;
  if (!chatId) {
    return;
  }
  await showExistingAccountHelp(chatId);
});

bot.action('onb:new', async (ctx) => {
  await ctx.answerCbQuery('Создаём аккаунт…');
  await registerNewUser(ctx);
});

bot.hears('Личный кабинет', async (ctx) => {
  inSupportMode.delete(ctx.chat.id);
  const uid = ctx.from?.id;
  if (!uid) {
    return;
  }
  if (!(await ensureLinked(ctx))) {
    return;
  }
  const r = await apiFetch('/internal/telegram/bot/mirror', {
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
        : 'Ссылка на кабинет временно недоступна. Привяжите Telegram в профиле на сайте.';
    await safeReply(ctx, m, mainKeyboard());
    return;
  }
  const url = typeof j.url === 'string' ? j.url : '';
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
  if (!(await ensureLinked(ctx))) {
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
  const body = lines.length > 0 ? lines.join('\n') : 'Не удалось загрузить данные.';
  await safeReply(ctx, body, mainKeyboard());
});

bot.hears('Мои устройства', async (ctx) => {
  inSupportMode.delete(ctx.chat.id);
  const uid = ctx.from?.id;
  if (!uid) {
    return;
  }
  if (!(await ensureLinked(ctx))) {
    return;
  }
  const { ok, data } = await fetchDevices(uid);
  if (!ok || !data.ok) {
    const m =
      typeof data.message === 'string'
        ? data.message
        : 'Не удалось загрузить устройства. Привяжите Telegram в Личном кабинете на сайте.';
    await safeReply(ctx, m, mainKeyboard());
    return;
  }
  const view = buildSubscriptionsView(data);
  await safeSendMessage(ctx.chat.id, view.text, {
    reply_markup: { inline_keyboard: view.keyboard },
  });
});

bot.action('dv:l', async (ctx) => {
  await ctx.answerCbQuery();
  await renderSubscriptions(ctx, ctx.from?.id);
});

bot.action(/^dv:o:([st]):(\d+)$/, async (ctx) => {
  await ctx.answerCbQuery();
  await renderDetail(ctx, ctx.from?.id, ctx.match[1], Number(ctx.match[2]));
});

bot.action(/^dv:d:([st]):(\d+):([0-9a-f]{8,64})$/, async (ctx) => {
  const uid = ctx.from?.id;
  const scope = ctx.match[1];
  const id = Number(ctx.match[2]);
  const hashPrefix = ctx.match[3];
  const r = await apiFetch('/internal/telegram/bot/devices/detach', {
    method: 'POST',
    body: JSON.stringify({ telegram_user_id: uid, scope, id, hash_prefix: hashPrefix }),
  });
  let j = {};
  try {
    j = await r.json();
  } catch {
    j = {};
  }
  await ctx.answerCbQuery(j.ok ? 'Устройство отвязано' : j.message || 'Не удалось отвязать');
  await renderDetail(ctx, uid, scope, id);
});

bot.action(/^dv:c:([st]):(\d+)$/, async (ctx) => {
  const scope = ctx.match[1];
  const id = Number(ctx.match[2]);
  await ctx.answerCbQuery();
  try {
    await ctx.editMessageReplyMarkup({
      inline_keyboard: [
        [{ text: '⚠️ Да, отвязать все', callback_data: `dv:C:${scope}:${id}` }],
        [{ text: '← Отмена', callback_data: `dv:o:${scope}:${id}` }],
      ],
    });
  } catch {
    // сообщение могло устареть
  }
});

bot.action(/^dv:C:([st]):(\d+)$/, async (ctx) => {
  const uid = ctx.from?.id;
  const scope = ctx.match[1];
  const id = Number(ctx.match[2]);
  const r = await apiFetch('/internal/telegram/bot/devices/clear', {
    method: 'POST',
    body: JSON.stringify({ telegram_user_id: uid, scope, id }),
  });
  let j = {};
  try {
    j = await r.json();
  } catch {
    j = {};
  }
  await ctx.answerCbQuery(j.ok ? 'Все привязки сброшены' : j.message || 'Ошибка');
  await renderDetail(ctx, uid, scope, id);
});

bot.hears('Оплатить', async (ctx) => {
  inSupportMode.delete(ctx.chat.id);
  const uid = ctx.from?.id;
  if (!uid) {
    return;
  }
  if (!(await ensureLinked(ctx))) {
    return;
  }
  const view = buildPaymentMenuView();
  await safeSendMessage(ctx.chat.id, view.text, {
    reply_markup: { inline_keyboard: view.keyboard },
  });
});

bot.action('py:0', async (ctx) => {
  await ctx.answerCbQuery();
  await editPayView(ctx, buildPaymentMenuView());
});

bot.action('py:1', async (ctx) => {
  await ctx.answerCbQuery();
  const { ok, data } = await fetchPaymentCatalog();
  if (!ok || !data.ok) {
    await ctx.answerCbQuery('Не удалось загрузить тарифы', { show_alert: true });
    return;
  }
  await editPayView(ctx, buildNewPlanView(data));
});

bot.action('py:2', async (ctx) => {
  const uid = ctx.from?.id;
  await ctx.answerCbQuery();
  const { ok, data } = await fetchRenewSubscriptions(uid);
  if (!ok || !data.ok) {
    const m =
      typeof data.message === 'string'
        ? data.message
        : 'Привяжите Telegram в Личном кабинете на сайте.';
    await editPayView(ctx, { text: m, keyboard: [[{ text: '← Назад', callback_data: 'py:0' }]] });
    return;
  }
  await editPayView(ctx, buildRenewSubsView(data.items));
});

bot.action(/^py:n:(solo|family)$/, async (ctx) => {
  await ctx.answerCbQuery();
  const plan = ctx.match[1];
  const { ok, data } = await fetchPaymentCatalog();
  if (!ok || !data.ok) {
    return;
  }
  await editPayView(ctx, buildPeriodView(data, plan, false, null));
});

bot.action(/^py:nt:(solo|family):(\d+)$/, async (ctx) => {
  await ctx.answerCbQuery();
  const plan = ctx.match[1];
  const periodIndex = Number(ctx.match[2]);
  await editPayView(ctx, buildPaymentMethodView('n', plan, periodIndex, null));
});

bot.action(/^py:rs:(\d+):(solo|family)$/, async (ctx) => {
  await ctx.answerCbQuery();
  const subId = Number(ctx.match[1]);
  const plan = ctx.match[2];
  const { ok, data } = await fetchPaymentCatalog();
  if (!ok || !data.ok) {
    return;
  }
  await editPayView(ctx, buildPeriodView(data, plan, true, subId));
});

bot.action(/^py:rt:(\d+):(solo|family):(\d+)$/, async (ctx) => {
  await ctx.answerCbQuery();
  const subId = Number(ctx.match[1]);
  const plan = ctx.match[2];
  const periodIndex = Number(ctx.match[3]);
  await editPayView(ctx, buildPaymentMethodView('r', plan, periodIndex, subId));
});

bot.action(/^py:x:n:(solo|family):(\d+):(sbp|card)$/, async (ctx) => {
  const uid = ctx.from?.id;
  const plan = ctx.match[1];
  const periodIndex = Number(ctx.match[2]);
  const method = ctx.match[3];
  await ctx.answerCbQuery('Создаём платёж…');
  const { ok, data } = await createPayment(uid, ctx.from?.username, {
    purpose: 'new',
    plan,
    period_index: periodIndex,
    payment_method: method,
  });
  if (!ok || !data.ok) {
    const m = typeof data.message === 'string' ? data.message : 'Не удалось создать платёж.';
    await editPayView(ctx, { text: m, keyboard: [[{ text: '← Назад', callback_data: 'py:0' }]] });
    return;
  }
  await editPayView(ctx, buildPayLinkView(data));
});

bot.action(/^py:x:r:(solo|family):(\d+):(\d+):(sbp|card)$/, async (ctx) => {
  const uid = ctx.from?.id;
  const plan = ctx.match[1];
  const periodIndex = Number(ctx.match[2]);
  const subId = Number(ctx.match[3]);
  const method = ctx.match[4];
  await ctx.answerCbQuery('Создаём платёж…');
  const { ok, data } = await createPayment(uid, ctx.from?.username, {
    purpose: 'renew',
    plan,
    period_index: periodIndex,
    subscription_id: subId,
    payment_method: method,
  });
  if (!ok || !data.ok) {
    const m = typeof data.message === 'string' ? data.message : 'Не удалось создать платёж.';
    await editPayView(ctx, { text: m, keyboard: [[{ text: '← Назад', callback_data: 'py:0' }]] });
    return;
  }
  await editPayView(ctx, buildPayLinkView(data));
});

bot.action(/^py:s:(ord_[A-Za-z0-9_-]+)$/, async (ctx) => {
  const uid = ctx.from?.id;
  const orderId = ctx.match[1];
  const { ok, data } = await checkPaymentStatus(uid, orderId);
  if (!ok || !data.ok) {
    await ctx.answerCbQuery('Заказ не найден', { show_alert: true });
    return;
  }
  if (data.paid) {
    await ctx.answerCbQuery('✅ Оплата получена! Подписка обновлена.');
    return;
  }
  const status = String(data.status ?? 'pending');
  const labels = {
    pending: 'Ожидаем оплату…',
    created: 'Ожидаем оплату…',
    declined: 'Оплата отменена',
    paid: 'Оплачено',
  };
  await ctx.answerCbQuery(labels[status] ?? 'Статус: ' + status, { show_alert: status === 'declined' });
});

bot.hears('Поддержка', async (ctx) => {
  inSupportMode.add(ctx.chat.id);
  const uid = ctx.from?.id;
  const kb = uid ? await replyKeyboardForUser(uid) : guestKeyboard();
  const text = templates.support_mode_enter.text;
  await safeReply(ctx, text, kb);
});

bot.on('message', async (ctx, next) => {
  if (!ctx.chat || ctx.chat.type !== 'private') {
    return next();
  }

  const text = ctx.message?.text?.trim() ?? '';
  if (text && (MENU_BUTTONS_RE.test(text) || GUEST_BUTTONS_RE.test(text))) {
    return next();
  }

  const chatId = ctx.chat.id;
  if (!inSupportMode.has(chatId)) {
    return next();
  }

  if (!supportGroupId) {
    const uid = ctx.from?.id;
    const kb = uid ? await replyKeyboardForUser(uid) : guestKeyboard();
    await safeReply(
      ctx,
      'Поддержка недоступна (не настроен TELEGRAM_SUPPORT_GROUP_ID).',
      kb
    );
    return;
  }

  try {
    const forwarded = await ctx.forwardMessage(supportGroupId);
    const mid = forwarded.message_id;
    supportReplyMap.set(mid, chatId);
  } catch (e) {
    console.error('forwardMessage', e);
    const uid = ctx.from?.id;
    const kb = uid ? await replyKeyboardForUser(uid) : guestKeyboard();
    await safeReply(ctx, 'Не удалось передать сообщение в поддержку.', kb);
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

const useWebhook = webhookBase.length > 0;

if (useWebhook) {
  app.use(bot.webhookCallback('/telegram/webhook'));
}

app.listen(port, async () => {
  console.error(`nadezhda-telegram-bot HTTP :${port} (notify + ${useWebhook ? 'webhook' : 'no webhook'})`);
  if (useWebhook) {
    const url = `${webhookBase}/telegram/webhook`;
    try {
      await bot.telegram.setWebhook(url);
      console.error('webhook set', url);
    } catch (e) {
      console.error('setWebhook failed', e);
    }
  } else {
    console.error('TELEGRAM_WEBHOOK_BASE_URL пуст — long polling getUpdates');
    void (async () => {
      for (let attempt = 1; attempt <= 10; attempt++) {
        const dropPending = attempt > 1;
        try {
          await bot.telegram.deleteWebhook({ drop_pending_updates: dropPending });
        } catch (e) {
          console.error('deleteWebhook', e);
        }
        try {
          console.error(`long polling: попытка ${attempt}`);
          await bot.launch();
          return;
        } catch (e) {
          const code = e?.response?.error_code;
          console.error('bot.launch', e?.message ?? e);
          if (code === 409 && attempt < 10) {
            await new Promise((r) => setTimeout(r, 1500 * attempt));
            continue;
          }
          process.exit(1);
        }
      }
    })();
  }
});

process.once('SIGINT', () => bot.stop('SIGINT'));
process.once('SIGTERM', () => bot.stop('SIGTERM'));
