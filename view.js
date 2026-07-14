(function () {
	'use strict';

	var STORAGE_KEY = 'dark-palette-preference';
	var media = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;

	function validPreference(value) {
		return value === 'light' || value === 'dark' || value === 'auto';
	}

	function getPreference() {
		try {
			var stored = window.localStorage.getItem(STORAGE_KEY);
			return validPreference(stored) ? stored : 'auto';
		} catch (error) {
			return 'auto';
		}
	}

	function setPreference(preference) {
		try {
			window.localStorage.setItem(STORAGE_KEY, preference);
		} catch (error) {}
	}

	function resolvedAppearance(preference) {
		if (preference === 'auto') {
			return media && media.matches ? 'dark' : 'light';
		}
		return preference;
	}

	function syncButtons(preference) {
		document.querySelectorAll('.dark-palette-appearance-toggle__button').forEach(function (button) {
			var active = button.dataset.appearance === preference;
			button.setAttribute('aria-pressed', active ? 'true' : 'false');
		});
	}

	function darkModeDisabled() {
		return document.documentElement.dataset.darkPaletteDisabled === 'true';
	}

	function applyPreference(preference, persist) {
		if (!validPreference(preference)) {
			preference = 'auto';
		}

		var appearance = darkModeDisabled() ? 'light' : resolvedAppearance(preference);
		document.documentElement.dataset.darkPalettePreference = preference;
		document.documentElement.dataset.darkPaletteAppearance = appearance;
		document.documentElement.style.colorScheme = appearance;

		if (persist) {
			setPreference(preference);
		}

		syncButtons(preference);
		document.dispatchEvent(new CustomEvent('darkpaletteappearancechange', {
			detail: { preference: preference, appearance: appearance }
		}));
	}

	function initialise() {
		var preference = getPreference();
		applyPreference(preference, false);

		document.addEventListener('click', function (event) {
			var button = event.target.closest('.dark-palette-appearance-toggle__button');
			if (!button) {
				return;
			}
			if (!darkModeDisabled()) {
				applyPreference(button.dataset.appearance, true);
			}
		});

		if (media) {
			var onSystemChange = function () {
				if (getPreference() === 'auto') {
					applyPreference('auto', false);
				}
			};
			if (media.addEventListener) {
				media.addEventListener('change', onSystemChange);
			} else if (media.addListener) {
				media.addListener(onSystemChange);
			}
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initialise);
	} else {
		initialise();
	}
})();
