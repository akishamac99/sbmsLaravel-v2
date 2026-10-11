// Feedback page: clickable star ratings, and a reminder if a rating was left empty.

const page = document.getElementById('feedback-page');

if (page) {
    page.addEventListener('click', (event) => {
        const star = event.target.closest('.star');
        if (!star) return;

        const value = Number(star.dataset.value);
        document.getElementById(star.dataset.target).value = value;

        star.parentElement.querySelectorAll('.star').forEach((s) => {
            s.classList.toggle('active', Number(s.dataset.value) <= value);
        });
    });

    page.addEventListener('submit', (event) => {
        const form = event.target.closest('.feedback-form');
        if (!form) return;

        const missing = [...form.querySelectorAll('input[type=hidden][name$=_rating]')].some((input) => !input.value);

        if (missing) {
            event.preventDefault();
            window.alert('Please rate both the service and the staff before submitting.');
        }
    });
}
