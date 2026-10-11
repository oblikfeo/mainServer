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
    ['🎁 Пробный доступ', 'Мои бонусы'],
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

const MENU_BUTTONS_RE = /^(Личный кабинет|Оплатить|Мои устройства|🎁 Пробный доступ|Мои бонусы|Поддержка)$/;
const GUEST_BUTTONS_RE = /^(У меня уже есть аккаунт|Новый пользователь|↩️ Начать сначала|Поддержка)$/;

const inSupportMode = new Set();
/** @type {Map<number, Array<{role: 'user'|'assistant', content: string}>>} chatId -> история диалога с ИИ-ассистентом */
const aiChats = new Map();
const AI_HISTORY_MAX = 40;
const pendingBroadcast = new Set();

/** Выход из любого режима поддержки (ИИ-ассистент и живой оператор) при переходе в другой раздел меню. */
function leaveSupportModes(chatId) {
  if (chatId == null) {
    return;
  }
  inSupportMode.delete(chatId);
  aiChats.delete(chatId);
}
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

/** Каталог цен + состояние подписки аккаунта одним вызовом. */
async function loadPayContext(uid) {
  const [cat, acc] = await Promise.all([fetchPaymentCatalog(), fetchRenewSubscriptions(uid)]);
  if (!cat.ok || !cat.data.ok) {
    return { ok: false, message: 'Не удалось загрузить цены. Попробуйте позже.' };
  }
  if (!acc.ok || !acc.data.ok) {
    return {
      ok: false,
      message:
        typeof acc.data.message === 'string'
          ? acc.data.message
          : 'Привяжите Telegram в Личном кабинете на сайте.',
    };
  }
  return { ok: true, catalog: cat.data, account: acc.data.account ?? {} };
}

function payStatusLine(account) {
  const state = String(account?.state ?? 'none');
  const expires = String(account?.expires ?? '').trim();
  if (state === 'active') {
    return expires !== '' ? `✅ Подписка активна до ${expires}` : '✅ Подписка активна';
  }
  if (state === 'trial') {
    return expires !== '' ? `🎁 Пробный период до ${expires}` : '🎁 Пробный период';
  }
  if (state === 'inactive') {
    return '⏸ Подписка не активна';
  }
  return 'Подписка ещё не оформлена';
}

const payBase = (ctxPay) => Number(ctxPay.account?.base_devices ?? ctxPay.catalog?.base_devices ?? 2);
const payHave = (ctxPay) => payBase(ctxPay) + Number(ctxPay.account?.extra_devices ?? 0);
const payMax = (ctxPay) => Number(ctxPay.account?.max_devices ?? ctxPay.catalog?.max_devices ?? 10);
const payLive = (ctxPay) => ['active', 'trial'].includes(String(ctxPay.account?.state ?? ''));
const payFixedDevices = (ctxPay) =>
  Boolean(ctxPay.account?.unlimited_devices) || payMax(ctxPay) <= payBase(ctxPay);

function findTerm(catalog, key) {
  const terms = Array.isArray(catalog?.terms) ? catalog.terms : [];
  return terms.find((t) => t.key === key) ?? null;
}

function payTotal(ctxPay, n, termKey, months) {
  const term = findTerm(ctxPay.catalog, termKey);
  const extra = Math.max(0, n - payBase(ctxPay));
  const perMonth = Number(ctxPay.catalog?.device_month_rub ?? 150);
  return Number(term?.amount_rub ?? 0) + extra * months * perMonth;
}

/** Шаг 1: сколько устройств нужно. */
function buildPayDevicesView(ctxPay) {
  const base = payBase(ctxPay);
  const have = payHave(ctxPay);
  const max = payMax(ctxPay);
  const perMonth = Number(ctxPay.catalog?.device_month_rub ?? 150);
  const keyboard = [];
  let row = [];
  for (let n = base; n <= max; n++) {
    row.push({ text: n === have ? `· ${n} ·` : String(n), callback_data: `py:d:${n}` });
    if (row.length === 4) {
      keyboard.push(row);
      row = [];
    }
  }
  if (row.length > 0) {
    keyboard.push(row);
  }
  return {
    text:
      `💳 Оплата\n\n${payStatusLine(ctxPay.account)}\n` +
      `Устройств сейчас: ${have}\n\n` +
      `В подписку входит ${base} устр. Каждое дополнительное — ${perMonth} ₽ в месяц.\n\n` +
      'Сколько устройств нужно?',
    keyboard,
  };
}

/** Шаг 2: срок подписки. */
function buildPayTermView(ctxPay, n) {
  const terms = Array.isArray(ctxPay.catalog?.terms) ? ctxPay.catalog.terms : [];
  const extra = Math.max(0, n - payBase(ctxPay));
  const keyboard = terms.map((t) => {
    const discount = Number(t.discount_percent ?? 0);
    return [
      {
        text: `${t.label} · ${t.amount_rub} ₽${discount > 0 ? ` (−${discount}%)` : ''}`,
        callback_data: `py:t:${n}:${t.key}`,
      },
    ];
  });
  if (payLive(ctxPay) && extra > 0) {
    keyboard.push([{ text: 'Не продлевать, только устройства', callback_data: `py:t:${n}:none` }]);
  }
  if (!payFixedDevices(ctxPay)) {
    keyboard.push([{ text: '← Назад', callback_data: 'py:0' }]);
  }
  const head = payFixedDevices(ctxPay)
    ? `💳 Оплата\n\n${payStatusLine(ctxPay.account)}`
    : `💳 Оплата · устройств: ${n}`;
  return { text: `${head}\n\nВыберите срок подписки:`, keyboard };
}

/** Шаг 3: на сколько месяцев оплатить доп. устройства. */
function buildPayDeviceMonthsView(ctxPay, n, termKey) {
  const extra = Math.max(0, n - payBase(ctxPay));
  const perMonth = Number(ctxPay.catalog?.device_month_rub ?? 150);
  const maxMonths = Number(ctxPay.catalog?.device_max_months ?? 6);
  const keyboard = [];
  let row = [];
  for (let m = 1; m <= maxMonths; m++) {
    row.push({ text: `${m} мес. · ${extra * m * perMonth} ₽`, callback_data: `py:m:${n}:${termKey}:${m}` });
    if (row.length === 2) {
      keyboard.push(row);
      row = [];
    }
  }
  if (row.length > 0) {
    keyboard.push(row);
  }
  if (termKey !== 'none' && Number(ctxPay.account?.extra_devices ?? 0) >= extra) {
    keyboard.push([{ text: 'Устройства сейчас не оплачивать', callback_data: `py:m:${n}:${termKey}:0` }]);
  }
  keyboard.push([{ text: '← Назад', callback_data: `py:d:${n}` }]);
  return {
    text:
      `📱 Доп. устройств: ${extra} · ${perMonth} ₽ в месяц за каждое\n\n` +
      'На сколько месяцев их оплатить?\n' +
      'Дни доп. устройства идут только пока подписка активна.',
    keyboard,
  };
}

/** Шаг 4: итог и способ оплаты. */
function buildPayMethodView(ctxPay, n, termKey, months) {
  const term = findTerm(ctxPay.catalog, termKey);
  const extra = Math.max(0, n - payBase(ctxPay));
  const perMonth = Number(ctxPay.catalog?.device_month_rub ?? 150);
  const lines = [];
  if (term) {
    lines.push(`Подписка · ${term.label} — ${term.amount_rub} ₽`);
  }
  if (extra > 0 && months > 0) {
    lines.push(`Доп. устройства: ${extra} × ${months} мес. — ${extra * months * perMonth} ₽`);
  }
  const base = `py:x:${n}:${termKey}:${months}`;
  return {
    text: `${lines.join('\n')}\n\nИтого: ${payTotal(ctxPay, n, termKey, months)} ₽\n\nВыберите способ оплаты:`,
    keyboard: [
      [{ text: '📱 СБП', callback_data: `${base}:sbp` }],
      [{ text: '💳 Картой', callback_data: `${base}:card` }],
      [{ text: '← Назад', callback_data: `py:d:${n}` }],
    ],
  };
}

function buildPayHomeView(ctxPay) {
  return payFixedDevices(ctxPay) ? buildPayTermView(ctxPay, 0) : buildPayDevicesView(ctxPay);
}

const payErrorView = (message) => ({
  text: message,
  keyboard: [[{ text: '🔄 Обновить', callback_data: 'py:0' }]],
});

function buildPayLinkView(data) {
  const url = String(data.pay_url ?? '');
  const orderId = String(data.order_id ?? '');
  const methodLabel = data.payment_method === 'card' ? 'Картой' : 'СБП';
  const keyboard = [
    [{ text: `💳 Оплатить (${methodLabel})`, url }],
    [{ text: '🔄 Проверить оплату', callback_data: `py:s:${orderId}` }],
    [{ text: '← К оплате', callback_data: 'py:0' }],
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

/** Экран устройств: у аккаунта одна подписка, поэтому сразу список устройств. */
function buildSubscriptionsView(j) {
  const items = Array.isArray(j.items) ? j.items : [];
  if (items.length === 0) {
    return {
      text:
        'Привязанных устройств пока нет.\n\n' +
        'Устройство появляется здесь после первого подключения через приложение Happ.',
      keyboard: [[{ text: '🔄 Обновить', callback_data: 'dv:l' }]],
    };
  }
  if (items.length === 1) {
    return buildDeviceDetailView(items[0], true);
  }

  const keyboard = items.map((it) => {
    const scope = scopeOf(it);
    const id = Number(it.id);
    const label = scope === 't' ? 'Тестовая подписка' : 'Подписка';
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
    text: '📱 Мои устройства\n\nВыберите, что открыть.',
    keyboard,
  };
}

/** Устройства подписки и кнопки отвязки. */
function buildDeviceDetailView(it, single = false) {
  const scope = scopeOf(it);
  const id = Number(it.id);
  const title = scope === 't' ? 'Тестовая подписка' : 'Мои устройства';
  const slots = Number(it.slots ?? 0);
  const devices = Array.isArray(it.devices) ? it.devices : [];
  const back = single
    ? { text: '🔄 Обновить', callback_data: 'dv:l' }
    : { text: '← Назад', callback_data: 'dv:l' };
  const addRow = scope === 's' ? [[{ text: '➕ Добавить устройство', callback_data: 'py:0' }]] : [];
  const state = it.active ? '' : '\nПодписка не активна.';

  if (devices.length === 0) {
    return {
      text:
        `📱 ${title}\nЗанято 0 из ${slots}${state}\n\n` +
        'Устройство появится здесь после первого подключения через приложение Happ.',
      keyboard: [...addRow, [back]],
    };
  }

  const keyboard = devices.map((d) => [
    {
      text: `❌ ${truncate(d.label || 'Устройство', 28)}`,
      callback_data: `dv:d:${scope}:${id}:${d.hash_prefix}`,
    },
  ]);
  if (devices.length > 1) {
    keyboard.push([{ text: '🧹 Отвязать все', callback_data: `dv:c:${scope}:${id}` }]);
  }
  keyboard.push(...addRow);
  keyboard.push([back]);

  return {
    text: `📱 ${title}\nЗанято ${devices.length} из ${slots}${state}\n\nНажмите на устройство, чтобы отвязать его.`,
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
  const items = Array.isArray(data.items) ? data.items : [];
  await editView(ctx, it ? buildDeviceDetailView(it, items.length === 1) : buildSubscriptionsView(data));
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
bot.command('menu', async (ctx) => {
  const uid = ctx.from?.id;
  if (!uid || !ctx.chat) {
    return;
  }
  await safeSendMessage(ctx.chat.id, 'Меню обновлено.', await replyKeyboardForUser(uid));
});

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
  leaveSupportModes(ctx.chat?.id);
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
  leaveSupportModes(ctx.chat?.id);
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
  leaveSupportModes(ctx.chat?.id);
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
  leaveSupportModes(ctx.chat.id);
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
  leaveSupportModes(ctx.chat.id);
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
  const html = typeof j.html === 'string' ? j.html : '';
  if (html) {
    const sent = await safeSendMessage(ctx.chat.id, html, {
      ...mainKeyboard(),
      parse_mode: 'HTML',
      link_preview_options: { is_disabled: true },
    });
    if (sent) {
      return;
    }
    // HTML не прошёл (например, ошибка разметки) — падаем на плоский текст ниже
  }
  const lines = Array.isArray(j.lines) ? j.lines : [];
  const body = lines.length > 0 ? lines.join('\n') : 'Не удалось загрузить данные.';
  await safeReply(ctx, body, mainKeyboard());
});

bot.hears('Мои устройства', async (ctx) => {
  leaveSupportModes(ctx.chat.id);
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

/** @type {Set<number>} uid с выдачей триала в процессе — защита от двойного нажатия */
const trialInFlight = new Set();

bot.hears('🎁 Пробный доступ', async (ctx) => {
  leaveSupportModes(ctx.chat.id);
  const uid = ctx.from?.id;
  if (!uid) {
    return;
  }
  if (!(await ensureLinked(ctx))) {
    return;
  }
  if (trialInFlight.has(uid)) {
    return;
  }
  trialInFlight.add(uid);
  try {
    const r = await apiFetch('/internal/telegram/bot/trial/issue', {
      method: 'POST',
      body: JSON.stringify({ telegram_user_id: uid }),
    });
    let j = {};
    try {
      j = await r.json();
    } catch {
      j = {};
    }

    const keyUrl = typeof j.subscription_url === 'string' && j.subscription_url !== '' ? j.subscription_url : null;

    if (r.ok && j.ok) {
      const parts = ['🎁 Тестовая подписка активирована!'];
      if (j.expires_at) {
        parts.push(`Действует до: ${j.expires_at}`);
      }
      const details = [];
      if (Number(j.devices) > 0) {
        details.push(`устройств: ${Number(j.devices)}`);
      }
      if (Number(j.quota_gb) > 0) {
        details.push(`трафик: ${Number(j.quota_gb)} ГБ`);
      }
      if (details.length > 0) {
        parts.push(`Лимиты — ${details.join(', ')}.`);
      }
      if (keyUrl) {
        parts.push('');
        parts.push('Ваш тестовый ключ (ссылка подписки):');
        parts.push(keyUrl);
        parts.push('');
        parts.push('Скопируйте ссылку и добавьте её в приложение Happ как подписку — интернет заработает сразу.');
      }
      await safeReply(ctx, parts.join('\n'), mainKeyboard());
      return;
    }

    let msg =
      typeof j.message === 'string'
        ? j.message
        : 'Не удалось выдать тестовую подписку. Попробуйте позже или напишите в поддержку.';
    if (keyUrl) {
      msg += `\n\nВаш ключ (ссылка подписки):\n${keyUrl}`;
    }
    await safeReply(ctx, msg, mainKeyboard());
  } catch (e) {
    console.error('trial issue', e);
    await safeReply(
      ctx,
      'Сервис временно недоступен. Попробуйте через минуту.',
      mainKeyboard()
    );
  } finally {
    trialInFlight.delete(uid);
  }
});

bot.hears('Оплатить', async (ctx) => {
  leaveSupportModes(ctx.chat.id);
  const uid = ctx.from?.id;
  if (!uid) {
    return;
  }
  if (!(await ensureLinked(ctx))) {
    return;
  }
  const ctxPay = await loadPayContext(uid);
  const view = ctxPay.ok ? buildPayHomeView(ctxPay) : payErrorView(ctxPay.message);
  await safeSendMessage(ctx.chat.id, view.text, {
    reply_markup: { inline_keyboard: view.keyboard },
  });
});

/** Загружает контекст оплаты; при ошибке сам показывает её и возвращает null. */
async function payContextOrShowError(ctx) {
  const ctxPay = await loadPayContext(ctx.from?.id);
  if (!ctxPay.ok) {
    await editPayView(ctx, payErrorView(ctxPay.message));
    return null;
  }
  return ctxPay;
}

// py:1, py:2 и py:n… — кнопки старого меню в уже отправленных сообщениях: ведём в новое.
bot.action(/^py:(0|1|2|n:.*|nt:.*|rs:.*|rt:.*|x:[nr]:.*)$/, async (ctx) => {
  await ctx.answerCbQuery();
  const ctxPay = await payContextOrShowError(ctx);
  if (ctxPay) {
    await editPayView(ctx, buildPayHomeView(ctxPay));
  }
});

bot.action(/^py:d:(\d+)$/, async (ctx) => {
  await ctx.answerCbQuery();
  const ctxPay = await payContextOrShowError(ctx);
  if (ctxPay) {
    await editPayView(ctx, buildPayTermView(ctxPay, Number(ctx.match[1])));
  }
});

bot.action(/^py:t:(\d+):([a-z0-9]+)$/, async (ctx) => {
  await ctx.answerCbQuery();
  const ctxPay = await payContextOrShowError(ctx);
  if (!ctxPay) {
    return;
  }
  const n = Number(ctx.match[1]);
  const termKey = ctx.match[2];
  const extra = Math.max(0, n - payBase(ctxPay));
  if (extra > 0 && !payFixedDevices(ctxPay)) {
    await editPayView(ctx, buildPayDeviceMonthsView(ctxPay, n, termKey));
    return;
  }
  await editPayView(ctx, buildPayMethodView(ctxPay, n, termKey, 0));
});

bot.action(/^py:m:(\d+):([a-z0-9]+):(\d+)$/, async (ctx) => {
  await ctx.answerCbQuery();
  const ctxPay = await payContextOrShowError(ctx);
  if (ctxPay) {
    await editPayView(
      ctx,
      buildPayMethodView(ctxPay, Number(ctx.match[1]), ctx.match[2], Number(ctx.match[3])),
    );
  }
});

bot.action(/^py:x:(\d+):([a-z0-9]+):(\d+):(sbp|card)$/, async (ctx) => {
  const uid = ctx.from?.id;
  await ctx.answerCbQuery('Создаём платёж…');
  const { ok, data } = await createPayment(uid, ctx.from?.username, {
    term: ctx.match[2],
    devices: Number(ctx.match[1]),
    device_months: Number(ctx.match[3]),
    payment_method: ctx.match[4],
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

/** Инлайн-кнопка ручного вызова оператора под ответами ИИ-ассистента. */
function callOperatorKeyboard() {
  return {
    inline_keyboard: [[{ text: '👤 Позвать оператора', callback_data: 'ai:human' }]],
  };
}

/** Запрос к ИИ-ассистенту через Laravel. Возвращает { ok, reply, handoff }. */
async function askAiAssistant(history, from) {
  const r = await apiFetch('/internal/telegram/bot/chat', {
    method: 'POST',
    body: JSON.stringify({
      messages: history,
      telegram_user_id: from?.id ?? null,
      telegram_username: from?.username ?? null,
    }),
  });
  let j = {};
  try {
    j = await r.json();
  } catch {
    j = {};
  }
  return { ok: r.ok && j.ok === true, reply: String(j.reply ?? ''), handoff: j.handoff === true };
}

/** Запрос сжатой сути обращения (1-2 предложения) через Laravel. Возвращает строку или ''. */
async function fetchAiSummary(history) {
  if (!Array.isArray(history) || history.length === 0) {
    return '';
  }
  try {
    const r = await apiFetch('/internal/telegram/bot/chat/summary', {
      method: 'POST',
      body: JSON.stringify({ messages: history }),
    });
    let j = {};
    try {
      j = await r.json();
    } catch {
      j = {};
    }
    return r.ok && j.ok === true ? String(j.summary ?? '').trim() : '';
  } catch (e) {
    console.error('fetchAiSummary', e);
    return '';
  }
}

/**
 * Перевод диалога с ИИ на живого оператора: в группу поддержки уходит одна
 * компактная карточка (кто клиент + суть обращения от ИИ, без всей переписки),
 * клиент переключается в обычный режим форварда (inSupportMode).
 */
async function escalateToSupport(ctx, chatId, reason) {
  const history = aiChats.get(chatId) ?? [];
  aiChats.delete(chatId);

  if (!supportGroupId) {
    const uid = ctx.from?.id;
    const kb = uid ? await replyKeyboardForUser(uid) : guestKeyboard();
    await safeSendMessage(
      chatId,
      'Поддержка сейчас недоступна. Напишите нам в @nadezhda_tehsup.',
      kb
    );
    return;
  }

  // Суть обращения. Фолбэк — последнее сообщение клиента, если ИИ-саммари не вышло.
  let gist = await fetchAiSummary(history);
  if (!gist) {
    const lastUser = [...history].reverse().find((m) => m.role === 'user');
    gist = lastUser ? lastUser.content : '(клиент не описал вопрос)';
  }

  const from = ctx.from ?? {};
  const who =
    (from.username ? `@${from.username}` : `${from.first_name ?? ''}`.trim() || 'клиент') +
    ` (id ${from.id ?? '—'})`;
  const card =
    `🆘 Запрос оператора из бота\n` +
    `Клиент: ${who}\n` +
    `————\n` +
    `Суть: ${gist}\n\n` +
    `Ответьте на это сообщение (reply) — текст уйдёт клиенту.`;

  // Постим карточку и подвязываем её к чату клиента: reply оператора в группе
  // долетит клиенту через нижний message-мост (supportReplyMap).
  try {
    const sent = await bot.telegram.sendMessage(supportGroupId, card, {
      link_preview_options: { is_disabled: true },
    });
    supportReplyMap.set(sent.message_id, chatId);
  } catch (e) {
    console.error('escalateToSupport send', e?.response ?? e);
  }

  inSupportMode.add(chatId);
  const uid = ctx.from?.id;
  const kb = uid ? await replyKeyboardForUser(uid) : guestKeyboard();
  await safeSendMessage(
    chatId,
    'Передали ваш диалог живому человеку — оператор скоро ответит здесь же. Можете дописать детали, они тоже уйдут в поддержку.',
    kb
  );
}

bot.hears('Поддержка', async (ctx) => {
  const chatId = ctx.chat.id;
  inSupportMode.delete(chatId);
  aiChats.set(chatId, []);
  const uid = ctx.from?.id;
  const kb = uid ? await replyKeyboardForUser(uid) : guestKeyboard();
  await safeSendMessage(
    chatId,
    'Здравствуйте! Я ассистент сервиса «Надежда VPN» — помогу с тарифами, подключением, оплатой и другими вопросами. Опишите, что случилось. Если понадобится живой человек, я передам ваш диалог оператору.',
    kb
  );
});

bot.action('ai:human', async (ctx) => {
  await ctx.answerCbQuery('Зову оператора…');
  const chatId = ctx.chat?.id;
  if (!chatId) {
    return;
  }
  try {
    await ctx.editMessageReplyMarkup(undefined);
  } catch {
    // кнопка могла устареть
  }
  if (!aiChats.has(chatId) && !inSupportMode.has(chatId)) {
    aiChats.set(chatId, []);
  }
  if (inSupportMode.has(chatId)) {
    return; // уже в живой поддержке
  }
  await escalateToSupport(ctx, chatId, 'manual');
});

// Диалог с ИИ-ассистентом (режим «Поддержка» до перевода на человека).
bot.on('message', async (ctx, next) => {
  if (!ctx.chat || ctx.chat.type !== 'private') {
    return next();
  }

  const text = ctx.message?.text?.trim() ?? '';
  if (text && (MENU_BUTTONS_RE.test(text) || GUEST_BUTTONS_RE.test(text))) {
    return next();
  }

  const chatId = ctx.chat.id;
  if (!aiChats.has(chatId)) {
    return next();
  }

  // ИИ работает только с текстом. Фото/файлы — сразу к живому оператору.
  if (!text) {
    await escalateToSupport(ctx, chatId, 'manual');
    return;
  }

  const history = aiChats.get(chatId);
  history.push({ role: 'user', content: text });
  if (history.length > AI_HISTORY_MAX) {
    history.splice(0, history.length - AI_HISTORY_MAX);
  }

  await ctx.sendChatAction('typing').catch(() => {});
  const { ok, reply, handoff } = await askAiAssistant(history, ctx.from);

  if (!ok || reply === '') {
    // ИИ недоступен — не бросаем клиента, переводим на человека.
    await safeSendMessage(
      chatId,
      'Не удалось получить ответ ассистента. Передаю ваш вопрос живому человеку.'
    );
    await escalateToSupport(ctx, chatId, 'manual');
    return;
  }

  history.push({ role: 'assistant', content: reply });

  if (handoff) {
    await safeSendMessage(chatId, reply);
    await escalateToSupport(ctx, chatId, 'ai');
    return;
  }

  await safeSendMessage(chatId, reply, { reply_markup: callOperatorKeyboard() });
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
