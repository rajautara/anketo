/* Anketo — appointment field: date → available time slots.
   Mirrors App\Libraries\AppointmentAvailability::slotsForDate. Booked slots are
   disabled; the chosen "Y-m-d H:i" is written to the hidden .ak-appt-value input.
   The server re-validates and re-checks bookings on submit. */
(function () {
    'use strict';

    function toMinutes(hhmm) {
        var m = /^([01]\d|2[0-3]):([0-5]\d)$/.exec(hhmm || '');
        return m ? (parseInt(m[1], 10) * 60 + parseInt(m[2], 10)) : null;
    }
    function fromMinutes(min) {
        var h = Math.floor(min / 60), m = min % 60;
        return (h < 10 ? '0' : '') + h + ':' + (m < 10 ? '0' : '') + m;
    }
    // Parse "YYYY-MM-DD" as a LOCAL date (avoid UTC parsing of the string form).
    function parseYmd(ymd) {
        var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(ymd || '');
        if (!m) { return null; }
        var d = new Date(parseInt(m[1], 10), parseInt(m[2], 10) - 1, parseInt(m[3], 10));
        if (d.getFullYear() !== +m[1] || d.getMonth() !== +m[2] - 1 || d.getDate() !== +m[3]) { return null; }
        return d;
    }
    function isoWeekday(d) { var w = d.getDay(); return w === 0 ? 7 : w; }

    function slotsForDate(ymd, cfg) {
        var d = parseYmd(ymd);
        if (!d) { return []; }
        var weekdays = (cfg.weekdays || []).map(Number);
        if (weekdays.indexOf(isoWeekday(d)) === -1) { return []; }
        var start = toMinutes(cfg.start_time), end = toMinutes(cfg.end_time), step = parseInt(cfg.slot_minutes, 10) || 0;
        if (start === null || end === null || step <= 0 || end <= start) { return []; }
        var out = [];
        for (var t = start; t + step <= end; t += step) { out.push(fromMinutes(t)); }
        return out;
    }

    function initWidget(root) {
        var cfg, booked;
        try { cfg = JSON.parse(root.getAttribute('data-config') || '{}'); } catch (e) { cfg = {}; }
        try { booked = JSON.parse(root.getAttribute('data-booked') || '[]'); } catch (e) { booked = []; }

        var dateInput = root.querySelector('.ak-appt-date');
        var slotsBox  = root.querySelector('.ak-appt-slots');
        var emptyMsg  = root.querySelector('.ak-appt-empty');
        var hidden    = root.querySelector('.ak-appt-value');
        if (!dateInput || !slotsBox || !hidden) { return; }

        function render() {
            slotsBox.innerHTML = '';
            var ymd = dateInput.value;
            var slots = ymd ? slotsForDate(ymd, cfg) : [];

            if (slots.length === 0) {
                hidden.value = '';
                if (emptyMsg) { emptyMsg.classList.toggle('d-none', !ymd); }
                return;
            }
            if (emptyMsg) { emptyMsg.classList.add('d-none'); }

            var selected = hidden.value;
            slots.forEach(function (slot) {
                var value = ymd + ' ' + slot;
                var isBooked = booked.indexOf(value) !== -1;

                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-sm ak-appt-slot ' + (value === selected ? 'btn-primary' : 'btn-outline-secondary');
                btn.textContent = slot;
                if (isBooked) {
                    btn.disabled = true;
                    btn.classList.add('ak-appt-booked');
                    btn.title = 'Already booked';
                } else {
                    btn.addEventListener('click', function () {
                        hidden.value = value;
                        hidden.dispatchEvent(new Event('change', { bubbles: true }));
                        render();
                    });
                }
                slotsBox.appendChild(btn);
            });
        }

        // If reloading with old input, preselect its date.
        if (hidden.value) {
            var parts = hidden.value.split(' ');
            if (parts.length === 2) { dateInput.value = parts[0]; }
        }

        dateInput.addEventListener('change', function () { hidden.value = ''; render(); });
        render();
    }

    function init() {
        var roots = document.querySelectorAll('[data-ak-appointment]');
        Array.prototype.forEach.call(roots, initWidget);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
