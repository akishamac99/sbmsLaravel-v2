// Payment screenshot picker (checkout and "upload payment" pages).
//  - preview, drag and drop, type check
//  - phone photos larger than ~1.5 MB are resized in the browser first, so they upload
//    reliably even when the server's upload limit is small
//  - the form will not submit without a screenshot, and the button shows progress

const MAX_BYTES = 10 * 1024 * 1024;
const TARGET_BYTES = 1.5 * 1024 * 1024;
const MAX_SIDE = 1800;
const ALLOWED = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

function formatSize(bytes) {
    return bytes > 1024 * 1024 ? (bytes / 1024 / 1024).toFixed(1) + ' MB' : (bytes / 1024).toFixed(0) + ' KB';
}

function shrink(file) {
    if (file.size <= TARGET_BYTES || file.type === 'image/gif') return Promise.resolve(file);

    return new Promise((resolve) => {
        const img = new Image();
        const url = URL.createObjectURL(file);
        img.onload = () => {
            const scale = Math.min(1, MAX_SIDE / Math.max(img.width, img.height));
            const canvas = document.createElement('canvas');
            canvas.width = Math.round(img.width * scale);
            canvas.height = Math.round(img.height * scale);
            canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
            URL.revokeObjectURL(url);
            canvas.toBlob((blob) => {
                if (!blob || blob.size >= file.size) return resolve(file);
                const name = file.name.replace(/\.[^.]+$/, '') + '.jpg';
                resolve(new File([blob], name, { type: 'image/jpeg', lastModified: Date.now() }));
            }, 'image/jpeg', 0.85);
        };
        img.onerror = () => { URL.revokeObjectURL(url); resolve(file); };
        img.src = url;
    });
}

document.querySelectorAll('[data-proof-upload]').forEach((box) => {
    const form = box.closest('form');
    const zone = box.querySelector('[data-drop-zone]');
    const input = box.querySelector('[data-proof-input]');
    const inner = box.querySelector('[data-drop-inner]');
    const wrap = box.querySelector('[data-preview-wrap]');
    const image = box.querySelector('[data-preview-img]');
    const nameEl = box.querySelector('[data-file-name]');
    const sizeEl = box.querySelector('[data-file-size]');
    const errorEl = box.querySelector('[data-upload-error]');
    let busy = false;

    const showError = (message) => {
        errorEl.textContent = message;
        errorEl.hidden = !message;
    };

    function clearUpload() {
        input.value = '';
        image.src = '';
        inner.hidden = false;
        wrap.hidden = true;
    }

    async function useFile(file) {
        showError('');

        if (!ALLOWED.includes(file.type)) {
            clearUpload();
            showError('Please choose a JPG, PNG, GIF or WEBP image.');
            return;
        }

        if (file.size > MAX_BYTES) {
            clearUpload();
            showError('That image is larger than 10 MB. Please take a screenshot instead of a photo.');
            return;
        }

        busy = true;
        const ready = await shrink(file);
        busy = false;

        // Put the (possibly resized) file back into the input so the form sends it.
        const transfer = new DataTransfer();
        transfer.items.add(ready);
        input.files = transfer.files;

        image.src = URL.createObjectURL(ready);
        nameEl.textContent = ready.name;
        sizeEl.textContent = formatSize(ready.size);
        inner.hidden = true;
        wrap.hidden = false;
    }

    zone.addEventListener('click', (event) => {
        if (event.target.closest('[data-clear-upload]')) return;
        input.click();
    });
    zone.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); input.click(); }
    });
    input.addEventListener('change', () => {
        if (input.files[0]) useFile(input.files[0]);
    });
    box.addEventListener('click', (event) => {
        if (event.target.closest('[data-clear-upload]')) {
            event.stopPropagation();
            clearUpload();
            showError('');
        }
    });
    zone.addEventListener('dragover', (event) => { event.preventDefault(); zone.classList.add('drag-over'); });
    zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
    zone.addEventListener('drop', (event) => {
        event.preventDefault();
        zone.classList.remove('drag-over');
        if (event.dataTransfer.files.length) useFile(event.dataTransfer.files[0]);
    });

    if (form) {
        // The native "required" bubble cannot point at a hidden input, so check it ourselves.
        input.addEventListener('invalid', (event) => {
            event.preventDefault();
            showError('Please attach your payment screenshot.');
            zone.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
        form.addEventListener('submit', (event) => {
            if (busy) { event.preventDefault(); return; }
            if (input.required && !input.files.length) {
                event.preventDefault();
                showError('Please attach your payment screenshot.');
                zone.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }
            const button = form.querySelector('[data-submit]');
            if (button) {
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading…';
            }
        });
    }
});

// Show the matching account details for the chosen payment method.
document.querySelectorAll('[data-pay-method]').forEach((radio) => {
    const sync = () => {
        const chosen = document.querySelector('[data-pay-method]:checked')?.value;
        document.querySelectorAll('[data-pay-details]').forEach((panel) => {
            panel.hidden = panel.dataset.payDetails !== chosen;
        });
        // Paying online needs no screenshot.
        const online = chosen === 'paymongo';
        document.querySelectorAll('[data-proof-section]').forEach((section) => { section.hidden = online; });
        document.querySelectorAll('[data-proof-input]').forEach((input) => { input.required = !online; });
    };
    radio.addEventListener('change', sync);
    sync();
});
