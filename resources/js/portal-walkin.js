// Walk-in check-in: keeps the staff list in sync with the chosen service and time,
// and keeps the summary panel up to date.

const form = document.getElementById('walkinForm');

if (form) {
    const peso = (value) => '₱' + Number(value || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const percent = Number(form.dataset.downpaymentPercent || 0);
    const field = (name) => form.elements.namedItem(name);
    const out = (key, value) => {
        const el = form.querySelector(`[data-out="${key}"]`);
        if (el) el.textContent = value;
    };

    const service = field('service_id');
    const time = field('booking_time');
    const staff = field('staff_id');
    const staffHint = form.querySelector('[data-staff-hint]');
    let preferredStaff = form.dataset.oldStaff || '';
    let requestNo = 0;

    function selectedText(select) {
        const option = select && select.options[select.selectedIndex];
        return option && option.value ? option.textContent.trim() : '';
    }

    function updateSummary() {
        const option = service.options[service.selectedIndex];
        const price = option && option.value ? Number(option.dataset.price) : 0;
        const type = form.querySelector('input[name="payment_type"]:checked');
        const now = type && type.value === 'partial' ? Math.round(price * percent) / 100 : price;

        out('customer', field('customer_name').value.trim() || '—');
        out('service', option && option.value ? option.dataset.name : '—');
        out('duration', option && option.value ? option.dataset.duration : '—');
        out('time', selectedText(time) || '—');
        out('staff', staff.value ? selectedText(staff) : 'First available');
        out('price', peso(price));
        out('now', peso(now));
        out('balance', peso(price - now));
    }

    async function loadStaff() {
        const thisRequest = ++requestNo;

        if (!service.value || !time.value) {
            staff.innerHTML = '<option value="">Choose a service first</option>';
            staff.disabled = true;
            updateSummary();
            return;
        }

        if (staff.value) preferredStaff = staff.value;
        staff.disabled = true;
        staff.innerHTML = '<option value="">Checking who is free…</option>';

        try {
            const url = new URL(form.dataset.staffUrl, window.location.origin);
            url.searchParams.set('service_id', service.value);
            url.searchParams.set('time', time.value);
            const response = await fetch(url, { headers: { Accept: 'application/json' } });
            const data = await response.json();
            if (thisRequest !== requestNo) return;

            const list = data.staff || [];
            staff.innerHTML = '';
            staff.add(new Option(list.length ? 'First available' : 'Nobody is free at this time', ''));
            list.forEach((member) => {
                staff.add(new Option(`${member.name} · ${member.specialization}`, member.id, false, String(member.id) === preferredStaff));
            });
            staff.disabled = list.length === 0;
            staffHint.textContent = list.length
                ? `${list.length} staff member${list.length === 1 ? '' : 's'} free for this service.`
                : 'Try a later time slot or another service.';
        } catch (error) {
            staff.innerHTML = '<option value="">First available</option>';
            staff.disabled = false;
            staffHint.textContent = 'Could not check availability. The first free staff member will be assigned.';
        }

        updateSummary();
    }

    service.addEventListener('change', loadStaff);
    time.addEventListener('change', loadStaff);
    staff.addEventListener('change', () => { preferredStaff = staff.value; updateSummary(); });
    form.addEventListener('input', updateSummary);
    form.addEventListener('change', updateSummary);

    loadStaff();
}
