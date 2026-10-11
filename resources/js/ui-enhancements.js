/**
 * UI Enhancements - Overflow Menus & Modal Management
 * Handles action menu dropdowns and modal dialogs
 */

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    // Close all open dropdowns
    if (!e.target.closest('.action-menu, .row-menu')) {
        closeAllMenus();
    }
});

/**
 * Toggle action menu dropdown
 * @param {Element} button - The menu button element
 */
function toggleMenu(button) {
    const menu = button.nextElementSibling;
    if (menu && menu.classList.contains('menu-dropdown')) {
        const willOpen = !menu.classList.contains('open');

        // Close other open menus
        closeAllMenus();

        // Toggle current menu
        if (willOpen) {
            menu.classList.add('open');
            setActionMenuContainerState(menu, true);
        }
    }
}

/**
 * Close all action menus
 */
function closeAllMenus() {
    document.querySelectorAll('.menu-dropdown.open').forEach(menu => {
        menu.classList.remove('open');
        setActionMenuContainerState(menu, false);
    });
}

function setActionMenuContainerState(menu, isOpen) {
    const card = menu.closest('.card');
    const tableWrap = menu.closest('.table-wrap');

    if (card) {
        card.classList.toggle('action-card-open', isOpen);
    }

    if (tableWrap) {
        tableWrap.classList.toggle('action-card-open', isOpen);
    }
}

/**
 * Open modal dialog
 * @param {string} modalId - The ID of the modal element
 */
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('open');
        closeAllMenus();
    }
}

/**
 * Close modal dialog
 * @param {string} modalId - The ID of the modal element
 */
function closeModal(modalId) {
    if (!modalId) {
        document.querySelectorAll('.modal.open').forEach(modal => {
            modal.classList.remove('open');
        });
        return;
    }

    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('open');
    }
}

/**
 * Close modal when clicking outside modal-content
 */
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        const modal = e.target;
        if (modal.classList.contains('open')) {
            modal.classList.remove('open');
        }
    }
});

/**
 * Handle Escape key to close modal
 */
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal.open').forEach(modal => {
            modal.classList.remove('open');
        });
        closeAllMenus();
    }
});

/**
 * Close modal on close button click
 */
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('close-modal')) {
        const modal = e.target.closest('.modal');
        if (modal) {
            modal.classList.remove('open');
        }
    }
});

/**
 * Utility: Confirm action before proceeding
 * @param {string} message - Confirmation message
 * @param {Function} callback - Function to execute if confirmed
 */
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

/**
 * Utility: Show toast notification
 * @param {string} message - Message to display
 * @param {string} type - Type: 'success', 'error', 'warning', 'info'
 * @param {number} duration - Duration in milliseconds (default: 3000)
 */
function showToast(message, type = 'info', duration = 3000) {
    // Create toast container if it doesn't exist
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 350px;
        `;
        document.body.appendChild(container);
    }

    // Create toast element
    const toast = document.createElement('div');
    const bgColor = {
        success: '#E8F5E9',
        error: '#FDECEC',
        warning: '#FFF3E0',
        info: 'rgba(139, 58, 82, 0.1)'
    }[type] || 'rgba(139, 58, 82, 0.1)';

    const textColor = {
        success: '#2E7D32',
        error: '#C62828',
        warning: '#E65100',
        info: '#8B3A52'
    }[type] || '#8B3A52';

    toast.style.cssText = `
        background: ${bgColor};
        color: ${textColor};
        padding: 14px 16px;
        border-radius: 8px;
        border-left: 3px solid ${textColor};
        margin-bottom: 10px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        animation: slideIn 0.3s ease;
    `;

    toast.textContent = message;
    container.appendChild(toast);

    // Auto remove after duration
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

// Add slide animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// Make functions globally accessible
window.toggleMenu = toggleMenu;
window.openModal = openModal;
window.closeModal = closeModal;
window.confirmAction = confirmAction;
window.showToast = showToast;

// Custom calendar and time controls for native date/select fields.
document.addEventListener('DOMContentLoaded', function() {
    initBeautyDatePickers();
    initBeautyTimePickers();
});

function initBeautyDatePickers() {
    document.querySelectorAll('input[type="date"].beauty-date').forEach(input => {
        if (input.dataset.beautyReady === '1') return;
        input.dataset.beautyReady = '1';
        input.classList.add('beauty-native-hidden');

        const picker = document.createElement('div');
        picker.className = 'beauty-calendar';

        const selected = input.value ? new Date(input.value + 'T00:00:00') : new Date();
        let viewDate = new Date(selected.getFullYear(), selected.getMonth(), 1);

        const label = document.createElement('button');
        label.type = 'button';
        label.className = 'beauty-date-display';
        label.innerHTML = '<i class="fas fa-calendar-alt"></i><span></span>';

        const panel = document.createElement('div');
        panel.className = 'beauty-calendar-panel';

        picker.appendChild(label);
        picker.appendChild(panel);
        input.insertAdjacentElement('afterend', picker);

        const minDate = input.min ? new Date(input.min + 'T00:00:00') : null;
        const maxDate = input.max ? new Date(input.max + 'T00:00:00') : null;

        const formatDisplay = value => {
            if (!value) return input.dataset.placeholder || 'Select date';
            return new Date(value + 'T00:00:00').toLocaleDateString('en-PH', {
                weekday: 'short',
                month: 'short',
                day: 'numeric',
                year: 'numeric'
            });
        };

        const updateLabel = () => {
            label.querySelector('span').textContent = formatDisplay(input.value);
        };

        const render = () => {
            const monthLabel = viewDate.toLocaleDateString('en-PH', { month: 'long', year: 'numeric' });
            const firstDay = new Date(viewDate.getFullYear(), viewDate.getMonth(), 1);
            const startOffset = firstDay.getDay();
            const daysInMonth = new Date(viewDate.getFullYear(), viewDate.getMonth() + 1, 0).getDate();
            const selectedValue = input.value;
            const todayValue = toDateValue(new Date());

            panel.innerHTML = `
                <div class="beauty-calendar-head">
                    <button type="button" class="beauty-cal-nav" data-dir="-1" aria-label="Previous month"><i class="fas fa-chevron-left"></i></button>
                    <strong>${monthLabel}</strong>
                    <button type="button" class="beauty-cal-nav" data-dir="1" aria-label="Next month"><i class="fas fa-chevron-right"></i></button>
                </div>
                <div class="beauty-calendar-week">
                    <span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span>
                </div>
                <div class="beauty-calendar-grid"></div>
            `;

            const grid = panel.querySelector('.beauty-calendar-grid');
            for (let i = 0; i < startOffset; i++) {
                const spacer = document.createElement('span');
                spacer.className = 'beauty-cal-empty';
                grid.appendChild(spacer);
            }

            for (let day = 1; day <= daysInMonth; day++) {
                const current = new Date(viewDate.getFullYear(), viewDate.getMonth(), day);
                const value = toDateValue(current);
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'beauty-cal-day';
                button.textContent = day;
                button.dataset.value = value;

                if (value === selectedValue) button.classList.add('selected');
                if (value === todayValue) button.classList.add('today');
                if ((minDate && current < minDate) || (maxDate && current > maxDate)) {
                    button.disabled = true;
                }

                grid.appendChild(button);
            }
        };

        label.addEventListener('click', () => {
            closeBeautyPickers(picker);
            picker.classList.toggle('open');
            render();
        });

        panel.addEventListener('click', event => {
            const nav = event.target.closest('.beauty-cal-nav');
            const day = event.target.closest('.beauty-cal-day');

            if (nav) {
                viewDate.setMonth(viewDate.getMonth() + Number(nav.dataset.dir));
                render();
            }

            if (day && !day.disabled) {
                input.value = day.dataset.value;
                input.dispatchEvent(new Event('change', { bubbles: true }));
                updateLabel();
                picker.classList.remove('open');
            }
        });

        input.addEventListener('change', updateLabel);
        updateLabel();
        render();
    });
}

function initBeautyTimePickers() {
    document.querySelectorAll('select.beauty-time').forEach(select => {
        if (select.dataset.beautyReady === '1') return;
        select.dataset.beautyReady = '1';
        select.classList.add('beauty-native-hidden');

        const picker = document.createElement('div');
        picker.className = 'beauty-time-picker';

        Array.from(select.options).forEach(option => {
            if (!option.value) return;

            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'beauty-time-chip';
            button.textContent = option.textContent;
            button.dataset.value = option.value;
            if (option.selected) button.classList.add('selected');
            picker.appendChild(button);
        });

        select.insertAdjacentElement('afterend', picker);

        const sync = () => {
            picker.querySelectorAll('.beauty-time-chip').forEach(button => {
                button.classList.toggle('selected', button.dataset.value === select.value);
            });
        };

        picker.addEventListener('click', event => {
            const button = event.target.closest('.beauty-time-chip');
            if (!button) return;
            select.value = button.dataset.value;
            select.dispatchEvent(new Event('change', { bubbles: true }));
            sync();
        });

        select.addEventListener('change', sync);
        sync();
    });
}

function closeBeautyPickers(exceptPicker = null) {
    document.querySelectorAll('.beauty-calendar.open').forEach(picker => {
        if (picker !== exceptPicker) picker.classList.remove('open');
    });
}

function toDateValue(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

document.addEventListener('click', function(event) {
    if (!event.target.closest('.beauty-calendar')) {
        closeBeautyPickers();
    }
});
