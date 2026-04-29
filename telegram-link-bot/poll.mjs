/**
 * Поллер Telegram: /start <token> → POST на сайт → sendMessage с кодом.
 * Окружение см. README.md (NLtest, systemd).
 */
const token = process.env.TELEGRAM_BOT_TOKEN;
const siteUrl = (process.env.TELEGRAM_LINK_SITE_URL ?? '').replace(/\/$/, '');
const internalToken = process.env.TELEGRAM_LINK_INTERNAL_API_TOKEN;

if (!token || !siteUrl || !internalToken) {
  console.error(
    'Нужны переменные окружения: TELEGRAM_BOT_TOKEN, TELEGRAM_LINK_SITE_URL, TELEGRAM_LINK_INTERNAL_API_TOKEN'
  );
  process.exit(1);
}

const tg = (method, body) =>
  fetch(`https://api.telegram.org/bot${token}/${method}`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: body ? JSON.stringify(body) : undefined,
  });

async function sendMessage(chatId, text) {
  const r = await tg('sendMessage', {
    chat_id: chatId,
    text,
  });
  const j = await r.json();
  if (!j.ok) {
    console.error('sendMessage error', j);
  }
}

const claimUrl = `${siteUrl}/api/internal/telegram/link/claim`;

async function handleStart(chatId, telegramUserId, username, payload) {
  if (!payload || payload.length < 32) {
    await sendMessage(
      chatId,
      'Откройте профиль на сайте Надежда в разделе «Профиль», нажмите «Получить ссылку на бота» и перейдите по ссылке.'
    );
    return;
  }

  const res = await fetch(claimUrl, {
    method: 'POST',
    headers: {
      Authorization: `Bearer ${internalToken}`,
      'Content-Type': 'application/json',
      Accept: 'application/json',
    },
    body: JSON.stringify({
      deeplink_token: payload,
      telegram_user_id: telegramUserId,
      telegram_chat_id: chatId,
      telegram_username: username ?? null,
    }),
  });

  let data = {};
  try {
    data = await res.json();
  } catch {
    data = {};
  }

  if (res.ok && data.ok === true && data.code_plain && data.message_for_chat) {
    await sendMessage(chatId, data.message_for_chat);
    return;
  }

  const msg =
    typeof data.message === 'string'
      ? data.message
      : 'Не удалось выдать код. Запросите новую ссылку в профиле на сайте.';
  await sendMessage(chatId, msg);
}

let offset = 0;

async function loop() {
  const r = await fetch(
    `https://api.telegram.org/bot${token}/getUpdates?timeout=50&offset=${offset}`,
    { method: 'GET' }
  );
  const j = await r.json();
  if (!j.ok) {
    console.error('getUpdates', j);
    await new Promise((s) => setTimeout(s, 5000));
    return;
  }
  for (const u of j.result ?? []) {
    offset = u.update_id + 1;
    const msg = u.message;
    if (!msg || !msg.text) continue;
    const text = msg.text.trim();
    const m = /^\/start(?:\s+(\S+))?$/.exec(text);
    if (!m) continue;
    const payload = m[1] ?? '';
    const from = msg.from;
    if (!from) continue;
    await handleStart(msg.chat.id, from.id, from.username ?? null, payload);
  }
}

await tg('deleteWebhook', { drop_pending_updates: false }).catch(() => {});
console.error('telegram-link-bot: polling, site=', siteUrl);

for (;;) {
  try {
    await loop();
  } catch (e) {
    console.error(e);
    await new Promise((s) => setTimeout(s, 3000));
  }
}
