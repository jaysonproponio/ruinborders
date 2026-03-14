(function () {
    var STORAGE_KEY = 'darkMode';
    var BODY_CLASS = 'dark-mode';

    function isDarkEnabled() {
        return window.localStorage.getItem(STORAGE_KEY) === 'enabled';
    }

    function syncExistingToggle(enabled) {
        var existingToggle = document.getElementById('darkModeBtn');
        if (!existingToggle) {
            return;
        }

        var icon = existingToggle.querySelector('i');
        var label = existingToggle.querySelector('span');

        if (icon) {
            icon.className = enabled ? 'fas fa-sun' : 'fas fa-moon';
        }

        if (label) {
            label.textContent = enabled ? 'Switch to Light Mode' : 'Switch to Dark Mode';
        }
    }

    function syncFab(enabled) {
        var button = document.getElementById('neuThemeToggle');
        if (!button) {
            return;
        }

        var icon = button.querySelector('i');
        button.setAttribute('aria-pressed', enabled ? 'true' : 'false');
        button.setAttribute('title', enabled ? 'Switch to light mode' : 'Switch to dark mode');

        if (icon) {
            icon.className = enabled ? 'fas fa-sun' : 'fas fa-moon';
        }
    }

    function applyTheme(enabled, persist) {
        document.documentElement.classList.toggle(BODY_CLASS, enabled);
        document.body.classList.toggle(BODY_CLASS, enabled);

        if (persist !== false) {
            window.localStorage.setItem(STORAGE_KEY, enabled ? 'enabled' : 'disabled');
        }

        syncExistingToggle(enabled);
        syncFab(enabled);

        document.dispatchEvent(new CustomEvent('neu-theme-change', {
            detail: { dark: enabled }
        }));
    }

    function ensureFab() {
        if (document.getElementById('neuThemeToggle')) {
            return;
        }

        var button = document.createElement('button');
        button.type = 'button';
        button.id = 'neuThemeToggle';
        button.className = 'theme-fab';
        button.setAttribute('aria-label', 'Toggle theme');
        button.innerHTML = '<i class="fas fa-moon" aria-hidden="true"></i>';

        button.addEventListener('click', function () {
            applyTheme(!document.body.classList.contains(BODY_CLASS));
        });

        document.body.appendChild(button);
    }

    function bindExistingToggle() {
        var existingToggle = document.getElementById('darkModeBtn');
        if (!existingToggle || existingToggle.dataset.neuBound === 'true') {
            return;
        }

        existingToggle.dataset.neuBound = 'true';
        existingToggle.addEventListener('click', function () {
            window.setTimeout(function () {
                var enabled = document.body.classList.contains(BODY_CLASS);
                syncExistingToggle(enabled);
                syncFab(enabled);
            }, 0);
        });
    }

    function init() {
        if (!document.body) {
            return;
        }

        applyTheme(isDarkEnabled(), false);
        ensureFab();
        bindExistingToggle();
        syncExistingToggle(document.body.classList.contains(BODY_CLASS));
        syncFab(document.body.classList.contains(BODY_CLASS));
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    window.applyNeuTheme = applyTheme;
}());
