// Time-slot picker for the booking screens. It shows every slot for the chosen date as a button:
// open, last spot, full or already passed. It reads the date from the form it sits in.

const PERIODS = ['Morning', 'Afternoon', 'Evening'];
const NOTES = { open: 'Open', few: 'Last spot', full: 'Full', past: 'Passed' };

function initPicker(picker) {
    const form = picker.closest('form');
    const dateInput = form?.querySelector('[name=booking_date]');
    const hidden = picker.querySelector('input[type=hidden]');
    const status = picker.querySelector('[data-slot-status]');
    const groups = picker.querySelector('[data-slot-groups]');
    let ticket = 0;

    if (!form || !dateInput) return;

    const say = (text) => {
        status.textContent = text;
        status.hidden = !text;
    };

    function select(value, silent = false) {
        hidden.value = value;

        groups.querySelectorAll('.slot-btn').forEach((button) => {
            const on = button.dataset.value === value;
            button.classList.toggle('is-selected', on);
            button.setAttribute('aria-pressed', on ? 'true' : 'false');
        });

        if (!silent) hidden.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function render(slots, keep) {
        groups.innerHTML = '';

        PERIODS.forEach((period) => {
            const list = slots.filter((slot) => slot.period === period);
            if (!list.length) return;

            const section = document.createElement('div');
            section.className = 'slot-group';

            const label = document.createElement('div');
            label.className = 'time-period-label';
            label.textContent = period;

            const grid = document.createElement('div');
            grid.className = 'slot-grid';

            list.forEach((slot) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = `slot-btn is-${slot.state}`;
                button.dataset.value = slot.value;
                button.disabled = slot.state === 'full' || slot.state === 'past';
                button.setAttribute('aria-label', `${slot.label}, ${NOTES[slot.state]}`);

                const time = document.createElement('span');
                time.className = 'slot-time';
                time.textContent = slot.label;

                const note = document.createElement('small');
                note.className = 'slot-note';
                note.textContent = NOTES[slot.state];

                button.append(time, note);
                grid.append(button);
            });

            section.append(label, grid);
            groups.append(section);
        });

        if (keep) select(keep, true);
    }

    // Load the slots for the date in the form. "keep" is a time to leave selected if it is still on the list.
    async function load(keep = '') {
        const mine = ++ticket;
        const date = dateInput.value;
        const serviceId = form.dataset.serviceId;

        groups.innerHTML = '';

        if (!date || !serviceId) {
            say('Choose a date to see the available times.');
            return;
        }

        say('Checking available times…');

        const query = new URLSearchParams({ date, service_id: serviceId });
        if (form.dataset.booking) query.set('booking', form.dataset.booking);

        try {
            const response = await fetch(`${picker.dataset.url}?${query}`, { headers: { Accept: 'application/json' } });
            const data = await response.json();

            if (mine !== ticket) return;

            if (!data.success) {
                say(data.message || 'Could not load the times. Please choose the date again.');
                return;
            }

            if (data.closed) {
                say('The salon is closed on this day. Please choose another date.');
                return;
            }

            render(data.slots, keep);

            const anyFree = data.slots.some((slot) => slot.state === 'open' || slot.state === 'few');
            say(anyFree ? 'Tap a time to choose it.' : 'Everything is taken on this day. Please choose another date.');
        } catch (error) {
            if (mine === ticket) say('Could not load the times. Please choose the date again.');
        }
    }

    groups.addEventListener('click', (event) => {
        const button = event.target.closest('.slot-btn');
        if (button && !button.disabled) select(button.dataset.value);
    });

    // A new date means the old time may not exist, so clear it and show the new day's slots.
    dateInput.addEventListener('change', () => {
        if (hidden.value) select('');
        load();
    });

    // Used by the booking page to set or clear the time from code: dispatch 'slot:set' with { value }.
    picker.addEventListener('slot:set', (event) => {
        const value = event.detail?.value || '';
        hidden.value = value;
        load(value);
    });

    load(hidden.value);
}

document.querySelectorAll('[data-slot-picker]').forEach(initPicker);
