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

    const endpoint = root.dataset.endpoint || '../../backend/routes/ai.php';
    const greeting = 'Hi! I\u2019m your SK Assistant. How can I help you today?';
    let pending = false;
    let closeTimer = 0;

    const appendInline = (parent, text) => {
        const tokenPattern = /(\[([^\]]+)\]\(([^)]+)\)|`([^`]+)`|\*\*([^*]+)\*\*|__([^_]+)__|\*([^*]+)\*|_([^_]+)_)/g;
        let lastIndex = 0;
        let match;
        while ((match = tokenPattern.exec(text))) {
            parent.append(document.createTextNode(text.slice(lastIndex, match.index)));
            if (match[1] && match[2] !== undefined) {
                let url;
                try { url = new URL(match[3], window.location.href); } catch (error) { url = null; }
                if (url && (url.protocol === 'http:' || url.protocol === 'https:')) {
                    const link = document.createElement('a');
                    link.textContent = match[2];
                    link.href = url.href;
                    link.target = '_blank';
                    link.rel = 'noopener noreferrer';
                    parent.append(link);
                } else {
                    parent.append(document.createTextNode(match[2]));
                }
            } else if (match[4] !== undefined) {
                const code = document.createElement('code');
                code.textContent = match[4];
                parent.append(code);
            } else {
                const strongText = match[5] ?? match[6];
                if (strongText !== undefined) {
                    const strong = document.createElement('strong');
                    strong.textContent = strongText;
                    parent.append(strong);
                } else {
                    const italic = document.createElement('em');
                    italic.textContent = match[7] ?? match[8];
                    parent.append(italic);
                }
            }
            lastIndex = tokenPattern.lastIndex;
        }
        parent.append(document.createTextNode(text.slice(lastIndex)));
    };

    const renderMarkdown = (container, source) => {
        const lines = String(source).replace(/\r\n?/g, '\n').split('\n');
        let index = 0;
        while (index < lines.length) {
            const line = lines[index];
            if (!line.trim()) { index += 1; continue; }

            const heading = line.match(/^\s{0,3}(#{1,4})\s+(.+?)\s*#*\s*$/);
            if (heading) {
                const element = document.createElement(`h${heading[1].length}`);
                appendInline(element, heading[2]);
                container.append(element);
                index += 1;
                continue;
            }

            const listMatch = line.match(/^\s{0,3}([-*+] |\d+[.)] )(.*)$/);
            if (listMatch) {
                const ordered = /^\d/.test(listMatch[1]);
                const list = document.createElement(ordered ? 'ol' : 'ul');
                while (index < lines.length) {
                    const itemMatch = lines[index].match(/^\s{0,3}([-*+] |\d+[.)] )(.*)$/);
                    if (!itemMatch || /^\d/.test(itemMatch[1]) !== ordered) break;
                    const item = document.createElement('li');
                    appendInline(item, itemMatch[2]);
                    list.append(item);
                    index += 1;
                }
                container.append(list);
                continue;
            }

            const paragraph = document.createElement('p');
            while (index < lines.length && lines[index].trim()) {
                if (paragraph.childNodes.length) paragraph.append(document.createElement('br'));
                appendInline(paragraph, lines[index]);
                index += 1;
            }
            container.append(paragraph);
        }
    };

    const isNearBottom = () => messagesElement.scrollHeight - messagesElement.scrollTop - messagesElement.clientHeight < 72;
    const scrollToLatest = () => { messagesElement.scrollTop = messagesElement.scrollHeight; };

    const appendMessage = ({ role, content, typing = false, error = false }) => {
        const follow = isNearBottom();
        const message = document.createElement('div');
        message.className = `ai-chat-message ai-chat-message-${role}`;
        if (typing) message.classList.add('ai-chat-message-typing');
        if (error) message.classList.add('ai-chat-message-error');

        if (role === 'assistant') {
            const avatar = document.createElement('span');
            avatar.className = 'ai-chat-message-avatar';
            avatar.setAttribute('aria-hidden', 'true');
            avatar.textContent = 'SK';
            message.append(avatar);
        }

        const body = document.createElement('div');
        body.className = 'ai-chat-message-content';
        if (typing) {
            body.setAttribute('role', 'status');
            body.setAttribute('aria-label', 'Assistant is responding');
            for (let dot = 0; dot < 3; dot += 1) {
                const indicator = document.createElement('span');
                indicator.className = 'ai-chat-typing-dot';
                indicator.setAttribute('aria-hidden', 'true');
                body.append(indicator);
            }
        } else if (role === 'assistant') {
            renderMarkdown(body, content);
        } else {
            body.textContent = content;
        }
        message.append(body);
        messagesElement.append(message);
        if (follow) requestAnimationFrame(scrollToLatest);
        return message;
    };

    const setPending = (value) => {
        pending = value;
        input.disabled = value;
        sendButton.disabled = value;
        form.setAttribute('aria-busy', String(value));
    };

    const setOpen = (isOpen, restoreFocus = true) => {
        window.clearTimeout(closeTimer);
        if (isOpen) {
            panel.hidden = false;
            panel.inert = false;
            panel.setAttribute('aria-hidden', 'false');
            button.setAttribute('aria-expanded', 'true');
            requestAnimationFrame(() => panel.classList.add('is-open'));
            input.focus();
            requestAnimationFrame(scrollToLatest);
            return;
        }

        if (panel.hidden) return;
        panel.classList.remove('is-open');
        panel.inert = true;
        panel.setAttribute('aria-hidden', 'true');
        button.setAttribute('aria-expanded', 'false');
        if (restoreFocus) button.focus();
        closeTimer = window.setTimeout(() => { panel.hidden = true; }, 190);
    };

    appendMessage({ role: 'assistant', content: greeting });
    button.addEventListener('click', () => setOpen(panel.hidden));
    closeButton.addEventListener('click', () => setOpen(false));
    panel.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            event.preventDefault();
            setOpen(false);
        }
    });
    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
            event.preventDefault();
            form.requestSubmit();
        }
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const content = input.value.trim();
        if (!content || pending) return;

        appendMessage({ role: 'user', content });
        input.value = '';
        setPending(true);
        const typing = appendMessage({ role: 'assistant', content: '', typing: true });
        const controller = new AbortController();
        let timedOut = false;
        const timer = window.setTimeout(() => { timedOut = true; controller.abort(); }, 40000);

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                body: JSON.stringify({ message: content }),
                signal: controller.signal,
            });
            const data = await response.json().catch(() => null);
            if (!response.ok) {
                let message = 'Your message could not be processed. Please try again.';
                if (response.status === 401 || response.status === 403) message = 'Your session may have expired. Please sign in again and retry.';
                else if (response.status === 413) message = 'That message is too long. Please shorten it and try again.';
                else if (response.status === 429) message = 'You have reached the request limit. Please wait a little and try again.';
                else if (response.status >= 500) message = 'The assistant is temporarily unavailable. Please try again.';
                appendMessage({ role: 'assistant', content: message, error: true });
            } else if (data && data.success === false) {
                appendMessage({ role: 'assistant', content: 'The assistant could not answer that message. Please try again.', error: true });
            } else if (data && typeof data.message === 'string' && data.message.trim()) {
                appendMessage({ role: 'assistant', content: data.message });
            } else {
                appendMessage({ role: 'assistant', content: 'The assistant returned an unexpected response. Please try again.', error: true });
            }
        } catch (error) {
            const message = timedOut
                ? 'The assistant took too long to respond. Please try again.'
                : 'Could not connect to the assistant. Check your connection and try again.';
            appendMessage({ role: 'assistant', content: message, error: true });
        } finally {
            window.clearTimeout(timer);
            typing.remove();
            setPending(false);
            if (!panel.hidden && !panel.inert) input.focus();
        }
    });
})();
