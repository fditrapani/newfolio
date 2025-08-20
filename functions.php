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
 * Inline Lucide SVG for core/navigation-link (frontend only)
 * - First tries local cache in uploads: /wp-content/uploads/newfolio-lucide/<slug>.svg
 * - Then tries theme bundle: /wp-content/themes/newfolio/assets/lucide/<slug>.svg
 * - If missing, downloads once from unpkg and saves to uploads (then serves from cache)
 * - Falls back to <i data-lucide="…"> if all else fails
 */

/** Return filesystem path + public URL for our uploads cache dir. */
function newfolio_lucide_cache_dirs() {
    $up = wp_upload_dir();
    $dir = trailingslashit( $up['basedir'] ) . 'newfolio-lucide/';
    $url = trailingslashit( $up['baseurl'] ) . 'newfolio-lucide/';
    if ( ! is_dir( $dir ) ) {
        wp_mkdir_p( $dir );
    }
    return [ $dir, $url ];
}

/** Read an SVG string for a given slug from cache/theme or download+cache it. */
function newfolio_get_lucide_svg( $slug ) {
    $slug = sanitize_title( $slug ); // simple hardening
    if ( ! $slug ) return '';

    // 1) uploads cache
    list( $cache_dir ) = newfolio_lucide_cache_dirs();
    $cache_path = $cache_dir . $slug . '.svg';
    if ( file_exists( $cache_path ) ) {
        $svg = file_get_contents( $cache_path );
        if ( is_string( $svg ) && str_starts_with( trim( $svg ), '<svg' ) ) {
            return $svg;
        }
    }

    // 2) theme bundle (optional: add any icons you commonly use here)
    $theme_path = get_template_directory() . '/assets/lucide/' . $slug . '.svg';
    if ( file_exists( $theme_path ) ) {
        $svg = file_get_contents( $theme_path );
        if ( is_string( $svg ) && str_starts_with( trim( $svg ), '<svg' ) ) {
            // write-through to cache for speed next time
            @file_put_contents( $cache_path, $svg );
            return $svg;
        }
    }

    // 3) remote fetch once (unpkg: lucide-static)
    $url = 'https://unpkg.com/lucide-static/icons/' . rawurlencode( $slug ) . '.svg';
    $res = wp_remote_get( $url, [ 'timeout' => 4 ] );
    if ( ! is_wp_error( $res ) ) {
        $code = wp_remote_retrieve_response_code( $res );
        $body = wp_remote_retrieve_body( $res );
        if ( 200 === $code && is_string( $body ) && str_starts_with( trim( $body ), '<svg' ) ) {
            // cache it
            @file_put_contents( $cache_path, $body );
            return $body;
        }
    }

    return ''; // let caller fall back
}

/**
 * Frontend-only render filter: inject inline SVG (no flicker).
 * Skips editor/REST/feeds. Idempotent.
 */
add_filter( 'render_block', function ( $block_content, $block ) {

    if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || is_feed() ) {
        return $block_content;
    }

    if (
        empty( $block['blockName'] ) ||
        'core/navigation-link' !== $block['blockName'] ||
        empty( $block['attrs']['icon'] )
    ) {
        return $block_content;
    }

    $slug = sanitize_text_field( $block['attrs']['icon'] );
    $svg  = newfolio_get_lucide_svg( $slug );

    // Parse the fragment safely
    $dom = new DOMDocument();
    libxml_use_internal_errors( true );
    $dom->loadHTML(
        '<?xml encoding="utf-8" ?><div class="__wrap">' . $block_content . '</div>',
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );
    libxml_clear_errors();

    $xpath  = new DOMXPath( $dom );
    $wrap   = $xpath->query( '//div[@class="__wrap"]' )->item( 0 );
    $anchor = $xpath->query( './/a', $wrap )->item( 0 );
    if ( ! $anchor ) return $block_content;

    // If an icon already exists, just ensure class and return
    $has_icon = $xpath->query(
        './/i[@data-lucide] | .//svg[contains(@class,"lucide")] | .//svg[@data-lucide]',
        $anchor
    )->length > 0;

    if ( $has_icon ) {
        $anchor->setAttribute( 'class', trim( $anchor->getAttribute( 'class' ) . ' has-lucide-icon' ) );
        $html = $dom->saveHTML( $wrap );
        return preg_replace( '/^<div class="__wrap">|<\/div>$/', '', $html );
    }

    // Build label wrapper (keeps your hover behavior)
    $label = $dom->createElement( 'span' );
    $label->setAttribute( 'class', 'nav-label' );
    while ( $anchor->firstChild ) {
        $label->appendChild( $anchor->firstChild );
    }

    if ( $svg ) {
        // Inline the SVG: import as a real <svg> node
        $svgDom = new DOMDocument();
        libxml_use_internal_errors( true );
        // loadXML is safer for pure SVG
        if ( $svgDom->loadXML( $svg ) ) {
            $svgEl = $dom->importNode( $svgDom->documentElement, true );
            // Normalise attributes the way Lucide does
            $svgEl->setAttribute( 'class', trim( $svgEl->getAttribute( 'class' ) . ' lucide' ) );
            if ( ! $svgEl->hasAttribute( 'width' ) )  $svgEl->setAttribute( 'width',  '1em' );
            if ( ! $svgEl->hasAttribute( 'height' ) ) $svgEl->setAttribute( 'height', '1em' );
            $svgEl->setAttribute( 'aria-hidden', 'true' );
            $svgEl->setAttribute( 'focusable', 'false' );

            $anchor->appendChild( $svgEl );
        } else {
            // Fallback to <i> if import fails unexpectedly
            $i = $dom->createElement( 'i' );
            $i->setAttribute( 'data-lucide', $slug );
            $anchor->appendChild( $i );
        }
        libxml_clear_errors();
    } else {
        // Absolute fallback: <i data-lucide>
        $i = $dom->createElement( 'i' );
        $i->setAttribute( 'data-lucide', $slug );
        $anchor->appendChild( $i );
    }

    // Append the label
    $anchor->appendChild( $label );

    // Add styling class
    $anchor->setAttribute( 'class', trim( $anchor->getAttribute( 'class' ) . ' has-lucide-icon' ) );

    // Return without wrapper
    $html = $dom->saveHTML( $wrap );
    return preg_replace( '/^<div class="__wrap">|<\/div>$/', '', $html );

}, 10, 2 );
