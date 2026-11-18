<link rel="stylesheet" href="../../styles/portal/ai-chat.css">

<div class="ai-chat" id="ai-chat" data-endpoint="../../backend/routes/ai.php">
    <button class="ai-chat-button" id="ai-chat-button" type="button" aria-label="Open chat with Kaya" aria-expanded="false" aria-controls="ai-chat-panel">
        <img src="../../assets/img/kaya-icon.svg" alt="" aria-hidden="true">
        <span class="ai-chat-launch-label">Ask Kaya!</span>
        <span class="ai-chat-open-label">
            <strong id="ai-chat-title">Kaya</strong>
            <small>Your SKonnect guide</small>
        </span>
    </button>

    <section class="ai-chat-panel" id="ai-chat-panel" aria-labelledby="ai-chat-title" aria-hidden="true" hidden>
        <header class="ai-chat-header">
            <button class="ai-chat-close" id="ai-chat-close" type="button" aria-label="Close chat">
                <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m18 6-12 12M6 6l12 12" /></svg>
            </button>
        </header>

        <div class="ai-chat-messages" id="ai-chat-messages" role="log" aria-live="polite" aria-label="Conversation">
            <section class="ai-chat-welcome" id="ai-chat-welcome" aria-labelledby="ai-chat-welcome-title">
                <p class="ai-chat-eyebrow">YOUR COMMUNITY, MADE EASIER</p>
                <h2 id="ai-chat-welcome-title">Hi, I’m Kaya.</h2>
                <p class="ai-chat-welcome-copy">I can help with announcements, available services, and tracking your requests. Pick a question below or type your own.</p>
                <div class="ai-chat-prompts" aria-label="Suggested questions">
                    <button class="ai-chat-prompt" type="button" data-prompt="How do I apply for a service?">
                        <span class="ai-chat-prompt-icon" aria-hidden="true">01</span>
                        <span>How do I apply for a service?</span>
                        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
                    </button>
                    <button class="ai-chat-prompt" type="button" data-prompt="What are the latest announcements?">
                        <span class="ai-chat-prompt-icon" aria-hidden="true">02</span>
                        <span>What are the latest announcements?</span>
                        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
                    </button>
                    <button class="ai-chat-prompt" type="button" data-prompt="What services can I apply for?">
                        <span class="ai-chat-prompt-icon" aria-hidden="true">03</span>
                        <span>What services can I apply for?</span>
                        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
                    </button>
                    <button class="ai-chat-prompt" type="button" data-prompt="Where can I check my request status?">
                        <span class="ai-chat-prompt-icon" aria-hidden="true">04</span>
                        <span>Where can I check my request status?</span>
                        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
                    </button>
                </div>
            </section>
        </div>

        <aside class="ai-chat-notice" role="note">
            <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9" /><path d="M12 11v5m0-8h.01" /></svg>
            <span>Kaya answers using information from SKonnect. For official decisions, confirm with your SK office.</span>
        </aside>

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
