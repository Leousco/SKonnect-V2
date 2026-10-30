(() => {
    const root = document.getElementById('ai-chat');
    const button = document.getElementById('ai-chat-button');
    const panel = document.getElementById('ai-chat-panel');
    const closeButton = document.getElementById('ai-chat-close');
    const input = document.getElementById('ai-chat-input');
    const form = document.getElementById('ai-chat-form');
    const messagesElement = document.getElementById('ai-chat-messages');
    const sendButton = form ? form.querySelector('.ai-chat-send') : null;

    if (!root || !button || !panel || !closeButton || !input || !form || !messagesElement || !sendButton) return;

    const endpoint = root.dataset.endpoint || '../../routes/ai.php';
    const greeting = 'Hi! I’m your SK Assistant. How can I help you today?';
    const fallbackError = 'Sorry, I couldn’t reach the assistant. Please try again.';
    let pending = false;

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

    const appendMessage = ({ role, content, typing = false }) => {
        const message = document.createElement('div');
        message.className = `ai-chat-message ai-chat-message-${role}`;
        if (typing) message.classList.add('ai-chat-message-typing');

        if (role === 'assistant') {
            const avatar = document.createElement('span');
            avatar.className = 'ai-chat-message-avatar';
            avatar.setAttribute('aria-hidden', 'true');
            avatar.textContent = 'SK';
            message.append(avatar);
        }

        if (role === 'assistant' && !typing) {
            const formatted = document.createElement('div');
            formatted.className = 'ai-chat-content';
            appendFormattedReply(formatted, content);
            message.append(formatted);
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

    appendMessage({ role: 'assistant', content: greeting });

    const setOpen = (isOpen) => {
        panel.hidden = !isOpen;
        panel.setAttribute('aria-hidden', String(!isOpen));
        button.setAttribute('aria-expanded', String(isOpen));
        if (isOpen) {
            input.focus();
            scrollToLatest();
        } else {
            button.focus();
        }
    };

    button.addEventListener('click', () => setOpen(panel.hidden));
    closeButton.addEventListener('click', () => setOpen(false));

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const content = input.value.trim();
        if (!content || pending) return;

        appendMessage({ role: 'user', content });
        input.value = '';
        setPending(true);
        const typing = appendMessage({ role: 'assistant', content: 'Typing…', typing: true });
        scrollToLatest();

        let reply = fallbackError;
        const controller = new AbortController();
        const timer = window.setTimeout(() => controller.abort(), 40000);

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                body: JSON.stringify({ message: content }),
                signal: controller.signal,
            });
            const data = await response.json().catch(() => null);
            if (data && typeof data.message === 'string' && data.message) reply = data.message;
        } catch (error) {
        } finally {
            window.clearTimeout(timer);
        }

        typing.remove();
        appendMessage({ role: 'assistant', content: reply });
        setPending(false);
        scrollToLatest();
        input.focus();
    });
})();
