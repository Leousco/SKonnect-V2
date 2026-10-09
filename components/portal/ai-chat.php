<link rel="stylesheet" href="../../styles/portal/ai-chat.css">

<div class="ai-chat" id="ai-chat">
    <button class="ai-chat-button" id="ai-chat-button" type="button" aria-label="Open SK Assistant" aria-expanded="false" aria-controls="ai-chat-panel">
        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 3.5 13.8 9l5.7 1.8-5.7 1.8L12 18.5l-1.8-5.9L4.5 11l5.7-2 1.8-5.5Z" />
            <path d="m19 15 .9 2.1L22 18l-2.1.9L19 21l-.9-2.1L16 18l2.1-.9L19 15Z" />
        </svg>
        <span>Ask SK</span>
    </button>

    <section class="ai-chat-panel" id="ai-chat-panel" aria-labelledby="ai-chat-title" aria-hidden="true" hidden>
        <header class="ai-chat-header">
            <div class="ai-chat-heading">
                <span class="ai-chat-avatar" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 3.5 13.8 9l5.7 1.8-5.7 1.8L12 18.5l-1.8-5.9L4.5 11l5.7-2 1.8-5.5Z" />
                    </svg>
                </span>
                <span>
                    <strong id="ai-chat-title">SK Assistant</strong>
                    <small>Resident Portal</small>
                </span>
            </div>
            <button class="ai-chat-close" id="ai-chat-close" type="button" aria-label="Close chat">
                <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m18 6-12 12M6 6l12 12" /></svg>
            </button>
        </header>

        <div class="ai-chat-messages" id="ai-chat-messages" role="log" aria-live="polite" aria-label="Conversation">
        </div>

        <form class="ai-chat-compose" id="ai-chat-form">
            <label class="sr-only" for="ai-chat-input">Your message</label>
            <input class="ai-chat-input" id="ai-chat-input" name="message" type="text" placeholder="Type a message..." autocomplete="off">
            <button class="ai-chat-send" type="submit" aria-label="Send message">
                <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 2-7 20-4-9-9-4 20-7ZM22 2 11 13" /></svg>
            </button>
        </form>
    </section>
</div>

<script src="../../scripts/portal/ai-chat.js"></script>
