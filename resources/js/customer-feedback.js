// Feedback page: shows a word for the chosen stars, counts comment characters, and
// reminds (inline, not with a popup) when a rating or comment is missing.

const page = document.getElementById('feedback-page');

if (page) {
    const WORDS = ['', 'Poor', 'Fair', 'Good', 'Very good', 'Excellent'];

    page.addEventListener('change', (event) => {
        const input = event.target.closest('.star-rating input');
        if (!input) return;
        const word = input.closest('.fb-rate-row').querySelector('.fb-rate-word');
        if (word) word.textContent = WORDS[Number(input.value)] || '';
        const error = input.closest('.feedback-form').querySelector('.fb-error');
        if (error) error.hidden = true;
    });

    page.addEventListener('input', (event) => {
        const area = event.target.closest('textarea[name=comment]');
        if (!area) return;
        const counter = area.closest('.form-group').querySelector('.fb-count');
        if (counter) counter.textContent = `${area.value.length} / ${area.maxLength}`;
    });

    page.addEventListener('submit', (event) => {
        const form = event.target.closest('.feedback-form');
        if (!form) return;

        const rated = (name) => form.querySelector(`input[name=${name}]:checked`);
        const error = form.querySelector('.fb-error');

        if (!rated('service_rating') || !rated('staff_rating')) {
            event.preventDefault();
            error.textContent = 'Please rate both the service and the staff before submitting.';
            error.hidden = false;
            (form.querySelector('.star-rating input:not(:checked)') || form).focus?.();
            return;
        }

        const button = form.querySelector('button[type=submit]');
        if (button) { button.disabled = true; button.textContent = 'Sending...'; }
    });
}
