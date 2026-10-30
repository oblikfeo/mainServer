{{-- Виджет чата поддержки (главная). Ассистент отвечает сразу, «Позвать человека» — вопрос уходит оператору в Telegram, ответ приходит сюда же. --}}
<div class="ndsc" id="ndsc"
     data-send="{{ route('support.chat.message') }}"
     data-base="{{ url('/support/chat') }}">
    <button type="button" class="ndsc-launch" id="ndsc-launch" aria-controls="ndsc-panel" aria-expanded="false" aria-label="Открыть чат поддержки">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 5h16v11H9l-5 4V5z" stroke="currentColor" stroke-width="2.4" stroke-linejoin="round"/></svg>
        <span class="ndsc-launch__text">Чат</span>
        <span class="ndsc-launch__dot" id="ndsc-dot" hidden></span>
    </button>

    <section class="ndsc-panel" id="ndsc-panel" role="dialog" aria-label="Чат поддержки" hidden>
        <header class="ndsc-head">
            <div>
                <div class="ndsc-head__title">Поддержка</div>
                <div class="ndsc-head__status" id="ndsc-status">Ассистент отвечает сразу</div>
            </div>
            <button type="button" class="ndsc-close" id="ndsc-close" aria-label="Закрыть чат">×</button>
        </header>

        <div class="ndsc-msgs" id="ndsc-msgs" aria-live="polite"></div>

        <div class="ndsc-contact" id="ndsc-contact" hidden>
            <p>Оставьте почту или Telegram — пришлём ответ, даже если закроете страницу.</p>
            <form id="ndsc-contact-form">
                <input type="text" id="ndsc-contact-input" maxlength="191" placeholder="почта или @telegram" autocomplete="email" required>
                <button type="submit">ОК</button>
            </form>
        </div>

        <div class="ndsc-human">
            <button type="button" id="ndsc-human">Позвать человека</button>
        </div>

        <form class="ndsc-input" id="ndsc-form">
            <textarea id="ndsc-text" rows="1" maxlength="1000" placeholder="Напишите вопрос…" aria-label="Сообщение"></textarea>
            <button type="submit" id="ndsc-send" aria-label="Отправить">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 12h14M12 5l7 7-7 7" stroke="currentColor" stroke-width="2.8" stroke-linecap="square"/></svg>
            </button>
        </form>
    </section>
</div>

<style>
    .ndsc { --ndsc-ink: #111; --ndsc-orange: #FF4500; --ndsc-bg: #f4f4f4; font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; color: var(--ndsc-ink); }
    .ndsc *, .ndsc *::before, .ndsc *::after { box-sizing: border-box; }
    .ndsc-launch {
        position: fixed; right: 20px; bottom: 20px; z-index: 9998;
        display: flex; align-items: center; gap: .5rem;
        padding: .8rem 1.1rem; background: var(--ndsc-orange); color: #fff;
        border: 3px solid var(--ndsc-ink); box-shadow: 5px 5px 0 var(--ndsc-ink);
        font-family: inherit; font-weight: 800; font-size: 1rem; line-height: 1; text-transform: uppercase; letter-spacing: .05em; cursor: pointer;
        transition: transform .12s, box-shadow .12s;
    }
    .ndsc-launch:hover { transform: translate(2px, 2px); box-shadow: 3px 3px 0 var(--ndsc-ink); }
    .ndsc-launch__dot { position: absolute; top: -7px; right: -7px; width: 16px; height: 16px; background: var(--ndsc-ink); border: 3px solid #fff; border-radius: 50%; }
    .ndsc--open .ndsc-launch { display: none; }

    .ndsc-panel {
        position: fixed; right: 20px; bottom: 20px; z-index: 9999;
        width: 380px; height: min(580px, calc(100vh - 40px));
        display: flex; flex-direction: column;
        background: #fff; border: 3px solid var(--ndsc-ink); box-shadow: 8px 8px 0 var(--ndsc-ink);
    }
    .ndsc-panel[hidden] { display: none; }
    .ndsc-head { display: flex; justify-content: space-between; align-items: center; gap: 1rem; padding: .85rem 1rem; background: var(--ndsc-ink); color: #fff; }
    .ndsc-head__title { font-weight: 800; text-transform: uppercase; letter-spacing: .06em; }
    .ndsc-head__status { font-size: .8rem; opacity: .8; margin-top: .15rem; }
    .ndsc-close { background: none; border: 0; color: #fff; font-size: 1.9rem; line-height: 1; cursor: pointer; padding: 0 .2rem; }

    .ndsc-msgs { flex: 1; overflow-y: auto; padding: 1rem; background: var(--ndsc-bg); display: flex; flex-direction: column; gap: .65rem; }
    .ndsc-msg { max-width: 86%; padding: .6rem .8rem; font-size: .95rem; line-height: 1.45; overflow-wrap: anywhere; white-space: normal; }
    .ndsc-msg a { color: inherit; text-decoration: underline; }
    .ndsc-msg--user { align-self: flex-end; background: var(--ndsc-orange); color: #fff; border: 2px solid var(--ndsc-ink); }
    .ndsc-msg--assistant { align-self: flex-start; background: #fff; border: 2px solid var(--ndsc-ink); }
    .ndsc-msg--operator { align-self: flex-start; background: #fff; border: 2px solid var(--ndsc-orange); box-shadow: 3px 3px 0 var(--ndsc-orange); }
    .ndsc-msg__who { display: block; font-size: .72rem; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; color: var(--ndsc-orange); margin-bottom: .2rem; }
    .ndsc-msg--system { align-self: center; max-width: 100%; text-align: center; font-size: .8rem; color: #555; padding: .2rem .5rem; }
    .ndsc-typing { align-self: flex-start; font-size: .85rem; color: #555; padding: .2rem 0; }

    .ndsc-contact { padding: .75rem 1rem; border-top: 3px solid var(--ndsc-ink); background: #fff; }
    .ndsc-contact p { margin: 0 0 .5rem; font-size: .85rem; line-height: 1.35; }
    .ndsc-contact form { display: flex; gap: .5rem; }
    .ndsc-contact input { flex: 1; min-width: 0; padding: .55rem .6rem; border: 2px solid var(--ndsc-ink); font: inherit; font-size: .9rem; }
    .ndsc-contact button { padding: 0 1rem; background: var(--ndsc-ink); color: #fff; border: 2px solid var(--ndsc-ink); font-weight: 800; cursor: pointer; }

    .ndsc-human { padding: .5rem 1rem 0; background: #fff; border-top: 3px solid var(--ndsc-ink); }
    .ndsc-human button { background: none; border: 0; padding: 0; font: inherit; font-size: .85rem; font-weight: 700; color: var(--ndsc-ink); text-decoration: underline; cursor: pointer; }
    .ndsc--operator .ndsc-human { display: none; }
    .ndsc--operator .ndsc-input { border-top: 3px solid var(--ndsc-ink); }

    .ndsc-input { display: flex; gap: .5rem; padding: .6rem 1rem .9rem; background: #fff; }
    .ndsc-input textarea { flex: 1; min-width: 0; resize: none; max-height: 7.5rem; padding: .6rem .7rem; border: 2px solid var(--ndsc-ink); font: inherit; font-size: 1rem; line-height: 1.35; }
    .ndsc-input textarea:focus, .ndsc-contact input:focus { outline: 3px solid var(--ndsc-orange); outline-offset: -1px; }
    .ndsc-input button { width: 48px; flex: none; background: var(--ndsc-orange); color: #fff; border: 2px solid var(--ndsc-ink); cursor: pointer; display: grid; place-items: center; }
    .ndsc-input button:disabled { opacity: .45; cursor: default; }

    @media (max-width: 560px) {
        .ndsc-launch { right: 16px; bottom: 16px; }
        .ndsc-panel { inset: 0; width: 100%; height: 100%; height: 100dvh; border: 0; box-shadow: none; }
    }
</style>

<script>
(function () {
    var root = document.getElementById('ndsc');
    if (!root) return;
    var KEY = 'nd_support_chat';
    var sendUrl = root.dataset.send, base = root.dataset.base;
    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    var $ = function (id) { return document.getElementById(id); };
    var panel = $('ndsc-panel'), msgs = $('ndsc-msgs'), text = $('ndsc-text'), send = $('ndsc-send');
    var chatId = store(), lastId = 0, status = 'ai', busy = false, loaded = false, pollTimer = null, hasContact = false;

    function store(v) {
        try {
            if (v === undefined) return localStorage.getItem(KEY) || '';
            localStorage.setItem(KEY, v);
        } catch (e) {}
        return v || '';
    }

    function esc(s) {
        return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; });
    }
    function fmt(s) {
        return esc(s).replace(/(https?:\/\/[^\s<]+[^\s<.,;:!?)»])/g, '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>').replace(/\n/g, '<br>');
    }

    function bubble(role, content) {
        var el = document.createElement('div');
        el.className = 'ndsc-msg ndsc-msg--' + role;
        el.innerHTML = (role === 'operator' ? '<span class="ndsc-msg__who">Оператор</span>' : '') + fmt(content);
        msgs.appendChild(el);
        msgs.scrollTop = msgs.scrollHeight;
        return el;
    }

    function greet() {
        if (msgs.children.length) return;
        bubble('assistant', 'Здравствуйте! Я ассистент «Надежды». Спросите про подключение, тарифы или оплату — отвечу сразу. Нужен живой человек — нажмите «Позвать человека» внизу.');
    }

    function setStatus(s) {
        status = s;
        root.classList.toggle('ndsc--operator', s === 'operator');
        $('ndsc-status').textContent = s === 'operator' ? 'Вопрос у оператора — ответ появится здесь' : 'Ассистент отвечает сразу';
        $('ndsc-contact').hidden = !(s === 'operator' && !hasContact);
        schedulePoll();
    }

    function open() {
        panel.hidden = false;
        root.classList.add('ndsc--open');
        $('ndsc-launch').setAttribute('aria-expanded', 'true');
        $('ndsc-dot').hidden = true;
        if (!loaded) { loaded = true; chatId ? fetchMessages(true) : greet(); }
        schedulePoll();
        setTimeout(function () { text.focus(); }, 50);
    }
    function close() {
        panel.hidden = true;
        root.classList.remove('ndsc--open');
        $('ndsc-launch').setAttribute('aria-expanded', 'false');
        schedulePoll();
    }

    function fetchMessages(initial) {
        if (!chatId) return Promise.resolve();
        return fetch(base + '/' + encodeURIComponent(chatId) + '/messages?after=' + (initial ? 0 : lastId), { headers: { Accept: 'application/json' } })
            .then(function (r) {
                if (r.status === 404) { chatId = store(''); lastId = 0; throw new Error('gone'); }
                return r.json();
            })
            .then(function (d) {
                if (initial) { msgs.innerHTML = ''; greet(); }
                var fresh = false;
                d.messages.forEach(function (m) {
                    if (m.id <= lastId && !initial) return;
                    lastId = Math.max(lastId, m.id);
                    bubble(m.role, m.content);
                    if (m.role === 'operator') fresh = true;
                });
                hasContact = !!(d.contact || d.has_email);
                setStatus(d.status);
                if (fresh && !initial && panel.hidden) $('ndsc-dot').hidden = false;
            })
            .catch(function () { if (initial) greet(); });
    }

    function schedulePoll() {
        clearTimeout(pollTimer);
        if (!chatId || status !== 'operator') return;
        pollTimer = setTimeout(function () { fetchMessages(false).then(schedulePoll); }, panel.hidden ? 30000 : 5000);
    }

    function parseEvent(raw) {
        var ev = 'message', data = [];
        raw.split('\n').forEach(function (l) {
            if (l.indexOf('event:') === 0) ev = l.slice(6).trim();
            else if (l.indexOf('data:') === 0) data.push(l.slice(5).trim());
        });
        try { return { event: ev, data: JSON.parse(data.join('\n') || '{}') }; } catch (e) { return null; }
    }

    function submit(e) {
        if (e) e.preventDefault();
        var value = text.value.trim();
        if (!value || busy) return;
        busy = true; send.disabled = true;
        text.value = ''; autosize();
        bubble('user', value);
        var typing = document.createElement('div');
        typing.className = 'ndsc-typing';
        typing.textContent = status === 'operator' ? 'Отправляем оператору…' : 'Ассистент печатает…';
        msgs.appendChild(typing); msgs.scrollTop = msgs.scrollHeight;
        var answer = null, acc = '';

        fetch(sendUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'text/event-stream', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ chat: chatId || null, text: value, page: location.pathname })
        }).then(function (r) {
            if (r.status === 419) throw new Error('Страница устарела — обновите её и напишите снова.');
            if (!r.ok || !r.body) throw new Error('Не удалось отправить сообщение. Попробуйте ещё раз.');
            var reader = r.body.getReader(), dec = new TextDecoder(), buf = '';
            function pump() {
                return reader.read().then(function (res) {
                    if (res.done) return;
                    buf += dec.decode(res.value, { stream: true });
                    var parts = buf.split('\n\n'); buf = parts.pop();
                    parts.forEach(function (p) {
                        var ev = parseEvent(p); if (!ev) return;
                        if (ev.event === 'chat') { chatId = store(ev.data.id); }
                        else if (ev.event === 'delta') {
                            if (!answer) { typing.remove(); answer = bubble('assistant', ''); }
                            acc += ev.data.text; answer.innerHTML = fmt(acc); msgs.scrollTop = msgs.scrollHeight;
                        }
                        else if (ev.event === 'done') { lastId = Math.max(lastId, ev.data.message_id || 0); }
                        else if (ev.event === 'handoff') { setStatus('operator'); bubble('system', 'Вопрос передан оператору. Ответ появится здесь — обычно в течение нескольких минут.'); }
                        else if (ev.event === 'queued') { typing.remove(); lastId = Math.max(lastId, ev.data.message_id || 0); }
                        else if (ev.event === 'notice') { typing.remove(); bubble('system', ev.data.text); }
                        else if (ev.event === 'chat_error') { typing.remove(); bubble('system', ev.data.message); }
                    });
                    return pump();
                });
            }
            return pump();
        }).catch(function (err) {
            typing.remove();
            bubble('system', err && err.message ? err.message : 'Ошибка связи. Попробуйте ещё раз.');
        }).then(function () {
            typing.remove();
            busy = false; send.disabled = false; text.focus();
        });
    }

    function callHuman() {
        if (!chatId) {
            bubble('system', 'Сначала коротко опишите вопрос — и сразу передадим его человеку.');
            text.focus();
            return;
        }
        fetch(base + '/' + encodeURIComponent(chatId) + '/handoff', { method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf } })
            .then(function (r) { if (!r.ok) throw new Error(); return r.json(); })
            .then(function () { setStatus('operator'); bubble('system', 'Позвали оператора. Ответ появится здесь — обычно в течение нескольких минут.'); })
            .catch(function () { bubble('system', 'Не получилось позвать оператора. Попробуйте ещё раз.'); });
    }

    function saveContact(e) {
        e.preventDefault();
        var v = $('ndsc-contact-input').value.trim();
        if (v.length < 3 || !chatId) return;
        fetch(base + '/' + encodeURIComponent(chatId) + '/contact', {
            method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf }, body: JSON.stringify({ contact: v })
        }).then(function (r) {
            if (!r.ok) throw new Error();
            hasContact = true; $('ndsc-contact').hidden = true;
            bubble('system', 'Спасибо! Ответ продублируем: ' + v);
        }).catch(function () { bubble('system', 'Не удалось сохранить контакт. Проверьте и попробуйте ещё раз.'); });
    }

    function autosize() { text.style.height = 'auto'; text.style.height = Math.min(text.scrollHeight, 120) + 'px'; }

    $('ndsc-launch').addEventListener('click', open);
    $('ndsc-close').addEventListener('click', close);
    $('ndsc-form').addEventListener('submit', submit);
    $('ndsc-human').addEventListener('click', callHuman);
    $('ndsc-contact-form').addEventListener('submit', saveContact);
    text.addEventListener('input', autosize);
    text.addEventListener('keydown', function (e) { if (e.key === 'Enter' && !e.shiftKey) submit(e); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !panel.hidden) close(); });
    document.querySelectorAll('[data-support-chat-open]').forEach(function (a) {
        a.addEventListener('click', function (e) { e.preventDefault(); open(); });
    });

    if (location.hash === '#support-chat') open();
    else if (chatId) fetchMessages(true).then(function () { loaded = true; });
})();
</script>
