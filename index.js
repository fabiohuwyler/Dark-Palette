(function (blocks, blockEditor, components, element, i18n) {
	'use strict';

	var el = element.createElement;
	var __ = i18n.__;
	var InspectorControls = blockEditor.InspectorControls;
	var useBlockProps = blockEditor.useBlockProps;
	var PanelBody = components.PanelBody;
	var ToggleControl = components.ToggleControl;
	var TextControl = components.TextControl;

	function Icon(props) {
		var paths = {
			light: el('g', {}, el('circle', { cx: 12, cy: 12, r: 4 }), el('path', { d: 'M12 2v2M12 20v2M4.93 4.93l1.42 1.42M17.65 17.65l1.42 1.42M2 12h2M20 12h2M4.93 19.07l1.42-1.42M17.65 6.35l1.42-1.42' })),
			dark: el('path', { d: 'M20.4 15.4A8.5 8.5 0 0 1 8.6 3.6 8.5 8.5 0 1 0 20.4 15.4Z' }),
			auto: el('g', {}, el('rect', { x: 3, y: 4, width: 18, height: 13, rx: 2 }), el('path', { d: 'M8 21h8M12 17v4' }))
		};
		return el('svg', { viewBox: '0 0 24 24', 'aria-hidden': true }, paths[props.name]);
	}

	blocks.registerBlockType('dark-palette/appearance-toggle', {
		edit: function (props) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var displayLabel = attributes.label === 'Appearance' ? __('Appearance', 'dark-palette') : attributes.label;
			var options = [
				{ value: 'light', label: __('Light', 'dark-palette') },
				{ value: 'dark', label: __('Dark', 'dark-palette') }
			];
			if (attributes.showAuto) {
				options.push({ value: 'auto', label: __('Auto', 'dark-palette') });
			}

			return el(
				element.Fragment,
				{},
				el(InspectorControls, {},
					el(PanelBody, { title: __('Display', 'dark-palette') },
						el(ToggleControl, {
							label: __('Show text labels', 'dark-palette'),
							checked: attributes.showLabels,
							onChange: function (value) { setAttributes({ showLabels: value }); }
						}),
						el(ToggleControl, {
							label: __('Include Auto option', 'dark-palette'),
							checked: attributes.showAuto,
							onChange: function (value) { setAttributes({ showAuto: value }); }
						}),
						el(TextControl, {
							label: __('Accessible group label', 'dark-palette'),
							value: displayLabel,
							onChange: function (value) { setAttributes({ label: value }); }
						})
					)
				),
				el('div', useBlockProps({
					className: 'dark-palette-appearance-toggle',
					'data-show-labels': attributes.showLabels ? 'true' : 'false'
				}),
					el('span', { className: 'dark-palette-appearance-toggle__label' }, displayLabel),
					el('div', { className: 'dark-palette-appearance-toggle__controls' },
						options.map(function (option) {
							return el('span', { className: 'dark-palette-appearance-toggle__button', key: option.value },
								el('span', { className: 'dark-palette-appearance-toggle__icon' }, el(Icon, { name: option.value })),
								el('span', { className: 'dark-palette-appearance-toggle__text' }, option.label)
							);
						})
					)
				)
			);
		},
		save: function () { return null; }
	});
})(window.wp.blocks, window.wp.blockEditor, window.wp.components, window.wp.element, window.wp.i18n);
