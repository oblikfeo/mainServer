@php
    $brand = config('marketing.brand_name', 'Надежда');
    $chatModels = (array) config('chat.models');
    $defaultModel = (string) config('chat.default_model');
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="robots" content="noindex, nofollow">
        <title>{{ $brand }} — чат</title>
        @include('partials.favicon')
        <style>
            :root {
                --ink: #111;
                --orange: #FF4500;
                --orange-dark: #E03E00;
                --paper: #fff;
                --cream: #fffde7;
            }
            * { box-sizing: border-box; }
            .lp-chat-body {
                background-color: #f4f4f4;
                background-image: radial-gradient(#d1d1d1 1px, transparent 1px);
                background-size: 20px 20px;
                margin: 0;
                min-height: 100vh;
                min-height: 100dvh;
                font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
                color: var(--ink);
                display: flex;
                justify-content: center;
                align-items: stretch;
                padding: 0;
            }
            @media (min-width: 768px) {
                .lp-chat-body { padding: 2rem; }
            }

            /* ======== каркас ======== */
            .lp-chat-shell {
                background: var(--paper);
                width: 100%;
                max-width: 820px;
                border: 4px solid var(--ink);
                display: flex;
                flex-direction: column;
                height: 100vh;
                height: 100dvh;
                overflow: hidden;
                animation: shell-in 0.45s cubic-bezier(0.2, 0.9, 0.25, 1.15) both;
            }
            @media (min-width: 768px) {
                .lp-chat-shell {
                    box-shadow: 15px 15px 0 var(--ink);
                    height: calc(100vh - 4rem);
                }
            }
            @keyframes shell-in {
                from { opacity: 0; transform: translateY(18px) scale(0.985); box-shadow: 0 0 0 var(--ink); }
            }

            /* ======== шапка ======== */
            .lp-chat-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 0.75rem;
                padding: 0.85rem 1rem;
                border-bottom: 4px solid var(--ink);
                flex-shrink: 0;
                background: var(--paper);
            }
            @media (min-width: 560px) {
                .lp-chat-header { padding: 1rem 1.5rem; }
            }
            .lp-chat-header__brand {
                font-size: 1rem;
                font-weight: 900;
                text-transform: uppercase;
                letter-spacing: 0.06em;
                color: var(--ink);
                text-decoration: none;
                white-space: nowrap;
            }
            .lp-chat-header__back {
                font-size: 0.7rem;
                font-weight: 900;
                text-transform: uppercase;
                letter-spacing: 0.04em;
                border: 3px solid var(--ink);
                padding: 0.5rem 0.8rem;
                text-decoration: none;
                color: var(--ink);
                background: var(--paper);
                box-shadow: 4px 4px 0 var(--ink);
                transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
                white-space: nowrap;
            }
            .lp-chat-header__back:hover {
                transform: translate(-2px, -2px);
                box-shadow: 6px 6px 0 var(--ink);
                background: var(--cream);
            }
            .lp-chat-header__back:active {
                transform: translate(2px, 2px);
                box-shadow: 1px 1px 0 var(--ink);
            }

            /* ======== лента сообщений ======== */
            .lp-chat-messages {
                flex: 1;
                overflow-y: auto;
                padding: 1.25rem 1rem 1.5rem;
                display: flex;
                flex-direction: column;
                gap: 1rem;
            }
            @media (min-width: 560px) {
                .lp-chat-messages { padding: 1.5rem 1.75rem 1.75rem; }
            }
            .lp-chat-messages::-webkit-scrollbar { width: 10px; }
            .lp-chat-messages::-webkit-scrollbar-track { background: #f1f1f1; }
            .lp-chat-messages::-webkit-scrollbar-thumb {
                background: var(--ink);
                border: 2px solid #f1f1f1;
            }

            .lp-chat-row {
                display: flex;
                flex-direction: column;
                max-width: 86%;
                animation: msg-in 0.32s cubic-bezier(0.2, 0.9, 0.3, 1.25) both;
            }
            @media (min-width: 560px) {
                .lp-chat-row { max-width: 78%; }
            }
            @keyframes msg-in {
                from { opacity: 0; transform: translateY(16px) scale(0.96); }
            }
            .lp-chat-row--user { align-self: flex-end; align-items: flex-end; }
            .lp-chat-row--assistant { align-self: flex-start; align-items: flex-start; }
            .lp-chat-msg {
                border: 3px solid var(--ink);
                padding: 0.75rem 0.95rem;
                font-size: 0.9375rem;
                line-height: 1.55;
                white-space: pre-wrap;
                overflow-wrap: break-word;
                min-width: 3.5rem;
            }
            .lp-chat-row--user .lp-chat-msg {
                background: var(--orange);
                color: #fff;
                box-shadow: 6px 6px 0 var(--ink);
                font-weight: 500;
            }
            .lp-chat-row--assistant .lp-chat-msg {
                background: var(--cream);
                box-shadow: 6px 6px 0 var(--ink);
            }
            .lp-chat-row--error .lp-chat-msg {
                background: var(--paper);
                border-color: var(--orange);
                color: #b53000;
                font-weight: 700;
                box-shadow: 6px 6px 0 var(--orange);
            }

            /* индикатор набора: прыгающие квадраты */
            .lp-chat-dots {
                display: inline-flex;
                gap: 5px;
                padding: 0.2rem 0;
            }
            .lp-chat-dots i {
                width: 9px;
                height: 9px;
                background: var(--ink);
                animation: dot-jump 0.9s ease-in-out infinite;
            }
            .lp-chat-dots i:nth-child(2) { animation-delay: 0.12s; background: var(--orange); }
            .lp-chat-dots i:nth-child(3) { animation-delay: 0.24s; }
            @keyframes dot-jump {
                0%, 60%, 100% { transform: translateY(0); }
                30% { transform: translateY(-7px) rotate(45deg); }
            }
            .lp-chat-cursor::after {
                content: "▌";
                color: var(--orange);
                animation: cursor-blink 0.9s steps(1) infinite;
            }
            @keyframes cursor-blink { 50% { opacity: 0; } }

            /* ======== пустое состояние ======== */
            .lp-chat-empty {
                margin: auto;
                text-align: center;
                max-width: 30rem;
                padding: 0 1rem;
                animation: msg-in 0.5s 0.15s cubic-bezier(0.2, 0.9, 0.3, 1.2) both;
            }
            .lp-chat-empty h1 {
                font-size: 1.9rem;
                line-height: 1.1;
                font-weight: 900;
                text-transform: uppercase;
                letter-spacing: -0.02em;
                margin: 0 0 1.4rem;
            }
            @media (min-width: 560px) {
                .lp-chat-empty h1 { font-size: 2.4rem; }
            }
            .lp-chat-empty h1 em {
                font-style: normal;
                background: var(--orange);
                color: #fff;
                padding: 0 0.35rem;
                display: inline-block;
                transform: rotate(-1.5deg);
            }
            .lp-chat-chips {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                gap: 0.6rem;
            }
            .lp-chat-chip {
                appearance: none;
                font-family: inherit;
                font-size: 0.75rem;
                font-weight: 700;
                border: 3px solid var(--ink);
                background: var(--paper);
                color: var(--ink);
                padding: 0.55rem 0.85rem;
                cursor: pointer;
                box-shadow: 4px 4px 0 var(--ink);
                transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
            }
            .lp-chat-chip:hover {
                transform: translate(-2px, -2px);
                box-shadow: 6px 6px 0 var(--ink);
                background: var(--cream);
            }
            .lp-chat-chip:active {
                transform: translate(2px, 2px);
                box-shadow: 1px 1px 0 var(--ink);
            }

            /* ======== панель ввода ======== */
            .lp-chat-inputbar {
                position: relative;
                border-top: 4px solid var(--ink);
                flex-shrink: 0;
                background: var(--paper);
                padding: 0.9rem 1rem;
            }
            @media (min-width: 560px) {
                .lp-chat-inputbar { padding: 1rem 1.5rem; }
            }
            .lp-chat-inputbar__box {
                display: flex;
                align-items: flex-end;
                border: 3px solid var(--ink);
                background: var(--paper);
                box-shadow: 5px 5px 0 var(--ink);
                transition: box-shadow 0.2s ease, transform 0.2s ease;
            }
            .lp-chat-inputbar__box:focus-within {
                box-shadow: 7px 7px 0 var(--orange);
                transform: translate(-1px, -1px);
            }
            .lp-chat-inputbar textarea {
                flex: 1;
                border: none;
                outline: none;
                resize: none;
                font-family: inherit;
                font-size: 1rem;
                line-height: 1.5;
                padding: 0.8rem 0.95rem;
                height: 3.1rem;
                max-height: 10rem;
                background: transparent;
                color: var(--ink);
            }
            .lp-chat-inputbar textarea::placeholder { color: #999; font-weight: 500; }

            /* кнопка выбора модели */
            .lp-chat-modelbtn {
                align-self: stretch;
                display: flex;
                align-items: center;
                justify-content: center;
                appearance: none;
                background: var(--paper);
                color: var(--ink);
                border: none;
                border-right: 3px solid var(--ink);
                padding: 0 0.85rem;
                cursor: pointer;
                transition: background 0.15s ease, color 0.15s ease;
            }
            .lp-chat-modelbtn:hover { background: var(--cream); }
            .lp-chat-modelbtn.is-open { background: var(--ink); color: #fff; }
            .lp-chat-modelbtn svg { display: block; }

            /* всплывающее окошко выбора модели */
            .lp-chat-pop {
                position: absolute;
                bottom: calc(100% + 0.65rem);
                left: 1rem;
                min-width: 13rem;
                background: var(--paper);
                border: 3px solid var(--ink);
                box-shadow: 7px 7px 0 var(--ink);
                z-index: 20;
                transform-origin: bottom left;
                animation: pop-in 0.22s cubic-bezier(0.3, 1.4, 0.4, 1) both;
            }
            @media (min-width: 560px) {
                .lp-chat-pop { left: 1.5rem; }
            }
            @keyframes pop-in {
                from { opacity: 0; transform: translateY(10px) scale(0.9); }
            }
            .lp-chat-pop__title {
                font-size: 0.6rem;
                font-weight: 900;
                text-transform: uppercase;
                letter-spacing: 0.16em;
                background: var(--ink);
                color: #fff;
                padding: 0.5rem 0.85rem;
            }
            .lp-chat-pop__item {
                display: flex;
                align-items: center;
                gap: 0.6rem;
                width: 100%;
                appearance: none;
                text-align: left;
                background: var(--paper);
                border: none;
                border-top: 2px solid var(--ink);
                font-family: inherit;
                font-size: 0.8rem;
                font-weight: 900;
                text-transform: uppercase;
                letter-spacing: 0.05em;
                color: var(--ink);
                padding: 0.7rem 0.85rem;
                cursor: pointer;
                transition: background 0.15s ease, padding-left 0.15s ease;
            }
            .lp-chat-pop__item:first-of-type { border-top: none; }
            .lp-chat-pop__item:hover { background: var(--cream); padding-left: 1.05rem; }
            .lp-chat-pop__item i {
                flex-shrink: 0;
                width: 11px;
                height: 11px;
                border: 2px solid var(--ink);
                background: var(--paper);
                transition: background 0.15s ease, transform 0.15s ease;
            }
            .lp-chat-pop__item.is-active i {
                background: var(--orange);
                transform: rotate(45deg);
            }

            /* кнопка отправки */
            .lp-chat-send {
                align-self: stretch;
                background: var(--orange);
                color: #fff;
                border: none;
                border-left: 3px solid var(--ink);
                padding: 0 1.2rem;
                cursor: pointer;
                transition: background 0.15s ease;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .lp-chat-send svg {
                display: block;
                transition: transform 0.2s cubic-bezier(0.3, 1.5, 0.5, 1);
            }
            .lp-chat-send:hover { background: var(--orange-dark); }
            .lp-chat-send:hover svg { transform: translateX(3px) rotate(8deg); }
            .lp-chat-send:active svg { transform: translateX(6px) scale(0.9) rotate(15deg); }
            .lp-chat-send:disabled { background: #b3b3b3; cursor: not-allowed; }
            .lp-chat-send:disabled svg { transform: none; animation: send-pulse 1s ease-in-out infinite; }
            @keyframes send-pulse {
                50% { opacity: 0.35; }
            }

            @media (prefers-reduced-motion: reduce) {
                *, *::before, *::after {
                    animation-duration: 0.01ms !important;
                    animation-iteration-count: 1 !important;
                    transition-duration: 0.01ms !important;
                }
            }
        </style>
    </head>
    <body class="lp-chat-body">
        <div class="lp-chat-shell">
            <header class="lp-chat-header">
                <a href="{{ url('/') }}" class="lp-chat-header__brand">{{ $brand }}</a>
                <a href="{{ route('dashboard') }}" class="lp-chat-header__back">Кабинет</a>
            </header>

            <main id="chat-messages" class="lp-chat-messages" aria-live="polite">
                <div id="chat-empty" class="lp-chat-empty">
                    <h1>Чем <em>помочь?</em></h1>
                    <div class="lp-chat-chips">
                        <button type="button" class="lp-chat-chip" data-prompt="Какой тариф мне подойдёт?">Какой тариф выбрать?</button>
                        <button type="button" class="lp-chat-chip" data-prompt="Как подключиться и начать пользоваться?">Как подключиться?</button>
                        <button type="button" class="lp-chat-chip" data-prompt="Есть ли бесплатный пробный период?">Пробный период</button>
                    </div>
                </div>
            </main>

            <div class="lp-chat-inputbar">
                <div id="model-pop" class="lp-chat-pop" hidden>
                    <div class="lp-chat-pop__title">Модель</div>
                    @foreach ($chatModels as $key => $model)
                        <button
                            type="button"
                            data-model="{{ $key }}"
                            class="lp-chat-pop__item {{ $key === $defaultModel ? 'is-active' : '' }}"
                        ><i></i>{{ $model['label'] }}</button>
                    @endforeach
                </div>

                <form id="chat-form" class="lp-chat-inputbar__box">
                    <button
                        type="button"
                        id="model-trigger"
                        class="lp-chat-modelbtn"
                        aria-haspopup="true"
                        aria-expanded="false"
                        title="Выбрать модель"
                    >
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="square">
                            <rect x="5" y="5" width="14" height="14"/>
                            <rect x="9.6" y="9.6" width="4.8" height="4.8"/>
                            <path d="M9 2v3M15 2v3M9 19v3M15 19v3M2 9h3M2 15h3M19 9h3M19 15h3"/>
                        </svg>
                    </button>
                    <textarea
                        id="chat-input"
                        rows="1"
                        placeholder="Напишите сообщение…"
                        autocomplete="off"
                        autofocus
                    ></textarea>
                    <button id="chat-send" type="submit" class="lp-chat-send" aria-label="Отправить">
                        <svg width="21" height="21" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M2.6 21.2 22.8 12 2.6 2.8l-.01 7.16L15.4 12 2.59 14.04z"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>

        <script>
            (function () {
                'use strict';

                var endpoint = @json(route('chat.message'));
                var csrf = document.querySelector('meta[name="csrf-token"]').content;

                var messagesEl = document.getElementById('chat-messages');
                var emptyEl = document.getElementById('chat-empty');
                var formEl = document.getElementById('chat-form');
                var inputEl = document.getElementById('chat-input');
                var sendEl = document.getElementById('chat-send');
                var popEl = document.getElementById('model-pop');
                var triggerEl = document.getElementById('model-trigger');
                var popItems = Array.prototype.slice.call(popEl.querySelectorAll('button[data-model]'));

                var history = [];
                var busy = false;
                var currentModel = @json($defaultModel);

                /* --- выбор модели --- */
                function setModel(key) {
                    currentModel = key;
                    popItems.forEach(function (btn) {
                        btn.classList.toggle('is-active', btn.dataset.model === key);
                    });
                }
                function openPop() {
                    popEl.hidden = false;
                    triggerEl.classList.add('is-open');
                    triggerEl.setAttribute('aria-expanded', 'true');
                }
                function closePop() {
                    popEl.hidden = true;
                    triggerEl.classList.remove('is-open');
                    triggerEl.setAttribute('aria-expanded', 'false');
                }
                triggerEl.addEventListener('click', function (e) {
                    e.stopPropagation();
                    popEl.hidden ? openPop() : closePop();
                });
                popItems.forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        setModel(btn.dataset.model);
                        closePop();
                        inputEl.focus();
                    });
                });
                document.addEventListener('click', function (e) {
                    if (!popEl.hidden && !popEl.contains(e.target)) closePop();
                });
                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape') closePop();
                });

                /* --- лента --- */
                function scrollDown() {
                    messagesEl.scrollTo({ top: messagesEl.scrollHeight, behavior: 'smooth' });
                }

                function addRow(kind) {
                    if (emptyEl) { emptyEl.remove(); emptyEl = null; }
                    var row = document.createElement('div');
                    row.className = 'lp-chat-row lp-chat-row--' + kind;
                    var msg = document.createElement('div');
                    msg.className = 'lp-chat-msg';
                    row.appendChild(msg);
                    messagesEl.appendChild(row);
                    scrollDown();
                    return { row: row, msg: msg };
                }

                function addText(kind, text) {
                    var b = addRow(kind);
                    b.msg.textContent = text;
                    scrollDown();
                    return b;
                }

                function typingDots() {
                    var dots = document.createElement('span');
                    dots.className = 'lp-chat-dots';
                    dots.innerHTML = '<i></i><i></i><i></i>';
                    return dots;
                }

                /* --- поле ввода: рост без скачков --- */
                var baseHeight = inputEl.offsetHeight;
                function autoGrow() {
                    inputEl.style.height = '0px';
                    var needed = Math.min(Math.max(inputEl.scrollHeight, baseHeight), 160);
                    inputEl.style.height = needed + 'px';
                }
                inputEl.addEventListener('input', autoGrow);

                inputEl.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        formEl.requestSubmit();
                    }
                });

                /* --- чипы-подсказки --- */
                Array.prototype.slice.call(document.querySelectorAll('.lp-chat-chip')).forEach(function (chip) {
                    chip.addEventListener('click', function () {
                        if (busy) return;
                        inputEl.value = chip.dataset.prompt;
                        autoGrow();
                        formEl.requestSubmit();
                    });
                });

                formEl.addEventListener('submit', function (e) {
                    e.preventDefault();
                    if (busy) return;
                    var text = inputEl.value.trim();
                    if (text === '') return;
                    inputEl.value = '';
                    autoGrow();
                    send(text);
                });

                function setBusy(value) {
                    busy = value;
                    sendEl.disabled = value;
                    if (!value) inputEl.focus();
                }

                async function send(text) {
                    setBusy(true);
                    history.push({ role: 'user', content: text });
                    addText('user', text);

                    var bubble = addRow('assistant');
                    bubble.msg.appendChild(typingDots());
                    var answer = '';
                    var failed = null;

                    try {
                        var response = await fetch(endpoint, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'text/event-stream',
                                'X-CSRF-TOKEN': csrf
                            },
                            body: JSON.stringify({ model: currentModel, messages: history })
                        });

                        if (!response.ok || !response.body) {
                            failed = response.status === 419
                                ? 'Сессия устарела — обновите страницу.'
                                : 'Не удалось отправить сообщение (' + response.status + ').';
                        } else {
                            var reader = response.body.getReader();
                            var decoder = new TextDecoder();
                            var buffer = '';

                            while (true) {
                                var chunk = await reader.read();
                                if (chunk.done) break;
                                buffer += decoder.decode(chunk.value, { stream: true });

                                var parts = buffer.split('\n\n');
                                buffer = parts.pop();

                                for (var i = 0; i < parts.length; i++) {
                                    var evt = parseSse(parts[i]);
                                    if (!evt) continue;

                                    if (evt.event === 'chat_error') {
                                        failed = evt.data.message || 'Ошибка сервиса.';
                                    } else if (evt.event === 'delta' && typeof evt.data.text === 'string') {
                                        if (answer === '') bubble.msg.classList.add('lp-chat-cursor');
                                        answer += evt.data.text;
                                        bubble.msg.textContent = answer;
                                        scrollDown();
                                    }
                                }
                            }
                        }
                    } catch (err) {
                        failed = 'Соединение прервалось. Попробуйте ещё раз.';
                    }

                    bubble.msg.classList.remove('lp-chat-cursor');

                    if (answer !== '') {
                        history.push({ role: 'assistant', content: answer });
                        if (failed) addText('error', 'Ответ мог прерваться: ' + failed);
                    } else {
                        bubble.row.remove();
                        history.pop();
                        addText('error', failed || 'Пустой ответ. Попробуйте ещё раз.');
                    }

                    setBusy(false);
                }

                function parseSse(block) {
                    var lines = block.split('\n');
                    var event = 'message';
                    var dataLines = [];
                    for (var i = 0; i < lines.length; i++) {
                        var line = lines[i];
                        if (line.indexOf('event:') === 0) {
                            event = line.slice(6).trim();
                        } else if (line.indexOf('data:') === 0) {
                            dataLines.push(line.slice(5).trim());
                        }
                    }
                    if (dataLines.length === 0) return null;
                    try {
                        return { event: event, data: JSON.parse(dataLines.join('\n')) };
                    } catch (e) {
                        return null;
                    }
                }
            })();
        </script>
    </body>
</html>
