# UI/UX standards applied (ENGSF2 Lesson 7)

Memory aid: *Tell me what is happening (1), in words I understand (2), let me undo it (3), do it the same way every time (4), and stop me before I break something (5).*

| Rule | What the system does | Where |
|---|---|---|
| 1 Visibility of system status | Top progress bar on every page change; submit buttons switch to "Please wait..." with a spinner and cannot be double-sent; after 15 s a "taking longer than usual, check your connection" note appears; success/error toasts stay 8-12 s with a close button; active menu item is highlighted (`aria-current`); screen reader announcements for sending | `resources/js/ux-standards.js`, `layouts/portal.blade.php` |
| 2 Match with the real world | English / Filipino switch (remembered per device, `lang` set on the page); plain-language error pages (403, 404, 419, 429, 500, 503) instead of "Server Error"; labels use salon words (Booking, Service, Stylist); amounts in pesos (₱) everywhere; Philippine mobile format `09XX XXX XXXX` | `resources/views/errors/*` |
| 3 User control and freedom | Cancel on every modal; Esc and outside click close modals; focus returns to the trigger; risky actions (cancel, decline, archive, reject, delete, no-show, block) ask first in a styled dialog whose default focused button is "No, go back" (Esc and outside click also cancel); service delete uses its own confirmation page; unsaved-changes warning on Edit booking and feedback forms (`data-warn-unsaved`); password reset can be restarted; log out always available | `ui-enhancements.js`, views |
| 4 Consistency and standards | One label per action ("Save changes", "Delete service", "Cancel booking"); trash icon only means delete (upload "Remove" uses X); ban icon for cancel/decline; X only closes; one button/color system (`btn-primary`, `menu-danger`); shared partials (status badge, settings tabs) | views, `style.css` |
| 5 Error prevention | Submit looks grayed out until required fields are valid, with the reason beside it (still clickable so it explains what is missing); mobile fields accept digits only and flag mistakes while typing; date/time/slot pickers, dropdowns and radio inputs instead of free text; server-side rules mirror every client check; row locking stops double payments/approvals | `ux-standards.js`, requests, actions |

Accessibility links (Lesson 6): text size, contrast, spacing, 44 px touch targets on touch devices, reduced motion, text alternatives, status never by color alone (badges carry words).

## Mini heuristic evaluation (steps 3-4 of the guide)
| Screen | Rule | Problem found | Severity | Fix applied |
|---|---|---|---|---|
| Any form | 1 | No feedback after Submit on slow data | 3 | Busy state + 15 s slow-connection note |
| Server errors | 2 | Default "500 Server Error" jargon | 3 | Plain-language error pages |
| Upload proof | 4 | Trash icon used for "Remove" preview | 2 | X icon |
| Cancel/Decline | 4 | Close "X" icon used for cancel | 2 | Ban icon |
| Prices | 2/4 | "PHP" and "₱" mixed | 1 | ₱ everywhere |
| Forms | 5 | Submit active with empty required fields | 3 | Grayed out + hint |
| Contact number | 5 | Letters accepted until server check | 3 | Digits only + live format hint |
| Edit booking / feedback | 3 | Typed text lost on accidental leave | 2 | Unsaved-changes warning |

## Honest limits (say these in the defense)
- Only a heuristic evaluation was done; no usability testing with real clients yet.
- Confirmations now use a styled dialog; an "Undo" toast after deletes would be better still.
- The Filipino option translates labels, menus, buttons and common messages (about 560 phrases); some long or dynamic sentences, such as validation messages, still appear in English.
- Undo is not available for finished payments; control moves earlier (review + confirm).
