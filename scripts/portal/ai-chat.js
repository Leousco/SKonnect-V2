(() => {
    const root = document.getElementById('ai-chat');
    const button = document.getElementById('ai-chat-button');
    const panel = document.getElementById('ai-chat-panel');
    const closeButton = document.getElementById('ai-chat-close');
    const input = document.getElementById('ai-chat-input');
    const form = document.getElementById('ai-chat-form');
    const messagesElement = document.getElementById('ai-chat-messages');
    const welcome = document.getElementById('ai-chat-welcome');
    const sendButton = form ? form.querySelector('.ai-chat-send') : null;

    if (!root || !button || !panel || !closeButton || !input || !form || !messagesElement || !welcome || !sendButton) return;

    const endpoint = root.dataset.endpoint || '../../routes/ai.php';
    const flagEndpoint = root.dataset.flagEndpoint || '../../routes/chatbot_answer_flag.php';
    const fallbackError = 'Sorry, I couldn’t reach Kaya. Please try again.';
    const MAX_HISTORY_TURNS = 8;
    const MAX_HISTORY_CHARS = 1000;
    const conversation = [];
    let pending = false;
    let closeTimer = null;

    const appendInlineText = (element, content) => {
        const pattern = /\*\*(.+?)\*\*|\*(.+?)\*|`([^`]+)`/g;
        let cursor = 0;
        let match;
        while ((match = pattern.exec(content)) !== null) {
            element.append(document.createTextNode(content.slice(cursor, match.index)));
            const formatted = document.createElement(match[1] ? 'strong' : match[2] ? 'em' : 'code');
            formatted.textContent = match[1] || match[2] || match[3];
            element.append(formatted);
            cursor = pattern.lastIndex;
        }
        element.append(document.createTextNode(content.slice(cursor)));
    };

    const appendFormattedReply = (container, content) => {
        const lines = content.replace(/\r/g, '').split('\n');
        let paragraph = [];
        let list = null;
        let listType = '';

        const flushParagraph = () => {
            if (!paragraph.length) return;
            const block = document.createElement('p');
            appendInlineText(block, paragraph.join(' '));
            container.append(block);
            paragraph = [];
        };
        const flushList = () => {
            if (list) container.append(list);
            list = null;
            listType = '';
        };

        lines.forEach((rawLine) => {
            const line = rawLine.trim();
            if (!line) {
                flushParagraph();
                flushList();
                return;
            }

            const heading = line.match(/^#{1,3}\s+(.+)$/);
            const bullet = line.match(/^(?:[-*•])\s+(.+)$/);
            const numbered = line.match(/^\d+[.)]\s+(.+)$/);
            if (heading) {
                flushParagraph();
                flushList();
                const title = document.createElement('strong');
                title.className = 'ai-chat-content-heading';
                appendInlineText(title, heading[1]);
                container.append(title);
                return;
            }
            if (bullet || numbered) {
                flushParagraph();
                const nextType = bullet ? 'ul' : 'ol';
                if (listType !== nextType) flushList();
                if (!list) {
                    list = document.createElement(nextType);
                    listType = nextType;
                }
                const item = document.createElement('li');
                appendInlineText(item, (bullet || numbered)[1]);
                list.append(item);
                return;
            }

            flushList();
            paragraph.push(line);
        });

        flushParagraph();
        flushList();
    };

    const appendMessage = ({ role, content, typing = false, faqIds = [] }) => {
        const message = document.createElement('div');
        message.className = `ai-chat-message ai-chat-message-${role}`;
        if (typing) message.classList.add('ai-chat-message-typing');

        if (role === 'assistant') {
            const avatar = document.createElement('span');
            avatar.className = 'ai-chat-message-avatar';
            avatar.setAttribute('aria-hidden', 'true');
            const image = document.createElement('img');
            image.src = '../../assets/img/kaya-icon.svg';
            image.alt = '';
            avatar.append(image);
            message.append(avatar);
        }

        if (role === 'assistant' && !typing) {
            const formatted = document.createElement('div');
            formatted.className = 'ai-chat-content';
            appendFormattedReply(formatted, content);
            message.append(formatted);
            const validFaqIds = Array.isArray(faqIds)
                ? [...new Set(faqIds.filter((id) => Number.isInteger(id) && id > 0))]
                : [];
            if (validFaqIds.length > 0) {
                const report = document.createElement('button');
                report.className = 'ai-chat-report';
                report.type = 'button';
                report.textContent = 'Report wrong or outdated answer';
                report.addEventListener('click', async () => {
                    report.disabled = true;
                    try {
                        const response = await fetch(flagEndpoint, {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                            body: JSON.stringify({ faq_ids: validFaqIds }),
                        });
                        const data = await response.json();
                        if (!response.ok || !data.success) {
                            throw new Error(data.message || 'Unable to send feedback.');
                        }
                        report.textContent = 'Reported — thank you';
                    } catch (error) {
                        report.disabled = false;
                        report.textContent = 'Could not report. Try again.';
                    }
                });
                message.append(report);
            }
        } else {
            const text = document.createElement('p');
            text.textContent = content;
            message.append(text);
        }
        messagesElement.append(message);
        return message;
    };

    const scrollToLatest = () => {
        messagesElement.scrollTop = messagesElement.scrollHeight;
    };

    const setPending = (value) => {
        pending = value;
        input.disabled = value;
        sendButton.disabled = value;
        form.setAttribute('aria-busy', String(value));
    };

    const rememberTurn = (role, content) => {
        const text = String(content || '').trim().slice(0, MAX_HISTORY_CHARS);
        if (!text) return;

        conversation.push({ role, content: text });
        if (conversation.length > MAX_HISTORY_TURNS) {
            conversation.splice(0, conversation.length - MAX_HISTORY_TURNS);
        }
    };

    const setOpen = (isOpen) => {
        if (closeTimer !== null) {
            window.clearTimeout(closeTimer);
            closeTimer = null;
        }

        panel.setAttribute('aria-hidden', String(!isOpen));
        root.classList.toggle('ai-chat--open', isOpen);
        root.classList.toggle('ai-chat--closing', !isOpen);
        button.disabled = isOpen;
        button.setAttribute('aria-expanded', String(isOpen));
        if (isOpen) {
            panel.hidden = false;
            input.focus();
            scrollToLatest();
        } else {
            closeTimer = window.setTimeout(finishClose, 500);
            button.focus();
        }
    };

    function finishClose() {
        if (!root.classList.contains('ai-chat--closing')) return;
        panel.hidden = true;
        root.classList.remove('ai-chat--closing');
        if (closeTimer !== null) {
            window.clearTimeout(closeTimer);
            closeTimer = null;
        }
    }

    root.addEventListener('transitionend', (event) => {
        if (event.target === root && event.propertyName === 'width') finishClose();
    });

    button.addEventListener('click', () => {
        if (!root.classList.contains('ai-chat--open')) setOpen(true);
    });
    closeButton.addEventListener('click', () => setOpen(false));

    panel.querySelectorAll('.ai-chat-prompt').forEach((prompt) => {
        prompt.addEventListener('click', () => {
            input.value = prompt.dataset.prompt || '';
            form.requestSubmit();
        });
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const content = input.value.trim();
        if (!content || pending) return;

        welcome.hidden = true;
        const history = conversation.slice(-MAX_HISTORY_TURNS);
        appendMessage({ role: 'user', content });
        rememberTurn('user', content);
        input.value = '';
        setPending(true);
        const typing = appendMessage({ role: 'assistant', content: 'Typing…', typing: true });
        scrollToLatest();

        let reply = fallbackError;
        let faqIds = [];
        const controller = new AbortController();
        const timer = window.setTimeout(() => controller.abort(), 40000);

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                body: JSON.stringify({ message: content, history }),
                signal: controller.signal,
            });
            const data = await response.json().catch(() => null);
            if (data && typeof data.message === 'string' && data.message) {
                reply = data.message;
                faqIds = Array.isArray(data.faq_ids) ? data.faq_ids : [];
            }
        } catch (error) {
        } finally {
            window.clearTimeout(timer);
        }

        typing.remove();
        appendMessage({ role: 'assistant', content: reply, faqIds });
        rememberTurn('assistant', reply);
        setPending(false);
        scrollToLatest();
        input.focus();
    });
})();
