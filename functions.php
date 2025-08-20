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

/**
 * 1) Editor: enqueue your enhancer JS (no lucide in parent editor)
 */
add_action( 'enqueue_block_editor_assets', function () {
    wp_enqueue_script(
        'newfolio-navigation-icons',
        get_template_directory_uri() . '/assets/js/navigation-icons.js',
        [ 'wp-blocks', 'wp-element', 'wp-components', 'wp-editor', 'wp-hooks' ],
        filemtime( get_template_directory() . '/assets/js/navigation-icons.js' ),
        true
    );

    // Optional: pass the lucide UMD URL to your JS if it injects it into the iframe.
    wp_localize_script(
        'newfolio-navigation-icons',
        'newfolioNavIcons',
        [
            'lucideSrc' => 'https://unpkg.com/lucide@latest/dist/umd/lucide.js',
        ]
    );
} );

/**
 * 2) Frontend: enqueue lucide UMD once and initialize
 */
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

/**
 * 3) Frontend-only render filter: inject exactly one icon + wrap label
 *    - Skips admin/editor/REST/feeds to prevent doubles in the editor canvas or APIs
 *    - Idempotent: won’t insert if an icon (i[data-lucide] or svg.lucide) is already present
 */
add_filter( 'render_block', function( $block_content, $block ) {

    // Skip in the editor / REST / feeds
    if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || is_feed() ) {
        return $block_content;
    }

    // Only target core/navigation-link with an "icon" attribute
    if (
        empty( $block['blockName'] ) ||
        $block['blockName'] !== 'core/navigation-link' ||
        empty( $block['attrs']['icon'] )
    ) {
        return $block_content;
    }

    $icon = sanitize_text_field( $block['attrs']['icon'] );

    // Safely manipulate fragment
    $dom = new DOMDocument();
    libxml_use_internal_errors( true );
    $dom->loadHTML(
        '<?xml encoding="utf-8" ?><div class="__wrap">' . $block_content . '</div>',
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );
    libxml_clear_errors();

    $xpath  = new DOMXPath( $dom );
    $wrap   = $xpath->query( '//div[@class="__wrap"]' )->item( 0 );
    $anchor = $xpath->query( './/a', $wrap )->item( 0 ); // first link

    if ( ! $anchor ) {
        return $block_content; // nothing to do
    }

    // If an icon already exists, just ensure class and return
    $already_has_icon = $xpath->query(
        './/i[@data-lucide] | .//svg[contains(@class,"lucide")] | .//svg[@data-lucide]',
        $anchor
    )->length > 0;

    if ( $already_has_icon ) {
        $anchor->setAttribute(
            'class',
            trim( $anchor->getAttribute( 'class' ) . ' has-lucide-icon' )
        );

        $html = $dom->saveHTML( $wrap );
        return preg_replace( '/^<div class="__wrap">|<\/div>$/', '', $html );
    }

    // Build <i data-lucide="..."> and wrap existing content as label
    $iconEl = $dom->createElement( 'i' );
    $iconEl->setAttribute( 'data-lucide', $icon );

    $labelSpan = $dom->createElement( 'span' );
    $labelSpan->setAttribute( 'class', 'nav-label' );

    // Move existing children into the label span (preserves nested markup)
    while ( $anchor->firstChild ) {
        $labelSpan->appendChild( $anchor->firstChild );
    }

    // Prepend icon, then label
    $anchor->appendChild( $iconEl );
    $anchor->appendChild( $labelSpan );

    // Add styling class
    $anchor->setAttribute(
        'class',
        trim( $anchor->getAttribute( 'class' ) . ' has-lucide-icon' )
    );

    // Return fragment without wrapper
    $html = $dom->saveHTML( $wrap );
    return preg_replace( '/^<div class="__wrap">|<\/div>$/', '', $html );

}, 10, 2 );
