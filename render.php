<?php
/**
 * Server-side render for the Appearance Toggle block.
 *
 * @var array $attributes Block attributes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$show_labels = ! empty( $attributes['showLabels'] );
$show_auto   = ! array_key_exists( 'showAuto', $attributes ) || ! empty( $attributes['showAuto'] );
$label       = isset( $attributes['label'] ) ? sanitize_text_field( $attributes['label'] ) : __( 'Appearance', 'dark-palette' );
if ( 'Appearance' === $label ) {
	$label = __( 'Appearance', 'dark-palette' );
}

$disabled = function_exists( 'dark_palette_is_disabled' ) && dark_palette_is_disabled();
$disabled_ui = $disabled && function_exists( 'dark_palette_get_effective_disabled_ui' ) ? dark_palette_get_effective_disabled_ui() : array( 'behavior' => 'hide', 'message' => '' );
if ( $disabled && 'hide' === $disabled_ui['behavior'] ) { return; }


$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class'             => 'dark-palette-appearance-toggle',
		'data-show-labels'  => $show_labels ? 'true' : 'false',
		'data-show-auto'    => $show_auto ? 'true' : 'false',
	)
);

$options = array(
	'light' => array(
		'label' => __( 'Light', 'dark-palette' ),
		'icon'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"></circle><path d="M12 2v2M12 20v2M4.93 4.93l1.42 1.42M17.65 17.65l1.42 1.42M2 12h2M20 12h2M4.93 19.07l1.42-1.42M17.65 6.35l1.42-1.42"></path></svg>',
	),
	'dark' => array(
		'label' => __( 'Dark', 'dark-palette' ),
		'icon'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.4 15.4A8.5 8.5 0 0 1 8.6 3.6 8.5 8.5 0 1 0 20.4 15.4Z"></path></svg>',
	),
	'auto' => array(
		'label' => __( 'Auto', 'dark-palette' ),
		'icon'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="13" rx="2"></rect><path d="M8 21h8M12 17v4"></path></svg>',
	),
);
?>
<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( $disabled && 'message' === $disabled_ui['behavior'] ) : ?>
		<p class="dark-palette-appearance-toggle__notice" role="status"><?php echo esc_html( $disabled_ui['message'] ); ?></p>
	<?php else : ?>
	<span class="dark-palette-appearance-toggle__label"><?php echo esc_html( $label ); ?></span>
	<div class="dark-palette-appearance-toggle__controls" role="group" aria-label="<?php echo esc_attr( $label ); ?>">
		<?php foreach ( $options as $value => $option ) : ?>
			<?php if ( 'auto' === $value && ! $show_auto ) { continue; } ?>
			<button class="dark-palette-appearance-toggle__button" type="button" <?php echo $disabled ? 'disabled aria-disabled="true"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> data-appearance="<?php echo esc_attr( $value ); ?>" aria-pressed="false" title="<?php echo esc_attr( $option['label'] ); ?>">
				<span class="dark-palette-appearance-toggle__icon"><?php echo $option['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<span class="dark-palette-appearance-toggle__text"><?php echo esc_html( $option['label'] ); ?></span>
			</button>
		<?php endforeach; ?>
	</div>
	<?php if ( $disabled && 'disabled' === $disabled_ui['behavior'] ) : ?><span class="dark-palette-appearance-toggle__notice" role="status"><?php echo esc_html( $disabled_ui['message'] ); ?></span><?php endif; ?>
	<?php endif; ?>
</div>
