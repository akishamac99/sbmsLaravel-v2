// Shared behaviour for the portal list pages:
//  - [data-auto-submit] controls submit their form as soon as they change (filters reload instantly)
//  - [data-modal-form] modals are reused for "Add" and "Edit": buttons with data-open-form fill them
//  - a modal whose form failed validation re-opens with the user's input
//  - image inputs show a preview, minute inputs show "1 hr 30 min"

const searchTimers = new WeakMap();

document.addEventListener('change', (event) => {
    const control = event.target.closest('[data-auto-submit]');
    if (control && control.form) control.form.requestSubmit();
});

document.addEventListener('input', (event) => {
    const control = event.target.closest('[data-auto-search]');
    if (!control || !control.form) return;
    clearTimeout(searchTimers.get(control));
    searchTimers.set(control, setTimeout(() => control.form.requestSubmit(), 450));
});

function setField(form, name, value) {
    const fields = form.querySelectorAll(`[name="${CSS.escape(name)}"]`);
    fields.forEach((input) => {
        // A hidden "0" paired with a checkbox keeps its fixed value.
        if (input.type === 'hidden' && fields.length > 1) return;
        if (input.type === 'checkbox') {
            input.checked = value === true || value === 1 || value === '1';
        } else if (input.type === 'radio') {
            input.checked = String(input.value) === String(value);
        } else if (input.type !== 'file') {
            input.value = value ?? '';
        }
        input.dispatchEvent(new Event('input', { bubbles: true }));
    });
}

function openForm(trigger) {
    const modal = document.getElementById(trigger.dataset.openForm);
    if (!modal) return;
    const form = modal.querySelector('form');
    const data = trigger.dataset.record ? JSON.parse(trigger.dataset.record) : {};
    const isEdit = Boolean(trigger.dataset.action);

    form.reset();
    form.querySelectorAll('.field-error, .alert-inline[data-server-error]').forEach((el) => el.remove());
    form.action = trigger.dataset.action || form.dataset.createAction;
    form.querySelector('input[name="_method"]').value = isEdit ? (form.dataset.editMethod || 'PUT') : 'POST';
    form.querySelector('input[name="_edit_action"]').value = isEdit ? form.action : '';
    modal.querySelectorAll('[data-title]').forEach((el) => { el.textContent = isEdit ? el.dataset.titleEdit : el.dataset.title; });
    modal.querySelectorAll('[data-only-create]').forEach((el) => { el.hidden = isEdit; el.querySelectorAll('input,select,textarea').forEach((i) => { i.disabled = isEdit; }); });
    modal.querySelectorAll('[data-only-edit]').forEach((el) => { el.hidden = !isEdit; el.querySelectorAll('input,select,textarea').forEach((i) => { i.disabled = !isEdit; }); });

    Object.entries(data).forEach(([name, value]) => setField(form, name, value));
    form.querySelectorAll('[data-image-preview]').forEach((img) => {
        const url = data[img.dataset.imagePreview] || '';
        img.src = url;
        img.hidden = !url;
        if (img.nextElementSibling) img.nextElementSibling.hidden = Boolean(url);
    });

    window.openModal(modal.id);
    const first = form.querySelector('input:not([type=hidden]):not([disabled]), select:not([disabled]), textarea');
    if (first) setTimeout(() => first.focus(), 60);
}

document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-open-form]');
    if (!trigger) return;
    event.preventDefault();
    openForm(trigger);
});

// Re-open a modal after a failed submit, pointing it back at the record being edited.
document.querySelectorAll('[data-modal-form][data-reopen]').forEach((modal) => {
    const form = modal.querySelector('form');
    const editAction = form.querySelector('input[name="_edit_action"]').value;
    if (editAction) {
        form.action = editAction;
        form.querySelector('input[name="_method"]').value = form.dataset.editMethod || 'PUT';
    }
    const isEdit = Boolean(editAction);
    modal.querySelectorAll('[data-title]').forEach((el) => { el.textContent = isEdit ? el.dataset.titleEdit : el.dataset.title; });
    modal.querySelectorAll('[data-only-create]').forEach((el) => { el.hidden = isEdit; el.querySelectorAll('input,select,textarea').forEach((i) => { i.disabled = isEdit; }); });
    modal.querySelectorAll('[data-only-edit]').forEach((el) => { el.hidden = !isEdit; el.querySelectorAll('input,select,textarea').forEach((i) => { i.disabled = !isEdit; }); });
    window.openModal(modal.id);
});

// Image preview for upload fields.
document.addEventListener('change', (event) => {
    const input = event.target;
    if (input.type !== 'file' || !input.dataset.previewTarget) return;
    const img = document.getElementById(input.dataset.previewTarget);
    const file = input.files && input.files[0];
    if (!img || !file) return;
    img.src = URL.createObjectURL(file);
    img.hidden = false;
    if (img.nextElementSibling) img.nextElementSibling.hidden = true;
});

// "90" minutes -> "1 hr 30 min" helper text.
function describeMinutes(total) {
    const minutes = Number(total);
    if (!minutes || minutes < 0) return '';
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;
    return [h ? `${h} hr` : '', m ? `${m} min` : ''].filter(Boolean).join(' ');
}

document.addEventListener('input', (event) => {
    const input = event.target.closest('[data-minutes]');
    if (!input) return;
    const hint = document.getElementById(input.dataset.minutes);
    if (hint) hint.textContent = describeMinutes(input.value) || 'Enter the length in minutes.';
});
document.querySelectorAll('[data-minutes]').forEach((input) => input.dispatchEvent(new Event('input', { bubbles: true })));
