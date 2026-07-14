<?php
/**
 * Uninstall Dark Palette.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

foreach ( array(
	'dark_palette_colors',
	'dark_palette_disabled_ui',
	'dark_palette_disabled_scopes',
	'dark_palette_migrated_from_tt5',
	'tt5_dark_mode_colors',
	'tt5_dark_mode_disabled_ui',
	'tt5_dark_mode_disabled_scopes',
) as $option ) {
	delete_option( $option );
}

foreach ( array(
	'_dark_palette_disabled',
	'_dark_palette_disabled_behavior',
	'_dark_palette_disabled_message',
	'_tt5_dark_mode_disabled',
	'_tt5_dark_mode_disabled_behavior',
	'_tt5_dark_mode_disabled_message',
) as $meta_key ) {
	delete_post_meta_by_key( $meta_key );
}
