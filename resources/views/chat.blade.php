@php
    $brand = config('marketing.brand_name', 'Надежда');
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
            .lp-chat-body {
                background-color: #f4f4f4;
                background-image: radial-gradient(#d1d1d1 1px, transparent 1px);
                background-size: 20px 20px;
                margin: 0;
                min-height: 100vh;
                min-height: 100dvh;
                font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
                color: #111;
                display: flex;
                justify-content: center;
                align-items: stretch;
                padding: 0;
            }
            @media (min-width: 768px) {
                .lp-chat-body { padding: 2rem; }
            }
            .lp-chat-shell {
                background: #fff;
                width: 100%;
                max-width: 800px;
                border: 4px solid #111;
                display: flex;
                flex-direction: column;
                height: 100vh;
                height: 100dvh;
                overflow: hidden;
            }
            @media (min-width: 768px) {
                .lp-chat-shell {
                    box-shadow: 15px 15px 0 #111;
                    height: calc(100vh - 4rem);
                }
            }
            .lp-chat-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 0.75rem;
                padding: 0.85rem 1rem;
                border-bottom: 4px solid #111;
                flex-shrink: 0;
            }
            @media (min-width: 480px) {
                .lp-chat-header { padding: 1rem 1.5rem; }
            }
            .lp-chat-header__brand {
                font-size: 1rem;
                font-weight: 900;
                text-transform: uppercase;
                letter-spacing: 0.06em;
                color: #111;
                text-decoration: none;
                white-space: nowrap;
            }
            .lp-chat-header__tag {
                display: inline-block;
                background: #111;
                color: #fff;
                font-size: 0.6875rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.06em;
                padding: 0.3rem 0.55rem;
            }
            .lp-chat-header__back {
                font-size: 0.75rem;
                font-weight: 700;
                text-transform: uppercase;
                border: 2px solid #111;
                padding: 0.45rem 0.8rem;
                text-decoration: none;
                color: #111;
                transition: background 0.2s, color 0.2s;
                white-space: nowrap;
            }
            .lp-chat-header__back:hover { background: #111; color: #fff; }
            .lp-chat-model {
                border: 2px solid #111;
                background: #fff;
                color: #111;
                font-family: inherit;
                font-size: 0.75rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.04em;
                padding: 0.4rem 0.5rem;
                cursor: pointer;
                appearance: none;
                -webkit-appearance: none;
                border-radius: 0;
                max-width: 9.5rem;
            }
            .lp-chat-model:hover { background: #fffde7; }
            .lp-chat-messages {
                flex: 1;
                overflow-y: auto;
                padding: 1.1rem 1rem;
                display: flex;
                flex-direction: column;
                gap: 0.85rem;
                scroll-behavior: smooth;
            }
            @media (min-width: 480px) {
                .lp-chat-messages { padding: 1.35rem 1.5rem; }
            }
            .lp-chat-msg {
                max-width: 88%;
                border: 3px solid #111;
                padding: 0.7rem 0.9rem;
                font-size: 0.9375rem;
                line-height: 1.55;
                white-space: pre-wrap;
                overflow-wrap: break-word;
            }
            @media (min-width: 480px) {
                .lp-chat-msg { max-width: 80%; }
            }
            .lp-chat-msg--user {
                align-self: flex-end;
                background: #111;
                color: #fff;
                box-shadow: 5px 5px 0 rgba(17, 17, 17, 0.25);
            }
            .lp-chat-msg--assistant {
                align-self: flex-start;
                background: #fffde7;
                box-shadow: 5px 5px 0 #111;
            }
            .lp-chat-msg--error {
                align-self: flex-start;
                background: #fff;
                border-color: #FF4500;
                color: #b53000;
                font-weight: 700;
                box-shadow: 5px 5px 0 #FF4500;
            }
            .lp-chat-empty {
                margin: auto;
                text-align: center;
                color: #666;
                font-size: 0.875rem;
                font-weight: 500;
                max-width: 26rem;
                padding: 0 1rem;
            }
            .lp-chat-empty strong {
                display: block;
                color: #111;
                font-weight: 900;
                text-transform: uppercase;
                font-size: 1.05rem;
                margin-bottom: 0.4rem;
            }
            .lp-chat-typing::after {
                content: "▌";
                animation: lp-chat-blink 1s steps(1) infinite;
            }
            @keyframes lp-chat-blink { 50% { opacity: 0; } }
            .lp-chat-inputbar {
                display: flex;
                align-items: stretch;
                border-top: 4px solid #111;
                flex-shrink: 0;
                background: #fff;
            }
            .lp-chat-inputbar textarea {
                flex: 1;
                border: none;
                outline: none;
                resize: none;
                font-family: inherit;
                font-size: 1rem;
                line-height: 1.45;
                padding: 0.95rem 1rem;
                max-height: 9.5rem;
                background: transparent;
                color: #111;
            }
            @media (min-width: 480px) {
                .lp-chat-inputbar textarea { padding: 1.05rem 1.5rem; }
            }
            .lp-chat-send {
                background: #FF4500;
                color: #fff;
                border: none;
                border-left: 4px solid #111;
                font-family: inherit;
                font-weight: 900;
                font-size: 0.875rem;
                text-transform: uppercase;
                letter-spacing: 0.04em;
                padding: 0 1.25rem;
                cursor: pointer;
                transition: background 0.2s;
            }
            @media (min-width: 480px) {
                .lp-chat-send { padding: 0 1.9rem; font-size: 0.9375rem; }
            }
            .lp-chat-send:hover { background: #E03E00; }
            .lp-chat-send:disabled { background: #999; cursor: not-allowed; }
        </style>
    </head>
    <body class="lp-chat-body">
        <div class="lp-chat-shell">
            <header class="lp-chat-header">
                <a href="{{ url('/') }}" class="lp-chat-header__brand">{{ $brand }}</a>
                <select id="chat-model" class="lp-chat-model" aria-label="Модель">
                    @foreach (config('chat.models') as $key => $model)
                        <option value="{{ $key }}" @selected($key === config('chat.default_model'))>{{ $model['label'] }}</option>
                    @endforeach
                </select>
                <a href="{{ route('dashboard') }}" class="lp-chat-header__back">Кабинет</a>
            </header>

            <main id="chat-messages" class="lp-chat-messages" aria-live="polite">
                <div id="chat-empty" class="lp-chat-empty">
                    <strong>Чем помочь?</strong>
                    Задайте любой вопрос — ассистент ответит здесь. История хранится только в этой вкладке.
                </div>
            </main>

            <form id="chat-form" class="lp-chat-inputbar">
                <textarea
                    id="chat-input"
                    rows="1"
                    placeholder="Напишите сообщение…"
                    autocomplete="off"
                    autofocus
                ></textarea>
                <button id="chat-send" type="submit" class="lp-chat-send">Отправить</button>
            </form>
        </div>

        <script>
            (function () {
                'use strict';

                var endpoint = @json(route('chat.message'));
                var csrf = document.querySelector('meta[name="csrf-token"]').content;

                var modelEl = document.getElementById('chat-model');
                var messagesEl = document.getElementById('chat-messages');
                var emptyEl = document.getElementById('chat-empty');
                var formEl = document.getElementById('chat-form');
                var inputEl = document.getElementById('chat-input');
                var sendEl = document.getElementById('chat-send');

                var history = [];
                var busy = false;

                function scrollDown() {
                    messagesEl.scrollTop = messagesEl.scrollHeight;
                }

                function addBubble(kind, text) {
                    if (emptyEl) { emptyEl.remove(); emptyEl = null; }
                    var el = document.createElement('div');
                    el.className = 'lp-chat-msg lp-chat-msg--' + kind;
                    el.textContent = text;
                    messagesEl.appendChild(el);
                    scrollDown();
                    return el;
                }

                function autoGrow() {
                    inputEl.style.height = 'auto';
                    inputEl.style.height = Math.min(inputEl.scrollHeight, 152) + 'px';
                }
                inputEl.addEventListener('input', autoGrow);

                inputEl.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        formEl.requestSubmit();
                    }
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
                    inputEl.disabled = value;
                    if (!value) inputEl.focus();
                }

                async function send(text) {
                    setBusy(true);
                    history.push({ role: 'user', content: text });
                    addBubble('user', text);

                    var bubble = addBubble('assistant', '');
                    bubble.classList.add('lp-chat-typing');
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
                            body: JSON.stringify({ model: modelEl.value, messages: history })
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
                                        answer += evt.data.text;
                                        bubble.textContent = answer;
                                        scrollDown();
                                    }
                                }
                            }
                        }
                    } catch (err) {
                        failed = 'Соединение прервалось. Попробуйте ещё раз.';
                    }

                    bubble.classList.remove('lp-chat-typing');

                    if (answer !== '') {
                        history.push({ role: 'assistant', content: answer });
                        if (failed) addBubble('error', 'Ответ мог прерваться: ' + failed);
                    } else {
                        bubble.remove();
                        history.pop();
                        addBubble('error', failed || 'Пустой ответ. Попробуйте ещё раз.');
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
