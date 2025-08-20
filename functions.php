<?php

if ( ! function_exists( 'newfolio_support' ) ) :
	function newfolio_support()  {

		// Adding support for core block visual styles.
		add_theme_support( 'wp-block-styles' );

		// Enqueue editor styles.
		add_editor_style( 'style.css' );
	}
	add_action( 'after_setup_theme', 'newfolio_support' );
endif;

/**
 * Enqueue scripts and styles.
 * ====================================================================================
 */
function newfolio_scripts() {
	// Enqueue theme stylesheet.
	wp_enqueue_style( 'newfolio-style', get_template_directory_uri() . '/style.css', array(), wp_get_theme()->get( 'Version' ) );
}

add_action( 'wp_enqueue_scripts', 'newfolio_scripts' );

/**
 * Enqueue Add custom fonts.
 * ====================================================================================
 */
function enqueue_google_fonts() {
    wp_enqueue_style(
        'google-fonts',
        'https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=DM+Serif+Display&display=swap',
        array(),
        null
    );
}
add_action('wp_enqueue_scripts', 'enqueue_google_fonts');

/**
 * Security helper functions
 * ====================================================================================
 */

/**
 * Validate and sanitize icon slug for Lucide icons
 * 
 * @param string $icon The icon slug to validate
 * @return string|false Validated icon slug or false if invalid
 */
function newfolio_validate_icon_slug( $icon ) {
	// Basic validation
	if ( empty( $icon ) || ! is_string( $icon ) ) {
		return false;
	}
	
	// Sanitize the input
	$icon = sanitize_text_field( $icon );
	
	// Convert to lowercase and trim
	$icon = strtolower( trim( $icon ) );
	
	// Only allow alphanumeric characters, hyphens, and underscores
	// This matches Lucide icon naming convention
	if ( ! preg_match( '/^[a-z0-9-_]+$/', $icon ) ) {
		return false;
	}
	
	// Additional validation: must be reasonable length and not contain suspicious patterns
	if ( strlen( $icon ) > 50 || 
		 strpos( $icon, 'script' ) !== false || 
		 strpos( $icon, 'javascript' ) !== false ||
		 strpos( $icon, 'on' ) === 0 ) { // Block event handlers
		return false;
	}
	
	// List of common Lucide icon names for additional validation
	// This is a subset - you can expand this list
	$valid_icons = array(
		'home', 'search', 'user', 'settings', 'menu', 'close', 'arrow-right', 'arrow-left',
		'chevron-down', 'chevron-up', 'chevron-right', 'chevron-left', 'mail', 'phone',
		'heart', 'star', 'download', 'upload', 'edit', 'trash', 'plus', 'minus',
		'check', 'x', 'info', 'alert-circle', 'alert-triangle', 'help-circle',
		'calendar', 'clock', 'map-pin', 'link', 'external-link', 'lock', 'unlock',
		'eye', 'eye-off', 'camera', 'image', 'video', 'music', 'file', 'folder',
		'grid', 'list', 'share', 'bookmark', 'tag', 'filter', 'sort-asc', 'sort-desc'
	);
	
	// If strict validation is enabled, only allow known icons
	// Comment out this check if you want to allow any valid format
	// if ( ! in_array( $icon, $valid_icons, true ) ) {
	//     return false;
	// }
	
	return $icon;
}

/**
 * Safely escape HTML attributes
 * 
 * @param string $value The value to escape
 * @return string Escaped value
 */
function newfolio_escape_attr( $value ) {
	return esc_attr( $value );
}

/**
 * Enqueue Add navigation icons
 * ====================================================================================
 */
 
 /** 1) Frontend: enqueue Lucide UMD and init */
 add_action( 'wp_enqueue_scripts', function () {
	 wp_enqueue_script(
		 'lucide-icons',
		 'https://unpkg.com/lucide@latest/dist/umd/lucide.js',
		 [],
		 null,
		 true
	 );
	 wp_add_inline_script(
		 'lucide-icons',
		 'document.addEventListener("DOMContentLoaded",function(){ if(window.lucide){ lucide.createIcons(); } });'
	 );
 } );
 
 /** 2) Editor: enqueue the minimal JS for attribute + sidebar + iframe conversion */
 add_action( 'enqueue_block_editor_assets', function () {
	 wp_enqueue_script(
		 'newfolio-navigation-icons',
		 get_template_directory_uri() . '/assets/js/navigation-icons.js',
		 [ 'wp-blocks', 'wp-element', 'wp-components', 'wp-editor', 'wp-hooks' ],
		 filemtime( get_template_directory() . '/assets/js/navigation-icons.js' ),
		 true
	 );
 
	 // Pass Lucide UMD URL so JS can load it into the editor iframe
	 wp_localize_script(
		 'newfolio-navigation-icons',
		 'newfolioNavIcons',
		 [ 'lucideSrc' => 'https://unpkg.com/lucide@latest/dist/umd/lucide.js' ]
	 );
 } );
 
 /**
  * 3) Frontend-only render filter:
  *    - Injects <i data-lucide="..."> once (idempotent)
  *    - Wraps label in <span class="nav-label">...</span>
  *    - Adds has-lucide-icon class
  */
 add_filter( 'render_block', function ( $block_content, $block ) {
 
	 // Skip editor/REST/feeds to avoid doubles in Gutenberg canvas and APIs
	 if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || is_feed() ) {
		 return $block_content;
	 }
 
	 // Only apply to navigation links that have an icon (attribute may be empty)
	 if (
		 empty( $block['blockName'] ) ||
		 $block['blockName'] !== 'core/navigation-link' ||
		 ! isset( $block['attrs']['icon'] )
	 ) {
		 return $block_content;
	 }
 
	 // Validate and sanitize the icon
	 $icon = newfolio_validate_icon_slug( $block['attrs']['icon'] );
	 
	 // If icon is invalid, return original content
	 if ( $icon === false ) {
		 return $block_content;
	 }
 
	 // Additional security: escape the block content before DOM manipulation
	 $escaped_block_content = wp_kses_post( $block_content );
	 
	 // Parse fragment safely
	 $dom = new DOMDocument();
	 libxml_use_internal_errors( true );
	 $dom->loadHTML(
		 '<?xml encoding="utf-8" ?><div class="__wrap">' . $escaped_block_content . '</div>',
		 LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
	 );
	 libxml_clear_errors();
 
	 $xpath  = new DOMXPath( $dom );
	 $wrap   = $xpath->query( '//div[@class="__wrap"]' )->item( 0 );
	 $anchor = $wrap ? $xpath->query( './/a', $wrap )->item( 0 ) : null;
	 if ( ! $anchor ) return $block_content;
 
	 // Already has an icon? (i[data-lucide] or an SVG previously converted)
	 $has_icon = $xpath->query(
		 './/i[@data-lucide] | .//svg[contains(@class,"lucide")] | .//svg[@data-lucide]',
		 $anchor
	 )->length > 0;
 
	 if ( ! $has_icon && $icon !== '' ) {
		 // Wrap existing link contents into .nav-label
		 $label = $dom->createElement( 'span' );
		 $label->setAttribute( 'class', 'nav-label' );
		 while ( $anchor->firstChild ) {
			 $label->appendChild( $anchor->firstChild );
		 }
 
		 // Prepend <i data-lucide="..."> (Lucide JS will convert) - NOW SECURE
		 $i = $dom->createElement( 'i' );
		 $i->setAttribute( 'data-lucide', newfolio_escape_attr( $icon ) );
		 $anchor->appendChild( $i );
		 $anchor->appendChild( $label );
	 }
 
	 // Ensure class for styling
	 $current_class = $anchor->getAttribute( 'class' );
	 $new_class = trim( $current_class . ' has-lucide-icon' );
	 $anchor->setAttribute( 'class', newfolio_escape_attr( $new_class ) );
 
	 $html = $dom->saveHTML( $wrap );
	 return preg_replace( '/^<div class="__wrap">|<\/div>$/', '', $html );
 }, 10, 2 );
 