// My Bookings page: live search, and an "are you sure?" question before cancelling.

const page = document.getElementById('bookings-page');

if (page) {
    const search = document.getElementById('searchBookings');
    const rows = () => page.querySelectorAll('#bookingsTable tbody tr[data-search]');
    const empty = document.getElementById('bookingsNoMatch');

    search?.addEventListener('input', () => {
        const term = search.value.toLowerCase().trim();
        let shown = 0;

        rows().forEach((row) => {
            const match = !term || row.dataset.search.includes(term);
            row.style.display = match ? '' : 'none';
            if (match) shown++;
        });

        if (empty) empty.style.display = shown === 0 && rows().length > 0 ? 'block' : 'none';
    });

    // Any form marked data-confirm asks first. Cancel the question and nothing is sent.
    page.addEventListener('submit', (event) => {
        const message = event.target.dataset.confirm;

        if (message && !window.confirm(message)) {
            event.preventDefault();
        }
    });
}
