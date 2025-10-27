<?php
/**
 * Dynamic render for snap Layout block.
 *
 * @param array  $attributes Block attributes.
 * @param string $content    InnerBlocks content (gallery + text).
 * @return string
 */
function newfolio_render_snap_layout_block( $attributes, $content ) {
	ob_start();
	?>
	<div class="snap-layout snap-container">
		<?php echo $content; ?>
	</div>
	<?php
	return ob_get_clean();
}
