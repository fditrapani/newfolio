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

/**
 * Add preload and aggressive caching for Lucide icons to prevent flickering
 */
function newfolio_lucide_cache_script() {
	?>
	<script>
	(function() {
		// Preload Lucide script immediately
		var script = document.createElement('script');
		script.src = 'https://unpkg.com/lucide@latest/dist/umd/lucide.js';
		script.async = true;
		script.onload = function() {
			// Immediately create icons when script loads
			if (window.lucide && typeof window.lucide.createIcons === 'function') {
				window.lucide.createIcons();
				localStorage.setItem('lucide-cached', 'true');
			}
		};
		document.head.appendChild(script);
		
		// Also try to create icons on DOMContentLoaded as backup
		document.addEventListener('DOMContentLoaded', function() {
			if (window.lucide && typeof window.lucide.createIcons === 'function') {
				// Small delay to ensure all elements are ready
				setTimeout(function() {
					window.lucide.createIcons();
					localStorage.setItem('lucide-cached', 'true');
				}, 5);
			}
		});
		
		// Additional check for when page is fully loaded
		window.addEventListener('load', function() {
			if (window.lucide && typeof window.lucide.createIcons === 'function') {
				window.lucide.createIcons();
				localStorage.setItem('lucide-cached', 'true');
			}
		});
	})();
	</script>
	<?php
}
add_action( 'wp_head', 'newfolio_lucide_cache_script', 1 );

/** 1) Frontend: enqueue Lucide UMD and init with enhanced caching */
add_action( 'wp_enqueue_scripts', function () {
	// Don't enqueue the script again since we're preloading it
	// Just add the inline script for additional safety
	wp_add_inline_script(
		'jquery', // Use jQuery as dependency since it's usually loaded
		'
		(function() {
			// Multiple attempts to create icons
			function createIconsMultipleAttempts() {
				if (window.lucide && typeof window.lucide.createIcons === "function") {
					window.lucide.createIcons();
					localStorage.setItem("lucide-cached", "true");
					return true;
				}
				return false;
			}
			
			// Try immediately
			if (!createIconsMultipleAttempts()) {
				// Try on DOMContentLoaded
				document.addEventListener("DOMContentLoaded", function() {
					if (!createIconsMultipleAttempts()) {
						// Try with a small delay
						setTimeout(createIconsMultipleAttempts, 10);
					}
				});
				
				// Try on window load
				window.addEventListener("load", function() {
					createIconsMultipleAttempts();
				});
			}
		})();
		'
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

/**
 * Portfolio pieces
 * ====================================================================================
 */
add_action('init', function () {
	register_post_type('snap', [
	  'labels' => [
		'name'                     => _x('Snaps', 'Post type general name', 'newfolio'),
		'singular_name'            => _x('Snap', 'Post type singular name', 'newfolio'),
		'menu_name'                => _x('Portfolio', 'Admin Menu text', 'newfolio'),
		'name_admin_bar'           => _x('Snap', 'Add New on Toolbar', 'newfolio'),
		'add_new'                  => __('Add New', 'newfolio'),
		'add_new_item'             => __('Add New Snap', 'newfolio'),
		'new_item'                 => __('New Snap', 'newfolio'),
		'edit_item'                => __('Edit Snap', 'newfolio'),
		'view_item'                => __('View Snap', 'newfolio'),
		'all_items'                => __('All Snaps', 'newfolio'),
		'search_items'             => __('Search Snaps', 'newfolio'),
		'parent_item_colon'        => __('Parent Snaps:', 'newfolio'),
		'not_found'                => __('No snaps found.', 'newfolio'),
		'not_found_in_trash'       => __('No snaps found in Trash.', 'newfolio'),
		'archives'                 => __('Snap Archives', 'newfolio'),
		'attributes'               => __('Snap Attributes', 'newfolio'),
		'insert_into_item'         => __('Insert into snap', 'newfolio'),
		'uploaded_to_this_item'    => __('Uploaded to this snap', 'newfolio'),
		// Optional: rename “Featured image” to “Cover image” (appears in sidebar)
		'featured_image'           => _x('Cover image', 'Featured Image label', 'newfolio'),
		'set_featured_image'       => _x('Set cover image', '', 'newfolio'),
		'remove_featured_image'    => _x('Remove cover image', '', 'newfolio'),
		'use_featured_image'       => _x('Use as cover image', '', 'newfolio'),
		'filter_items_list'        => __('Filter snaps list', 'newfolio'),
		'items_list_navigation'    => __('Snaps list navigation', 'newfolio'),
		'items_list'               => __('Snaps list', 'newfolio'),
		'item_published'           => __('Snap published.', 'newfolio'),
		'item_published_privately' => __('Snap published privately.', 'newfolio'),
		'item_reverted_to_draft'   => __('Snap reverted to draft.', 'newfolio'),
		'item_scheduled'           => __('Snap scheduled.', 'newfolio'),
		'item_updated'             => __('Snap updated.', 'newfolio'),
	  ],
	  'public' => true,
	  'publicly_queryable' => true,
	  'show_in_rest' => true,
	  'menu_icon' => 'dashicons-portfolio',
	  'supports' => ['title','editor','excerpt','thumbnail','custom-fields','revisions'],
	  'rewrite' => ['slug' => 'work'],
	]);
  });
  
  add_filter('enter_title_here', function ($text, $post) {
	return ($post && $post->post_type === 'snap') ? __('Snap title…', 'newfolio') : $text;
  }, 10, 2);

  // Useful, crisp thumbnails for the grid
  add_action('after_setup_theme', function () {
	add_theme_support('post-thumbnails');
	add_image_size('snap-card', 900, 0, false); // large, unconstrained height
  });

  add_action('init', function () {
	register_block_style('core/post-template', [
	  'name'  => 'masonry',
	  'label' => 'Masonry Grid',
	]);
  });

  // Customize body class
  add_filter('body_class', function ($classes) {
    if (is_singular('snap')) {
        $classes[] = 'snap-single'; // your custom class
    }

    return $classes;
});

/**
 * Portfolio pieces
 * ====================================================================================
 */
add_filter('render_block', function ($block_content, $block) {
    // Only touch nav links, and only on the front page
    if (empty($block['blockName']) || $block['blockName'] !== 'core/navigation-link') {
        return $block_content;
    }
    if (!is_front_page()) {
        return $block_content;
    }

    // Get the link URL this block is rendering
    $url = $block['attrs']['url'] ?? '';
    if (!$url) {
        return $block_content;
    }

    // Normalize both URLs for comparison (absolute vs relative, trailing slashes)
    $home = trailingslashit(home_url('/'));
    $url_norm = trailingslashit((0 === strpos($url, 'http')) ? $url : home_url($url));

    // If this nav item points to the site root, mark it "current"
    if ($url_norm === $home) {
        // Add class to the <li class="wp-block-navigation-item ...">
        $block_content = preg_replace(
            '/<li\b([^>]*)class="([^"]*)"/',
            '<li$1class="$2 current-menu-item"',
            $block_content,
            1
        );

        // Add aria-current="page" to the anchor
        $block_content = preg_replace(
            '/<a\b(?![^>]*\baria-current=)([^>]*)>/',
            '<a$1 aria-current="page">',
            $block_content,
            1
        );
    }

    return $block_content;
}, 10, 2);

/**
 * Add custom body classes to block editor for specific templates
 * ====================================================================================
 */
add_action('enqueue_block_editor_assets', function () {
    // Add JavaScript to add body class
    wp_add_inline_script('wp-edit-post', '
        (function() {
            let isVisualEditor = true;
            let classAdded = false;
            
            // Function to add or remove body class based on editor state
            function updateEditorBodyClass() {
                try {
                    // Check if we\'re in the editor
                    if (!document.body) {
                        return;
                    }
                    
                    // Check if we\'re in visual editor mode
                    const visualEditor = document.querySelector(".edit-site-visual-editor__editor-canvas");
                    const codeEditor = document.querySelector(".edit-site-code-editor__body");
                    
                    if (visualEditor && !codeEditor) {
                        isVisualEditor = true;
                    } else if (codeEditor && !visualEditor) {
                        isVisualEditor = false;
                        // Remove class when switching to code editor
                        if (classAdded) {
                            classAdded = false;
                        }
                        return;
                    }
                    
                    // Only proceed if we\'re in visual editor mode
                    if (!isVisualEditor) {
                        return;
                    }
                    
					// Check if we\'re editing a post that uses the home template
                    const editorContent = document.querySelector(".edit-site-visual-editor__editor-canvas");
					
                    if (editorContent) {
						const iframeDocument = editorContent.contentDocument;
						const iframeBody = iframeDocument.querySelector("body");
						
						if (iframeBody) {
							const hasBlogClass = iframeBody.querySelector(".newfolio__content--blog-template");
							
							// Only add class if we have blog template elements AND haven\'t added it yet
							if (hasBlogClass && !classAdded) {
								// Add to main body for easier targeting
								if (iframeBody.classList && !iframeBody.classList.contains("editing-home-template")) {
									iframeBody.classList.add("editing-home-template");
									classAdded = true;
								}
							} else if (!hasBlogClass && classAdded) {
								// Remove class if blog template elements are no longer present
								if (iframeBody.classList && iframeBody.classList.contains("editing-home-template")) {
									iframeBody.classList.remove("editing-home-template");
									classAdded = false;
								}
							}
						}
                    }
                } catch (error) {
                    // Silently handle any errors
                    console.log("Editor body class update error:", error);
                }
            }
            
            // Run when DOM is ready
            if (document.readyState === "loading") {
                document.addEventListener("DOMContentLoaded", updateEditorBodyClass);
            } else {
                updateEditorBodyClass();
            }
            
            // Run when WordPress editor is ready
            if (typeof wp !== "undefined" && wp.domReady) {
                wp.domReady(updateEditorBodyClass);
            }
            
            // Listen for editor mode changes
            if (typeof wp !== "undefined" && wp.data) {
                wp.data.subscribe(function() {
                    const isVisualMode = wp.data.select("core/edit-post").isFeatureActive("visual-editor");
                    if (isVisualMode !== isVisualEditor) {
                        isVisualEditor = isVisualMode;
                        if (!isVisualMode) {
                            classAdded = false; // Reset when switching to code editor
                        }
                        updateEditorBodyClass();
                    }
                });
            }
            
            // Run periodically to catch late-loading elements (but with a limit)
            let attempts = 0;
            const maxAttempts = 10;
            const interval = setInterval(function() {
                updateEditorBodyClass();
                attempts++;
                if (attempts >= maxAttempts) {
                    clearInterval(interval);
                }
            }, 1000);
        })();
    ');
});

/*
* Making blog post cards fully clickable
*=========================================================== */

// functions.php

add_filter('render_block', function ($content, $block) {
    // Front-end only (skip editor & REST previews)
    if ( is_admin() || wp_is_json_request() ) {
        return $content;
    }

    // Fast bail if this block's HTML doesn't contain our marker class.
    if ( strpos($content, 'link-card') === false ) {
        return $content;
    }

    // Correct post for THIS rendered item (passed down by core/post-template).
    $post_id = (int) ( $block['context']['postId'] ?? 0 );

    // Fallback (rare): if context didn't flow, use the loop post.
    if ( ! $post_id ) {
        $loop_id = get_the_ID();
        if ( $loop_id ) {
            $post_id = (int) $loop_id;
        }
    }
    if ( ! $post_id ) {
        return $content;
    }

    $url = get_permalink( $post_id );
    if ( ! $url ) {
        return $content;
    }

    // Don’t double-insert.
    if ( strpos($content, 'link-card__overlay') !== false ) {
        return $content;
    }

    $overlay = sprintf(
        '<a class="link-card__overlay" href="%s" aria-hidden="true" tabindex="-1"></a>',
        esc_url($url)
    );

    // Insert right after the first opening tag (handles <div>, <section>, etc., even with whitespace/comments before it)
    $updated = preg_replace('/(<\s*[a-z0-9:-]+\b[^>]*>)/i', '$1' . $overlay, $content, 1);

    return $updated ?: $content;
}, 20, 2);
