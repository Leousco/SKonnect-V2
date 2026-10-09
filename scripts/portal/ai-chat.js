(() => {
    const button = document.getElementById('ai-chat-button');
    const panel = document.getElementById('ai-chat-panel');
    const closeButton = document.getElementById('ai-chat-close');
    const input = document.getElementById('ai-chat-input');
    const form = document.getElementById('ai-chat-form');
    const messagesElement = document.getElementById('ai-chat-messages');

    if (!button || !panel || !closeButton || !input || !form || !messagesElement) return;

    const greeting = 'Hi! I’m your SK Assistant. How can I help you today?';
    const placeholderReply = 'This is a temporary placeholder response. The SKonnect AI backend has not been connected yet.';
    const messages = [{ role: 'assistant', content: greeting }];

    const appendMessage = ({ role, content }) => {
        const message = document.createElement('div');
        message.className = `ai-chat-message ai-chat-message-${role}`;

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
    };

    const scrollToLatest = () => {
        messagesElement.scrollTop = messagesElement.scrollHeight;
    };

    appendMessage(messages[0]);

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

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        const content = input.value.trim();
        if (!content) return;

        const userMessage = { role: 'user', content };
        messages.push(userMessage);
        appendMessage(userMessage);
        input.value = '';
        scrollToLatest();

        window.setTimeout(() => {
            const assistantMessage = { role: 'assistant', content: placeholderReply };
            messages.push(assistantMessage);
            appendMessage(assistantMessage);
            scrollToLatest();
        }, 500);
    });
})();
