(() => {
    const root = document.getElementById('answer-desk');
    const list = document.getElementById('answer-desk-list');
    const manualForm = document.getElementById('answer-desk-manual-form');
    const addToggle = document.getElementById('answer-desk-add-toggle');
    const addCancel = document.getElementById('answer-desk-add-cancel');
    if (!root || !list || !manualForm || !addToggle || !addCancel) return;

    const endpoint = root.dataset.endpoint;
    const filters = Array.from(root.querySelectorAll('[data-filter]'));
    let activeFilter = 'pending';

    const element = (tag, className, text) => {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined) node.textContent = text;
        return node;
    };

    const notify = (message, isError = false) => {
        let notice = document.getElementById('answer-desk-notice');
        if (!notice) {
            notice = element('p', 'answer-desk-notice');
            notice.id = 'answer-desk-notice';
            notice.setAttribute('role', 'status');
            root.insertBefore(notice, manualForm);
        }
        notice.textContent = message;
        notice.classList.toggle('is-error', isError);
    };

    const formatDate = (value, includeTime = false) => {
        if (!value) return '—';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return String(value);
        return date.toLocaleString('en-PH', includeTime
            ? { year: 'numeric', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }
            : { year: 'numeric', month: 'short', day: 'numeric' });
    };

    const createButton = (text, className, onClick) => {
        const button = element('button', className, text);
        button.type = 'button';
        button.addEventListener('click', onClick);
        return button;
    };

    const request = async (action, data = {}) => {
        const response = await fetch(endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body: JSON.stringify({ action, ...data }),
        });
        const result = await response.json();
        if (!response.ok || result.status !== 'success') {
            throw new Error(result.message || 'The Answer Desk request failed.');
        }
        return result;
    };

    const makeEditor = (item, action, article) => {
        const form = element('form', 'answer-desk-form answer-desk-inline-form');
        const answerLabel = element('label', '', action === 'answer' ? 'Answer' : 'Edit answer');
        const answer = element('textarea');
        answer.name = 'answer';
        answer.maxLength = 1500;
        answer.rows = 4;
        answer.required = true;
        answer.value = action === 'edit_answer' ? item.answer || '' : '';
        answerLabel.append(answer);

        const keywordLabel = element('label', '', 'Keywords or aliases');
        const keywords = element('input');
        keywords.name = 'keywords';
        keywords.type = 'text';
        keywords.maxLength = 500;
        keywords.value = item.keywords || '';
        keywordLabel.append(keywords);

        const reviewLabel = element('label', '', 'Review by');
        const reviewBy = element('input');
        reviewBy.name = 'review_by';
        reviewBy.type = 'date';
        reviewBy.value = item.review_by || '';
        reviewLabel.append(reviewBy);

        const row = element('div', 'answer-desk-form-row');
        row.append(keywordLabel, reviewLabel);
        const actions = element('div', 'answer-desk-form-actions');
        const save = element('button', 'answer-desk-primary', action === 'answer' ? 'Save answer' : 'Save changes');
        save.type = 'submit';
        const cancel = createButton('Cancel', 'answer-desk-secondary', () => form.remove());
        actions.append(save, cancel);
        form.append(answerLabel, row, actions);
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            save.disabled = true;
            try {
                await request(action, {
                    id: item.id,
                    answer: answer.value,
                    keywords: keywords.value,
                    review_by: reviewBy.value,
                });
                notify(action === 'answer' ? 'Answer saved.' : 'Answer updated.');
                await loadItems();
            } catch (error) {
                notify(error.message, true);
                save.disabled = false;
            }
        });
        article.append(form);
        answer.focus();
    };

    const appendFlags = (article, flags) => {
        const unresolved = (flags || []).filter((flag) => !flag.resolved);
        if (!unresolved.length) return;
        const section = element('section', 'answer-desk-flags');
        section.append(element('h3', '', 'Resident feedback'));
        unresolved.forEach((flag) => {
            const row = element('div', 'answer-desk-flag');
            const details = element('p', '', `${flag.reason || 'No comment'} — ${flag.user_name || 'Resident'} · ${formatDate(flag.created_at, true)}`);
            const resolve = createButton('Resolve flag', 'answer-desk-secondary', async () => {
                resolve.disabled = true;
                try {
                    await request('resolve_flag', { id: Number(article.dataset.id), flag_id: Number(flag.id) });
                    notify('Feedback flag resolved.');
                    await loadItems();
                } catch (error) {
                    notify(error.message, true);
                    resolve.disabled = false;
                }
            });
            row.append(details, resolve);
            section.append(row);
        });
        article.append(section);
    };

    const renderItem = (item) => {
        const article = element('article', 'answer-desk-item');
        article.dataset.id = String(item.id);
        article.classList.add(item.is_active ? `is-${item.status}` : 'is-retracted');
        const heading = element('div', 'answer-desk-item-heading');
        const title = element('h2', '', item.question || '(No question text)');
        const state = element('span', `answer-desk-state state-${item.is_active ? item.status : 'retracted'}`,
            item.is_active ? item.status.replace('_', ' ') : 'retracted');
        heading.append(title, state);
        const meta = element('p', 'answer-desk-meta',
            `${item.asked_count} ${item.asked_count === 1 ? 'resident ask' : 'resident asks'} · First asked ${formatDate(item.created_at)}`);
        article.append(heading, meta);

        const dateParts = new Intl.DateTimeFormat('en', {
            timeZone: 'Asia/Manila',
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
        }).formatToParts(new Date());
        const todayString = `${dateParts.find((part) => part.type === 'year').value}-${dateParts.find((part) => part.type === 'month').value}-${dateParts.find((part) => part.type === 'day').value}`;
        if (item.review_by && item.review_by < todayString) {
            article.append(element('span', 'answer-desk-review-badge', 'Needs review'));
        }
        if (item.answer) {
            article.append(element('h3', 'answer-desk-subheading', 'Approved answer'));
            article.append(element('p', 'answer-desk-answer', item.answer));
        }
        if (item.keywords) {
            article.append(element('p', 'answer-desk-keywords', `Keywords: ${item.keywords}`));
        }
        if (item.review_by) {
            article.append(element('p', 'answer-desk-audit', `Review by ${formatDate(item.review_by)}`));
        }
        const audit = [];
        if (item.answered_by_name) audit.push(`Answered by ${item.answered_by_name}${item.answered_at ? ` on ${formatDate(item.answered_at, true)}` : ''}`);
        if (item.updated_by_name) audit.push(`Last edited by ${item.updated_by_name} on ${formatDate(item.updated_at, true)}`);
        if (audit.length) article.append(element('p', 'answer-desk-audit', audit.join(' · ')));
        if (item.flag_count) article.append(element('p', 'answer-desk-flag-count', `${item.flag_count} resident ${item.flag_count === 1 ? 'report' : 'reports'}`));

        const actions = element('div', 'answer-desk-actions');
        if (item.is_active && item.status === 'pending') {
            actions.append(createButton('Answer', 'answer-desk-primary', () => makeEditor(item, 'answer', article)));
        } else if (item.is_active && item.status === 'answered') {
            actions.append(createButton('Edit answer', 'answer-desk-secondary', () => makeEditor(item, 'edit_answer', article)));
            actions.append(createButton('Retract', 'answer-desk-secondary', async () => updateAction(item.id, 'retract', 'FAQ retracted from Kaya.')));
        }
        if (item.status === 'answered') {
            actions.append(createButton('Return to pending', 'answer-desk-secondary', async () => updateAction(item.id, 'return_pending', 'Question returned to pending.')));
        }
        if (item.status !== 'dismissed') {
            actions.append(createButton('Dismiss', 'answer-desk-secondary', async () => updateAction(item.id, 'dismiss', 'Question dismissed.')));
        }
        if (actions.childElementCount) article.append(actions);
        appendFlags(article, item.flags);
        return article;
    };

    const updateAction = async (id, action, successMessage) => {
        try {
            await request(action, { id });
            notify(successMessage);
            await loadItems();
        } catch (error) {
            notify(error.message, true);
        }
    };

    async function loadItems() {
        list.replaceChildren(element('p', 'answer-desk-message', 'Loading questions…'));
        try {
            const response = await fetch(`${endpoint}?action=list&filter=${encodeURIComponent(activeFilter)}`, {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
            });
            const result = await response.json();
            if (!response.ok || result.status !== 'success') {
                throw new Error(result.message || 'Unable to load questions.');
            }
            list.replaceChildren();
            if (!Array.isArray(result.data) || result.data.length === 0) {
                list.append(element('p', 'answer-desk-message', 'No questions in this view. Kaya is ready for the next one.'));
                return;
            }
            result.data.forEach((item) => list.append(renderItem(item)));
        } catch (error) {
            list.replaceChildren(element('p', 'answer-desk-message is-error', error.message));
        }
    }

    filters.forEach((filter) => {
        filter.addEventListener('click', () => {
            activeFilter = filter.dataset.filter;
            filters.forEach((button) => button.classList.toggle('is-selected', button === filter));
            loadItems();
        });
    });

    addToggle.addEventListener('click', () => {
        manualForm.hidden = !manualForm.hidden;
        if (!manualForm.hidden) manualForm.querySelector('[name="question"]').focus();
    });
    addCancel.addEventListener('click', () => {
        manualForm.reset();
        manualForm.hidden = true;
    });
    manualForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const submit = manualForm.querySelector('[type="submit"]');
        const fields = new FormData(manualForm);
        submit.disabled = true;
        try {
            await request('manual_add', {
                question: fields.get('question'),
                answer: fields.get('answer'),
                keywords: fields.get('keywords'),
                review_by: fields.get('review_by'),
            });
            manualForm.reset();
            manualForm.hidden = true;
            activeFilter = 'answered';
            filters.forEach((button) => button.classList.toggle('is-selected', button.dataset.filter === activeFilter));
            notify('Manual FAQ added.');
            await loadItems();
        } catch (error) {
            notify(error.message, true);
        } finally {
            submit.disabled = false;
        }
    });

    loadItems();
})();
