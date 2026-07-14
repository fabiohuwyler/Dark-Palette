<?php
/**
 * Plugin Name:       Dark Palette
 * Plugin URI:        https://github.com/fabiohuwyler/dark-palette
 * Description:       Native dark mode for modern WordPress block themes.
 * Version:           1.0.0
 * Requires at least: 6.7
 * Requires PHP:      7.4
 * Author:            Fabio Huwyler
 * Author URI:        https://fabiohuwyler.ch
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       dark-palette
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const DARK_PALETTE_VERSION       = '1.0.0';
const DARK_PALETTE_OPTION        = 'dark_palette_colors';
const DARK_PALETTE_META          = '_dark_palette_disabled';
const DARK_PALETTE_META_BEHAVIOR = '_dark_palette_disabled_behavior';
const DARK_PALETTE_META_MESSAGE  = '_dark_palette_disabled_message';
const DARK_PALETTE_UI_OPTION     = 'dark_palette_disabled_ui';
const DARK_PALETTE_SCOPE_OPTION  = 'dark_palette_disabled_scopes';


/** Migrate settings and post metadata from pre-1.0 TT5 Dark Mode builds. */
function dark_palette_maybe_migrate_legacy_data() {
	if ( '1' === get_option( 'dark_palette_migrated_from_tt5', '0' ) ) {
		return;
	}

	$option_map = array(
		'tt5_dark_mode_colors'          => DARK_PALETTE_OPTION,
		'tt5_dark_mode_disabled_ui'     => DARK_PALETTE_UI_OPTION,
		'tt5_dark_mode_disabled_scopes' => DARK_PALETTE_SCOPE_OPTION,
	);

	foreach ( $option_map as $legacy => $current ) {
		if ( false === get_option( $current, false ) ) {
			$value = get_option( $legacy, false );
			if ( false !== $value ) {
				update_option( $current, $value );
			}
		}
	}

	global $wpdb;
	$meta_map = array(
		'_tt5_dark_mode_disabled'          => DARK_PALETTE_META,
		'_tt5_dark_mode_disabled_behavior' => DARK_PALETTE_META_BEHAVIOR,
		'_tt5_dark_mode_disabled_message'  => DARK_PALETTE_META_MESSAGE,
	);
	foreach ( $meta_map as $legacy => $current ) {
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->postmeta} SET meta_key = %s WHERE meta_key = %s AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM {$wpdb->postmeta}) AS existing WHERE existing.post_id = {$wpdb->postmeta}.post_id AND existing.meta_key = %s)",
				$current,
				$legacy,
				$current
			)
		);
	}

	update_option( 'dark_palette_migrated_from_tt5', '1', false );
}
add_action( 'init', 'dark_palette_maybe_migrate_legacy_data', 1 );

function dark_palette_default_colors() {
	return array(
		'base'     => '#111111',
		'contrast' => '#f7f7f7',
		'accent-1' => '#ffef71',
		'accent-2' => '#5b345a',
		'accent-3' => '#b7a9ff',
		'accent-4' => '#b8b8b8',
		'accent-5' => '#1c1c1c',
		'accent-6' => '#4a4a4a',
	);
}

function dark_palette_default_disabled_ui() {
	return array(
		'behavior' => 'message',
		'message'  => __( 'Dark mode is not available on this page.', 'dark-palette' ),
	);
}

function dark_palette_default_scopes() {
	return array(
		'archives'             => false,
		'search'               => false,
		'404'                  => false,
		'password_protected'   => false,
		'woocommerce_checkout' => false,
		'login'                => false,
		'templates'            => array(),
	);
}

function dark_palette_get_colors() {
	$saved     = get_option( DARK_PALETTE_OPTION, array() );
	$defaults  = dark_palette_default_colors();
	$originals = dark_palette_get_original_colors();
	foreach ( array_keys( $originals ) as $slug ) {
		if ( ! isset( $defaults[ $slug ] ) ) {
			$defaults[ $slug ] = '#111111';
		}
	}
	return wp_parse_args( is_array( $saved ) ? $saved : array(), $defaults );
}

function dark_palette_get_disabled_ui() {
	$saved = get_option( DARK_PALETTE_UI_OPTION, array() );
	return wp_parse_args( is_array( $saved ) ? $saved : array(), dark_palette_default_disabled_ui() );
}

function dark_palette_get_scopes() {
	$saved  = get_option( DARK_PALETTE_SCOPE_OPTION, array() );
	$scopes = wp_parse_args( is_array( $saved ) ? $saved : array(), dark_palette_default_scopes() );
	$scopes['templates'] = isset( $scopes['templates'] ) && is_array( $scopes['templates'] )
		? array_values( array_filter( array_map( 'sanitize_key', $scopes['templates'] ) ) )
		: array();
	return $scopes;
}

/** Return the active theme palette keyed by preset slug. */
function dark_palette_get_original_colors() {
	$colors = array();
	if ( ! function_exists( 'wp_get_global_settings' ) ) {
		return $colors;
	}

	$settings = wp_get_global_settings();
	$palettes = isset( $settings['color']['palette'] ) && is_array( $settings['color']['palette'] ) ? $settings['color']['palette'] : array();
	foreach ( array( 'theme', 'default', 'custom' ) as $origin ) {
		if ( empty( $palettes[ $origin ] ) || ! is_array( $palettes[ $origin ] ) ) {
			continue;
		}
		foreach ( $palettes[ $origin ] as $item ) {
			if ( ! empty( $item['slug'] ) && ! empty( $item['color'] ) && ! isset( $colors[ $item['slug'] ] ) ) {
				$colors[ sanitize_key( $item['slug'] ) ] = sanitize_text_field( $item['color'] );
			}
		}
	}
	return $colors;
}

/** Register the block and per-entry editor settings. */
function dark_palette_init() {
	register_block_type( __DIR__ );

	$post_types = get_post_types( array( 'show_in_rest' => true ), 'names' );
	foreach ( $post_types as $post_type ) {
		register_post_meta(
			$post_type,
			DARK_PALETTE_META,
			array(
				'type'              => 'boolean',
				'single'            => true,
				'default'           => false,
				'show_in_rest'      => true,
				'sanitize_callback' => 'rest_sanitize_boolean',
				'auth_callback'     => function () { return current_user_can( 'edit_posts' ); },
			)
		);
		register_post_meta(
			$post_type,
			DARK_PALETTE_META_BEHAVIOR,
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'show_in_rest'      => true,
				'sanitize_callback' => function ( $value ) { return in_array( $value, array( '', 'hide', 'message', 'disabled' ), true ) ? $value : ''; },
				'auth_callback'     => function () { return current_user_can( 'edit_posts' ); },
			)
		);
		register_post_meta(
			$post_type,
			DARK_PALETTE_META_MESSAGE,
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_textarea_field',
				'auth_callback'     => function () { return current_user_can( 'edit_posts' ); },
			)
		);
	}
}
add_action( 'init', 'dark_palette_init' );

/** Resolve the active singular template to a selectable template slug. */
function dark_palette_current_template_slug() {
	if ( ! is_singular() ) {
		return '';
	}

	$template = get_page_template_slug( get_queried_object_id() );
	if ( $template && 'default' !== $template ) {
		$template = str_replace( '\\', '/', $template );
		return sanitize_key( basename( $template, '.html' ) );
	}

	if ( is_page() ) {
		return 'page';
	}
	if ( is_attachment() ) {
		return 'attachment';
	}
	return 'single';
}

/** Whether dark mode is disabled in the current frontend context. */
function dark_palette_is_disabled() {
	$scopes = dark_palette_get_scopes();

	if ( is_singular() && (bool) get_post_meta( get_queried_object_id(), DARK_PALETTE_META, true ) ) {
		return true;
	}
	if ( is_singular() && ! empty( $scopes['password_protected'] ) && post_password_required( get_queried_object_id() ) ) {
		return true;
	}
	if ( ! empty( $scopes['search'] ) && is_search() ) {
		return true;
	}
	if ( ! empty( $scopes['404'] ) && is_404() ) {
		return true;
	}
	if ( ! empty( $scopes['archives'] ) && is_archive() ) {
		return true;
	}
	if ( ! empty( $scopes['woocommerce_checkout'] ) && function_exists( 'is_checkout' ) && is_checkout() ) {
		return true;
	}

	$template_slug = dark_palette_current_template_slug();
	return $template_slug && in_array( $template_slug, $scopes['templates'], true );
}

function dark_palette_get_effective_disabled_ui() {
	$global = dark_palette_get_disabled_ui();
	if ( ! is_singular() ) {
		return $global;
	}

	$post_id  = get_queried_object_id();
	$behavior = get_post_meta( $post_id, DARK_PALETTE_META_BEHAVIOR, true );
	$message  = get_post_meta( $post_id, DARK_PALETTE_META_MESSAGE, true );
	if ( ! in_array( $behavior, array( 'hide', 'message', 'disabled' ), true ) ) {
		$behavior = $global['behavior'];
	}
	if ( '' === trim( (string) $message ) ) {
		$message = $global['message'];
	}
	return array( 'behavior' => $behavior, 'message' => $message );
}

/** Apply the stored/system preference before the page paints. */
function dark_palette_print_boot_script() {
	$disabled = dark_palette_is_disabled();
	?>
	<script id="dark-palette-boot">
	(function(){
		var disabled=<?php echo $disabled ? 'true' : 'false'; ?>;
		var key='dark-palette-preference';
		var preference='auto';
		try { var stored=window.localStorage.getItem(key); if(stored==='light'||stored==='dark'||stored==='auto'){preference=stored;} } catch(e) {}
		var dark=!disabled&&(preference==='dark'||(preference==='auto'&&window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches));
		var root=document.documentElement;
		root.dataset.darkPaletteDisabled=disabled?'true':'false';
		root.dataset.darkPaletteAppearance=dark?'dark':'light';
		root.dataset.darkPalettePreference=preference;
		root.style.colorScheme=dark?'dark':'light';
	})();
	</script>
	<?php
}
add_action( 'wp_head', 'dark_palette_print_boot_script', 0 );

/** Print the configured palette after block styles are available. */
function dark_palette_print_palette() {
	$colors = dark_palette_get_colors();
	?>
	<style id="dark-palette-palette">
	html[data-dark-palette-appearance="dark"][data-dark-palette-disabled="false"] {
	<?php foreach ( $colors as $slug => $color ) : ?>
		--wp--preset--color--<?php echo esc_html( $slug ); ?>: <?php echo esc_html( $color ); ?>;
	<?php endforeach; ?>
	}
	</style>
	<?php
}
add_action( 'wp_head', 'dark_palette_print_palette', 20 );

function dark_palette_enqueue_editor_sidebar() {
	$asset = include __DIR__ . '/post-editor.asset.php';
	wp_enqueue_script( 'dark-palette-post-editor', plugins_url( 'post-editor.js', __FILE__ ), $asset['dependencies'], $asset['version'], true );
}
add_action( 'enqueue_block_editor_assets', 'dark_palette_enqueue_editor_sidebar' );

/** Force the WordPress login screen to light mode when configured. */
function dark_palette_login_boot_script() {
	$scopes = dark_palette_get_scopes();
	if ( empty( $scopes['login'] ) ) {
		return;
	}
	?><script id="dark-palette-login-boot">document.documentElement.dataset.darkPaletteDisabled='true';document.documentElement.dataset.darkPaletteAppearance='light';document.documentElement.style.colorScheme='light';</script><?php
}
add_action( 'login_head', 'dark_palette_login_boot_script', 0 );

function dark_palette_sanitize_colors( $value ) {
	$defaults  = dark_palette_default_colors();
	$originals = dark_palette_get_original_colors();
	$slugs     = array_unique( array_merge( array_keys( $defaults ), array_keys( $originals ) ) );
	$output    = array();
	$value     = is_array( $value ) ? $value : array();

	foreach ( $slugs as $slug ) {
		$slug     = sanitize_key( $slug );
		$fallback = isset( $defaults[ $slug ] ) ? $defaults[ $slug ] : '#111111';
		$color    = isset( $value[ $slug ] ) ? sanitize_hex_color( $value[ $slug ] ) : '';
		$output[ $slug ] = $color ? $color : $fallback;
	}
	return $output;
}

function dark_palette_sanitize_disabled_ui( $value ) {
	$defaults = dark_palette_default_disabled_ui();
	$value    = is_array( $value ) ? $value : array();
	$behavior = isset( $value['behavior'] ) && in_array( $value['behavior'], array( 'hide', 'message', 'disabled' ), true ) ? $value['behavior'] : $defaults['behavior'];
	$message  = isset( $value['message'] ) ? sanitize_textarea_field( $value['message'] ) : '';
	return array( 'behavior' => $behavior, 'message' => $message ?: $defaults['message'] );
}

function dark_palette_sanitize_scopes( $value ) {
	$value = is_array( $value ) ? $value : array();
	$out   = dark_palette_default_scopes();
	foreach ( array( 'archives', 'search', '404', 'password_protected', 'woocommerce_checkout', 'login' ) as $key ) {
		$out[ $key ] = ! empty( $value[ $key ] );
	}
	$out['templates'] = isset( $value['templates'] ) && is_array( $value['templates'] )
		? array_values( array_unique( array_filter( array_map( 'sanitize_key', $value['templates'] ) ) ) )
		: array();
	return $out;
}

function dark_palette_admin_init() {
	register_setting( 'dark_palette_settings', DARK_PALETTE_OPTION, array( 'type' => 'array', 'sanitize_callback' => 'dark_palette_sanitize_colors', 'default' => dark_palette_default_colors() ) );
	register_setting( 'dark_palette_settings', DARK_PALETTE_UI_OPTION, array( 'type' => 'array', 'sanitize_callback' => 'dark_palette_sanitize_disabled_ui', 'default' => dark_palette_default_disabled_ui() ) );
	register_setting( 'dark_palette_settings', DARK_PALETTE_SCOPE_OPTION, array( 'type' => 'array', 'sanitize_callback' => 'dark_palette_sanitize_scopes', 'default' => dark_palette_default_scopes() ) );
}
add_action( 'admin_init', 'dark_palette_admin_init' );

function dark_palette_admin_menu() {
	add_theme_page( __( 'Dark Palette', 'dark-palette' ), __( 'Dark Palette', 'dark-palette' ), 'edit_theme_options', 'dark-palette', 'dark_palette_render_settings_page' );
}
add_action( 'admin_menu', 'dark_palette_admin_menu' );

function dark_palette_get_template_choices() {
	$choices = array(
		'page'       => __( 'Default Page template', 'dark-palette' ),
		'single'     => __( 'Default Single Post template', 'dark-palette' ),
		'attachment' => __( 'Attachment template', 'dark-palette' ),
	);
	if ( function_exists( 'get_block_templates' ) ) {
		foreach ( get_block_templates( array(), 'wp_template' ) as $template ) {
			if ( empty( $template->slug ) ) {
				continue;
			}
			$slug = sanitize_key( $template->slug );
			$title = ! empty( $template->title ) ? $template->title : $slug;
			$choices[ $slug ] = $title;
		}
	}
	return $choices;
}

function dark_palette_render_settings_page() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	$colors          = dark_palette_get_colors();
	$original_colors = dark_palette_get_original_colors();
	$disabled_ui     = dark_palette_get_disabled_ui();
	$scopes          = dark_palette_get_scopes();
	$template_choices = dark_palette_get_template_choices();
	$labels = array();
	foreach ( array_keys( $colors ) as $slug ) {
		$labels[ $slug ] = ucwords( str_replace( '-', ' ', $slug ) );
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Dark Palette', 'dark-palette' ); ?></h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'dark_palette_settings' ); ?>

			<h2><?php esc_html_e( 'Colour counterparts', 'dark-palette' ); ?></h2>
			<p><?php esc_html_e( 'The original theme colour is shown beside the colour that replaces it in dark mode.', 'dark-palette' ); ?></p>
			<table class="widefat striped dark-palette-colors" style="max-width:900px">
				<thead><tr><th><?php esc_html_e( 'Colour', 'dark-palette' ); ?></th><th><?php esc_html_e( 'Original colour', 'dark-palette' ); ?></th><th><?php esc_html_e( 'Dark counterpart', 'dark-palette' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( $labels as $slug => $label ) :
					$original = isset( $original_colors[ $slug ] ) ? $original_colors[ $slug ] : '';
				?>
				<tr>
					<th scope="row"><label for="dark-palette-dark-<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $label ); ?></label></th>
					<td>
						<span aria-hidden="true" style="display:inline-block;width:34px;height:34px;border:1px solid #8c8f94;border-radius:4px;vertical-align:middle;background:<?php echo esc_attr( $original ?: 'transparent' ); ?>"></span>
						<code><?php echo esc_html( $original ?: __( 'Not found', 'dark-palette' ) ); ?></code>
					</td>
					<td><input id="dark-palette-dark-<?php echo esc_attr( $slug ); ?>" type="color" name="<?php echo esc_attr( DARK_PALETTE_OPTION ); ?>[<?php echo esc_attr( $slug ); ?>]" value="<?php echo esc_attr( $colors[ $slug ] ); ?>"> <code><?php echo esc_html( $colors[ $slug ] ); ?></code></td>
				</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<h2><?php esc_html_e( 'Unavailable-state display', 'dark-palette' ); ?></h2>
			<table class="form-table" role="presentation"><tbody>
			<tr><th scope="row"><?php esc_html_e( 'Default behaviour', 'dark-palette' ); ?></th><td><select name="<?php echo esc_attr( DARK_PALETTE_UI_OPTION ); ?>[behavior]">
				<option value="hide" <?php selected( $disabled_ui['behavior'], 'hide' ); ?>><?php esc_html_e( 'Hide the toggle', 'dark-palette' ); ?></option>
				<option value="message" <?php selected( $disabled_ui['behavior'], 'message' ); ?>><?php esc_html_e( 'Show an information message', 'dark-palette' ); ?></option>
				<option value="disabled" <?php selected( $disabled_ui['behavior'], 'disabled' ); ?>><?php esc_html_e( 'Show a disabled toggle and message', 'dark-palette' ); ?></option>
			</select></td></tr>
			<tr><th scope="row"><label for="dark-palette-disabled-message"><?php esc_html_e( 'Default message', 'dark-palette' ); ?></label></th><td><textarea id="dark-palette-disabled-message" class="large-text" rows="3" name="<?php echo esc_attr( DARK_PALETTE_UI_OPTION ); ?>[message]"><?php echo esc_textarea( $disabled_ui['message'] ); ?></textarea><p class="description"><?php esc_html_e( 'Used unless an individual post or page supplies its own text.', 'dark-palette' ); ?></p></td></tr>
			</tbody></table>

			<h2><?php esc_html_e( 'Disable dark mode by context', 'dark-palette' ); ?></h2>
			<p><?php esc_html_e( 'These contexts are forced to light mode and use the unavailable-state display chosen above.', 'dark-palette' ); ?></p>
			<fieldset style="display:grid;gap:10px;max-width:760px">
			<?php
			$scope_labels = array(
				'archives' => __( 'All archive pages', 'dark-palette' ),
				'search' => __( 'Search results', 'dark-palette' ),
				'404' => __( '404 pages', 'dark-palette' ),
				'password_protected' => __( 'Password-protected posts and pages', 'dark-palette' ),
				'woocommerce_checkout' => __( 'WooCommerce checkout', 'dark-palette' ),
				'login' => __( 'WordPress login pages', 'dark-palette' ),
			);
			foreach ( $scope_labels as $key => $scope_label ) : ?>
				<label><input type="checkbox" name="<?php echo esc_attr( DARK_PALETTE_SCOPE_OPTION ); ?>[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( ! empty( $scopes[ $key ] ) ); ?>> <?php echo esc_html( $scope_label ); ?></label>
			<?php endforeach; ?>
			</fieldset>

			<h3><?php esc_html_e( 'Templates', 'dark-palette' ); ?></h3>
			<p><?php esc_html_e( 'Select templates whose singular pages should always remain in light mode.', 'dark-palette' ); ?></p>
			<fieldset style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:10px;max-width:900px">
			<?php foreach ( $template_choices as $slug => $title ) : ?>
				<label><input type="checkbox" name="<?php echo esc_attr( DARK_PALETTE_SCOPE_OPTION ); ?>[templates][]" value="<?php echo esc_attr( $slug ); ?>" <?php checked( in_array( $slug, $scopes['templates'], true ) ); ?>> <?php echo esc_html( $title ); ?> <code><?php echo esc_html( $slug ); ?></code></label>
			<?php endforeach; ?>
			</fieldset>

			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
