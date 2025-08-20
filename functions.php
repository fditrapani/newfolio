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
 * Enqueue Add navogation icons
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
 
	 $icon = sanitize_text_field( (string) $block['attrs']['icon'] );
 
	 // Parse fragment safely
	 $dom = new DOMDocument();
	 libxml_use_internal_errors( true );
	 $dom->loadHTML(
		 '<?xml encoding="utf-8" ?><div class="__wrap">'.$block_content.'</div>',
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
 
		 // Prepend <i data-lucide="..."> (Lucide JS will convert)
		 $i = $dom->createElement( 'i' );
		 $i->setAttribute( 'data-lucide', $icon );
		 $anchor->appendChild( $i );
		 $anchor->appendChild( $label );
	 }
 
	 // Ensure class for styling
	 $anchor->setAttribute(
		 'class',
		 trim( $anchor->getAttribute( 'class' ) . ' has-lucide-icon' )
	 );
 
	 $html = $dom->saveHTML( $wrap );
	 return preg_replace( '/^<div class="__wrap">|<\/div>$/', '', $html );
 }, 10, 2 );
 

 
