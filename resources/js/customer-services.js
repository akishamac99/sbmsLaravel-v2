// Behaviour for the customer services page: filters, booking modals, time picker, live staff list, cart totals.
// It talks to the Blade view through data-* attributes, so nothing here needs to be a global function.

const root = document.getElementById('services-page');

if (root) {
    const availabilityUrl = root.dataset.availabilityUrl;
    const updateUrl = root.dataset.updateUrl;      // contains __ID__
    const removeUrl = root.dataset.removeUrl;      // contains __ID__
    const downpaymentPercent = Number(root.dataset.downpaymentPercent || 0);
    const homeFee = root.dataset.homeFee || '500';
    const cartItems = JSON.parse(document.getElementById('cart-data')?.textContent || '[]');

    let activeCategory = 'all';

    /* ---------------------------------------------------------------- modals */

    const openModal = (id) => document.getElementById(id)?.classList.add('open');
    const closeModals = () => document.querySelectorAll('.modal.open').forEach((m) => m.classList.remove('open'));

    /* ---------------------------------------------------------------- catalog filter */

    function filterServices() {
        const search = (document.getElementById('serviceSearch')?.value || '').toLowerCase().trim();
        let visibleTotal = 0;

        document.querySelectorAll('.service-category-group').forEach((group) => {
            let visibleInGroup = 0;

            group.querySelectorAll('.service-catalog-card').forEach((card) => {
                const matchesCategory = activeCategory === 'all' || card.dataset.category === activeCategory;
                const matchesSearch = !search || (card.dataset.name || '').includes(search);
                const visible = matchesCategory && matchesSearch;

                card.style.display = visible ? '' : 'none';
                if (visible) visibleInGroup++;
            });

            group.style.display = visibleInGroup > 0 ? '' : 'none';
            visibleTotal += visibleInGroup;
        });

        const empty = document.getElementById('serviceEmptyState');
        if (empty) empty.style.display = visibleTotal === 0 ? 'block' : 'none';
    }

    /* ---------------------------------------------------------------- time picker (see slot-picker.js) */

    // Choose a time in a form's slot picker ('' clears it). The picker loads the slots for the chosen date.
    function setTime(picker, value) {
        picker.dispatchEvent(new CustomEvent('slot:set', { detail: { value } }));
    }

    /* ---------------------------------------------------------------- live staff list */

    function setStaffMessage(form, message, hint = null) {
        const select = form.querySelector('[name=staff_id]');
        select.innerHTML = '';
        select.add(new Option(message, ''));

        const hintEl = form.querySelector('[data-staff-hint]');
        if (hintEl && hint !== null) hintEl.textContent = hint;
    }

    // Ask the server who is qualified and free for this service, date and time.
    async function refreshStaff(form, selectedStaffId = '') {
        const date = form.querySelector('[name=booking_date]').value;
        const time = form.querySelector('.slot-picker input[type=hidden]').value;
        const hintEl = form.querySelector('[data-staff-hint]');

        if (!date || !time) {
            setStaffMessage(form, 'Choose date and time first', 'Available and qualified staff will appear after selecting a date and time.');
            return;
        }

        // If the customer changes the date again before the answer arrives, ignore the old answer.
        const ticket = (form._staffTicket = (form._staffTicket || 0) + 1);
        if (hintEl) hintEl.textContent = 'Checking availability...';

        const query = new URLSearchParams({ date, time, service_id: form.dataset.serviceId || '' });

        try {
            const response = await fetch(`${availabilityUrl}?${query}`, { headers: { Accept: 'application/json' } });
            const data = await response.json();

            if (ticket !== form._staffTicket) return;

            const staff = data.success ? data.staff : [];

            if (staff.length === 0) {
                setStaffMessage(form, 'No staff available for this slot', 'Please choose a different date, time, or service.');
                return;
            }

            const select = form.querySelector('[name=staff_id]');
            select.innerHTML = '';
            select.add(new Option('Select Staff', ''));

            staff.forEach((s) => {
                const option = new Option(`${s.fullname} (${s.specialization})`, s.staffID);
                option.selected = String(s.staffID) === String(selectedStaffId);
                select.add(option);
            });

            if (hintEl) hintEl.textContent = `${staff.length} qualified staff available for this slot.`;
        } catch (error) {
            if (ticket !== form._staffTicket) return;
            setStaffMessage(form, 'Could not load staff', 'Could not check live availability. Please choose date and time again.');
        }
    }

    /* ---------------------------------------------------------------- opening the modals */

    function openBooking(button) {
        const form = document.querySelector('#bookingModal form');
        const d = button.dataset;

        form.querySelector('[name=service_id]').value = d.serviceId;
        form.dataset.serviceId = d.serviceId;
        form.querySelector('#serviceName').value = `${d.name} - PHP ${d.price} (${d.duration})`;
        form.querySelector('[name=booking_date]').value = '';
        setTime(form.querySelector('.slot-picker'), '');
        setStaffMessage(form, 'Choose date and time first', 'Available and qualified staff will appear after selecting a date and time.');

        const warning = document.getElementById('homeServiceWarning');
        warning.style.display = d.home === '1' ? 'block' : 'none';
        document.getElementById('homeServiceMsg').textContent =
            `This makeup service is available as home service. Additional PHP ${homeFee} fee applies for home service.`;

        openModal('bookingModal');
    }

    function openEdit(button) {
        const item = cartItems.find((entry) => String(entry.cartID) === String(button.dataset.cartId));
        if (!item) return;

        const form = document.querySelector('#editCartModal form');

        form.action = updateUrl.replace('__ID__', item.cartID);
        form.dataset.serviceId = item.serviceID;
        form.querySelector('#editServiceName').value = `${item.serviceName} - PHP ${item.servicePrice}`;
        form.querySelector('[name=booking_date]').value = item.bookingDate || '';
        setTime(form.querySelector('.slot-picker'), item.bookingTime || '');
        form.querySelector('[name=special_request]').value = item.special_request || '';
        form.querySelector('[name=notes]').value = item.notes || '';

        refreshStaff(form, item.staffID || '');
        openModal('editCartModal');
    }

    function openDelete(button) {
        const form = document.querySelector('#deleteCartModal form');

        form.action = removeUrl.replace('__ID__', button.dataset.cartId);
        document.getElementById('deleteServiceName').textContent = button.dataset.name;
        openModal('deleteCartModal');
    }

    /* ---------------------------------------------------------------- cart totals */

    function updateSelectedTotals() {
        let total = 0;

        document.querySelectorAll('.checkout-item-check:checked').forEach((box) => {
            total += Number(box.dataset.price || 0);
        });

        const fmt = (n) => n.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });

        const badge = document.getElementById('cartSelectedTotal');
        if (badge) badge.textContent = '₱' + fmt(total);

        const down = document.getElementById('selectedDownpayment');
        if (down) down.textContent = fmt(total * (downpaymentPercent / 100));
    }

    /* ---------------------------------------------------------------- events */

    document.addEventListener('click', (event) => {
        const target = event.target;

        const chip = target.closest('.service-filter-chip');
        if (chip) {
            activeCategory = String(chip.dataset.category);
            document.querySelectorAll('.service-filter-chip').forEach((c) => c.classList.remove('active'));
            chip.classList.add('active');
            filterServices();
            return;
        }

        const openBtn = target.closest('[data-open-booking]');
        if (openBtn) return openBooking(openBtn);

        const editBtn = target.closest('[data-edit-cart]');
        if (editBtn) return openEdit(editBtn);

        const deleteBtn = target.closest('[data-delete-cart]');
        if (deleteBtn) return openDelete(deleteBtn);

        if (target.closest('[data-close-modal]') || target.classList.contains('modal') || target.classList.contains('modal-overlay')) {
            closeModals();
            return;
        }

        const scrollBtn = target.closest('[data-scroll-to]');
        if (scrollBtn) {
            document.querySelector(scrollBtn.dataset.scrollTo)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            return;
        }

    });

    document.addEventListener('input', (event) => {
        if (event.target.id === 'serviceSearch') filterServices();
    });

    document.addEventListener('change', (event) => {
        const field = event.target;

        if (field.matches('[data-schedule-field]')) {
            const form = field.closest('form[data-staff-form]');
            // When editing, keep the person already chosen if they are still free at the new slot.
            if (form) refreshStaff(form, form.querySelector('[name=staff_id]').value);
        }

        if (field.matches('.checkout-item-check')) updateSelectedTotals();
    });

    updateSelectedTotals();
}
