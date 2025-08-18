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
 */
function newfolio_scripts() {
	// Enqueue theme stylesheet.
	wp_enqueue_style( 'newfolio-style', get_template_directory_uri() . '/style.css', array(), wp_get_theme()->get( 'Version' ) );
}

add_action( 'wp_enqueue_scripts', 'newfolio_scripts' );

/**
 * Enqueue Add custom fonts.
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
