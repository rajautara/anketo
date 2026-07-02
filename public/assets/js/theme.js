/* Anketo — theme (dark/light) toggle.
   The initial theme is applied inline in the <head> (anti-FOUC) before this
   file runs; this file only handles user toggling + syncing across tabs. */
(function () {
    'use strict';

    var STORAGE_KEY = 'ak-theme';

    function stored() {
        try { return localStorage.getItem(STORAGE_KEY); } catch (e) { return null; }
    }

    function systemPref() {
        return (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light';
    }

    function current() {
        return document.documentElement.getAttribute('data-bs-theme') || stored() || systemPref();
    }

    function apply(theme, persist) {
        document.documentElement.setAttribute('data-bs-theme', theme);
        if (persist) {
            try { localStorage.setItem(STORAGE_KEY, theme); } catch (e) {}
        }
        document.querySelectorAll('[data-ak-theme-toggle]').forEach(function (btn) {
            btn.setAttribute('aria-pressed', theme === 'dark' ? 'true' : 'false');
            btn.setAttribute('title', theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode');
        });
    }

    function init() {
        apply(current(), false);
        document.querySelectorAll('[data-ak-theme-toggle]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                apply(current() === 'dark' ? 'light' : 'dark', true);
            });
        });
    }

    // Keep tabs in sync.
    window.addEventListener('storage', function (e) {
        if (e.key === STORAGE_KEY && e.newValue) { apply(e.newValue, false); }
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
