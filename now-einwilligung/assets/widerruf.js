/* NOW-Einwilligung – Widerruf: Absenden-Button erst aktiv, wenn vollständig */
(function () {
    'use strict';
    var form = document.getElementById('widerrufForm');
    if (!form) { return; }
    var submitBtn = document.getElementById('submitBtn');

    function isValid() {
        var vorname  = (form.vorname  ? form.vorname.value.trim()  : '');
        var nachname = (form.nachname ? form.nachname.value.trim() : '');
        if (vorname.length < 2 || nachname.length < 2) { return false; }
        if (form.anbieter_id && !form.anbieter_id.value) { return false; }
        var required = ['berechtigung', 'widerruf_bestaetigt'];
        for (var i = 0; i < required.length; i++) {
            var el = form.elements[required[i]];
            if (!el || !el.checked) { return false; }
        }
        return true;
    }
    function refresh() { submitBtn.disabled = !isValid(); }
    form.addEventListener('input', refresh);
    form.addEventListener('change', refresh);
    refresh();
})();
