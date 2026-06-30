/* NOW-Einwilligung – Formularlogik (ohne Abhängigkeiten) */
(function () {
    'use strict';

    // Tabs
    var tabLinks = document.querySelectorAll('.tab-link');
    tabLinks.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = btn.getAttribute('data-tab');
            document.querySelectorAll('.tab-link').forEach(function (b) { b.classList.remove('active'); });
            document.querySelectorAll('.tab-content').forEach(function (c) { c.classList.remove('active'); });
            btn.classList.add('active');
            var panel = document.getElementById(id);
            if (panel) { panel.classList.add('active'); }
        });
    });

    // Absenden-Button erst aktiv, wenn alles ausgefüllt/angekreuzt ist
    var form = document.getElementById('consentForm');
    if (!form) { return; }
    var submitBtn = document.getElementById('submitBtn');

    function isValid() {
        var vorname  = (form.vorname  ? form.vorname.value.trim()  : '');
        var nachname = (form.nachname ? form.nachname.value.trim() : '');
        if (vorname.length < 2 || nachname.length < 2) { return false; }

        if (form.anbieter_id && !form.anbieter_id.value) { return false; }

        var required = ['berechtigung', 'now_info', 'agb_wisy', 'datenschutz_wisy'];
        for (var i = 0; i < required.length; i++) {
            var el = form.elements[required[i]];
            if (!el || !el.checked) { return false; }
        }
        return true;
    }

    function refresh() {
        submitBtn.disabled = !isValid();
    }

    form.addEventListener('input', refresh);
    form.addEventListener('change', refresh);
    refresh();
})();
