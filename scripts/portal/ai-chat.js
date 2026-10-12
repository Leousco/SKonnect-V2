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

        const text = document.createElement('p');
        text.textContent = content;
        message.append(text);
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
