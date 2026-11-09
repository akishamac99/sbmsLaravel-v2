{{-- Accessibility helpers shared by the new pages: screen-reader-only text and visible keyboard focus. --}}
<style>
    .sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
    .confirm-panel a:focus-visible,.confirm-panel button:focus-visible,.confirm-panel input:focus-visible,
    .card a:focus-visible,.card button:focus-visible,.card input:focus-visible,.card select:focus-visible{outline:3px solid #1a56db;outline-offset:2px}
    .confirm-panel{max-width:640px}
    .field-error{color:#9b1c1c;font-weight:600;margin-top:.25rem}
</style>
