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
	// Enqueue theme stylesheet with version for cache busting
	$theme_version = wp_get_theme()->get( 'Version' );
	wp_enqueue_style( 'newfolio-style', get_template_directory_uri() . '/style.css', array(), $theme_version );
	
	// Add preload for critical resources
	if ( ! is_admin() ) {
		// Preload Google Fonts
		wp_enqueue_style( 'google-fonts-preload', 'https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=DM+Serif+Display&display=swap', array(), null );
	}
}

add_action( 'wp_enqueue_scripts', 'newfolio_scripts' );

/**
 * Google Fonts are now handled in newfolio_scripts() for better performance
 */

/**
 * Add security headers
 */
function newfolio_security_headers() {
	if ( ! is_admin() ) {
		// Add Content Security Policy for better security
		header( "Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://unpkg.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self' https://unpkg.com;" );
		
		// Add X-Frame-Options to prevent clickjacking
		header( 'X-Frame-Options: SAMEORIGIN' );
		
		// Add X-Content-Type-Options to prevent MIME type sniffing
		header( 'X-Content-Type-Options: nosniff' );
		
		// Add Referrer Policy
		header( 'Referrer-Policy: strict-origin-when-cross-origin' );
	}
}
add_action( 'send_headers', 'newfolio_security_headers' );

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
		'grid', 'list', 'share', 'bookmark', 'tag', 'filter', 'sort-asc', 'sort-desc',
		'github', 'twitter', 'facebook', 'instagram', 'linkedin', 'youtube', 'rss',
		'globe', 'map', 'navigation', 'compass', 'location', 'phone-call', 'message-circle'
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
		// Add CSS to hide icon elements until they're ready
		var style = document.createElement('style');
		style.textContent = `
			.has-lucide-icon i[data-lucide] {
				opacity: 0;
				transition: opacity 0.2s ease-in-out;
			}
			.has-lucide-icon i[data-lucide].lucide-ready {
				opacity: 1;
			}
			.has-lucide-icon svg.lucide {
				opacity: 1;
			}
		`;
		document.head.appendChild(style);
		
		// Check if Lucide is already cached
		var isCached = localStorage.getItem('lucide-cached') === 'true';
		var lucideLoaded = false;
		
		// Function to create icons and mark them as ready
		function createIcons() {
			if (window.lucide && typeof window.lucide.createIcons === 'function') {
				// Create icons
				window.lucide.createIcons();
				
				// Mark all icon elements as ready
				var iconElements = document.querySelectorAll('.has-lucide-icon i[data-lucide]');
				iconElements.forEach(function(el) {
					el.classList.add('lucide-ready');
				});
				
				// Cache the success
				localStorage.setItem('lucide-cached', 'true');
				lucideLoaded = true;
				return true;
			}
			return false;
		}
		
		// Function to load Lucide script
		function loadLucideScript() {
			if (lucideLoaded) return;
			
			var script = document.createElement('script');
			script.src = 'https://unpkg.com/lucide@latest/dist/umd/lucide.js';
			script.async = true;
			script.onload = function() {
				// Try to create icons immediately after load
				setTimeout(createIcons, 10);
			};
			document.head.appendChild(script);
		}
		
		// If cached, try to create icons immediately
		if (isCached) {
			// Try to create icons if Lucide is already available
			if (!createIcons()) {
				// If not available, load it
				loadLucideScript();
			}
		} else {
			// Load Lucide script
			loadLucideScript();
		}
		
		// Backup attempts on various events
		['DOMContentLoaded', 'load'].forEach(function(event) {
			document.addEventListener(event, function() {
				if (!lucideLoaded) {
					setTimeout(createIcons, 50);
				}
			});
		});
		
			// Final fallback - check periodically for a short time
	var attempts = 0;
	var maxAttempts = 5; // Reduced from 10 to 5 for better performance
	var interval = setInterval(function() {
		if (lucideLoaded || attempts >= maxAttempts) {
			clearInterval(interval);
			return;
		}
		createIcons();
		attempts++;
	}, 100); // Reduced from 200ms to 100ms for faster response
	})();
	</script>
	<?php
}
add_action( 'wp_head', 'newfolio_lucide_cache_script', 1 );

/**
 * Add preload link for Lucide script to improve loading performance
 */
function newfolio_lucide_preload() {
	// Only add preload on frontend
	if ( ! is_admin() && ! wp_is_json_request() ) {
		echo '<link rel="preload" href="https://unpkg.com/lucide@latest/dist/umd/lucide.js" as="script" crossorigin="anonymous">' . "\n";
	}
}
add_action( 'wp_head', 'newfolio_lucide_preload', 0 );

/**
 * Add resource hints for better performance
 */
function newfolio_resource_hints( $hints, $relation_type ) {
	if ( 'dns-prefetch' === $relation_type ) {
		$hints[] = '//fonts.googleapis.com';
		$hints[] = '//fonts.gstatic.com';
		$hints[] = '//unpkg.com';
	}
	
	if ( 'preconnect' === $relation_type ) {
		$hints[] = 'https://fonts.googleapis.com';
		$hints[] = 'https://fonts.gstatic.com';
		$hints[] = 'https://unpkg.com';
	}
	
	return $hints;
}
add_filter( 'wp_resource_hints', 'newfolio_resource_hints', 10, 2 );

/** 1) Frontend: Additional safety script for icon creation */
add_action( 'wp_enqueue_scripts', function () {
	// Add a lightweight backup script that works with the main caching script
	wp_add_inline_script(
		'jquery', // Use jQuery as dependency since it's usually loaded
		'
		(function() {
			// Only run if the main script hasn\'t already handled icons
			if (document.querySelector(".has-lucide-icon i[data-lucide]:not(.lucide-ready)")) {
				// Wait a bit longer and try again
				setTimeout(function() {
					if (window.lucide && typeof window.lucide.createIcons === "function") {
						window.lucide.createIcons();
						
						// Mark elements as ready
						var iconElements = document.querySelectorAll(".has-lucide-icon i[data-lucide]");
						iconElements.forEach(function(el) {
							el.classList.add("lucide-ready");
						});
					}
				}, 500);
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
	
	// Parse fragment safely with additional security measures
	$dom = new DOMDocument();
	libxml_use_internal_errors( true );
	
	// Use a more secure approach with proper encoding
	$dom->encoding = 'UTF-8';
	$dom->loadHTML(
		'<?xml encoding="utf-8" ?><div class="__wrap">' . $escaped_block_content . '</div>',
		LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOENT
	);
	libxml_clear_errors();

	$xpath = new DOMXPath( $dom );
	$wrap  = $xpath->query( '//div[@class="__wrap"]' )->item( 0 );
	
	// More specific anchor selection for better security
	$anchor = $wrap ? $xpath->query( './/a[contains(@class, "wp-block-navigation-item__content") or contains(@class, "wp-block-navigation-link__content")]', $wrap )->item( 0 ) : null;
	
	// Fallback to any anchor if specific ones not found
	if ( ! $anchor && $wrap ) {
		$anchor = $xpath->query( './/a', $wrap )->item( 0 );
	}
	
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


/*
* Change the comment form title
* =========================================================== */
add_filter( 'comment_form_defaults', function( $defaults ) {
    $defaults['title_reply'] = __( 'Leave a comment', 'your-textdomain' );
    return $defaults;
});

// Force Post Comments Form default title to "Leave a comment" (editor + front end)
add_filter( 'register_block_type_args', function( $args, $block_type ) {
	if ( $block_type !== 'core/post-comments-form' ) {
		return $args;
	}

	$args['attributes'] = $args['attributes'] ?? array();

	// Ensure the attribute exists, then change its default.
	$attr = $args['attributes']['titleReply'] ?? array( 'type' => 'string' );
	$attr['default'] = __( 'Leave a comment', 'your-textdomain' );
	$args['attributes']['titleReply'] = $attr;

	// (Optional) keep related labels consistent.
	foreach ( [
		'titleReplyTo' => __( 'Leave a comment to %s', 'your-textdomain' ),
		'labelSubmit'  => __( 'Post comment', 'your-textdomain' ),
	] as $key => $value ) {
		$attr = $args['attributes'][ $key ] ?? array( 'type' => 'string' );
		$attr['default'] = $value;
		$args['attributes'][ $key ] = $attr;
	}

	return $args;
}, 10, 2 );


/*
* Conditionally show comments pagination template part
* =========================================================== */
add_filter( 'render_block', function( $block_content, $block ) {
    // Only process template-part blocks with comments-pagination slug
    if ( $block['blockName'] !== 'core/template-part' || 
         ( $block['attrs']['slug'] ?? '' ) !== 'comments-pagination' ) {
        return $block_content;
    }
    
    // Get the current post
    $post = get_post();
    if ( ! $post ) {
        return '';
    }
    
    // Check if there are more comments than the per-page limit
    $total_comments = get_comments_number( $post->ID );
    $comments_per_page = get_option( 'comments_per_page' );
    
    // Only show pagination if there are more comments than the per-page limit
    if ( $total_comments <= $comments_per_page ) {
        return '';
    }
    
    return $block_content;
}, 10, 2 );

/* Customize comment form
* =========================================================== */

// 1) Remove the built-in cancel link beside the title
add_filter( 'cancel_comment_reply_link', '__return_false' );

// 2) Append our own cancel link beside the submit button
add_filter( 'comment_form_submit_field', function( $submit_field, $args ) {
	$cancel_text = ! empty( $args['cancel_reply_link'] )
		? $args['cancel_reply_link']
		: __( 'Cancel reply', 'your-textdomain' );

	// Core's comment-reply.js looks for this exact ID and toggles visibility.
	$cancel_link = sprintf(
		'<a rel="nofollow" id="cancel-comment-reply-link" href="#" style="display:none" onclick="return addComment.cancelForm();">%s</a>',
		esc_html( $cancel_text )
	);

	// Inject our link just before the closing </p> of the submit wrapper.
	if ( false !== strpos( $submit_field, '</p>' ) ) {
		$submit_field = str_replace( '</p>', ' ' . $cancel_link . '</p>', $submit_field );
	} else {
		// Fallback: just append it.
		$submit_field .= ' ' . $cancel_link;
	}

	return $submit_field;
}, 10, 2 );

// 3) Ensure the core JS that handles moving/canceling replies is loaded
add_action( 'wp_enqueue_scripts', function () {
	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
});

add_filter( 'comment_form_defaults', function( $defaults ) {
	$req_hint = ' <span class="required" aria-hidden="true">*</span><span class="screen-reader-text">' .
		esc_html__( 'required', 'your-textdomain' ) . '</span>';

	$defaults['comment_field'] =
		'<p class="comment-form-comment">' .
			'<label for="comment">' . esc_html__( 'Message', 'your-textdomain' ) . $req_hint . '</label>' .
			'<textarea id="comment" name="comment" cols="45" rows="8" required aria-required="true"></textarea>' .
		'</p>';

	return $defaults;
});


// LIGHT DARK MODE

// Define theme defaults in one place
function get_newfolio_theme_defaults() {
	return array(
		'home' => 'dark',
		'search' => 'dark',
		'archive' => 'dark',
		'singular' => 'dark',
		'single-snap' => 'light',
		'404' => 'light',
		'front-page' => 'light',
		'index' => 'light',
		'page' => 'light',
		'blog-post-alt' => 'dark',
	);
}

// Register a per-template meta key to store the choice.
add_action( 'init', function () {
	register_post_meta(
		'wp_template',
		'newfolio_theme',
		[
			'type'         => 'string',
			'single'       => true,
			'default'      => 'light',
			'show_in_rest' => [
				'schema' => [
					'type' => 'string',
					'enum' => ['light', 'dark'],
					'context' => ['view', 'edit'],
				],
			],
			'auth_callback'=> function () { return current_user_can( 'edit_theme_options' ); },
		]
	);
	
	// Also register for wp_template_part
	register_post_meta(
		'wp_template_part',
		'newfolio_theme',
		[
			'type'         => 'string',
			'single'       => true,
			'default'      => 'light',
			'show_in_rest' => [
				'schema' => [
					'type' => 'string',
					'enum' => ['light', 'dark'],
					'context' => ['view', 'edit'],
				],
			],
			'auth_callback'=> function () { return current_user_can( 'edit_theme_options' ); },
		]
	);
} );

// Ensure meta fields are saved when templates are updated
add_action( 'rest_after_insert_wp_template', function( $post, $request, $creating ) {
	if ( isset( $request['meta']['newfolio_theme'] ) ) {
		update_post_meta( $post->ID, 'newfolio_theme', $request['meta']['newfolio_theme'] );
	}
}, 10, 3 );

add_action( 'rest_after_insert_wp_template_part', function( $post, $request, $creating ) {
	if ( isset( $request['meta']['newfolio_theme'] ) ) {
		update_post_meta( $post->ID, 'newfolio_theme', $request['meta']['newfolio_theme'] );
	}
}, 10, 3 );

// Force meta fields to be included in REST API responses
add_filter( 'rest_prepare_wp_template', function( $response, $post, $request ) {
	$meta = get_post_meta( $post->ID, 'newfolio_theme', true );
	if ( $meta ) {
		$response->data['meta'] = $response->data['meta'] ?? array();
		$response->data['meta']['newfolio_theme'] = $meta;
	}
	return $response;
}, 10, 3 );

add_filter( 'rest_prepare_wp_template_part', function( $response, $post, $request ) {
	$meta = get_post_meta( $post->ID, 'newfolio_theme', true );
	if ( $meta ) {
		$response->data['meta'] = $response->data['meta'] ?? array();
		$response->data['meta']['newfolio_theme'] = $meta;
	}
	return $response;
}, 10, 3 );


// AJAX handler for getting theme
add_action( 'wp_ajax_get_newfolio_theme', function() {
	$post_type = sanitize_text_field( $_POST['post_type'] );
	$template_slug = sanitize_text_field( $_POST['template_slug'] );
	
	// Permission check
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_die( 'Permission denied' );
	}
	
	// Find the template by slug
	$templates = get_posts( array(
		'post_type' => $post_type,
		'name' => $template_slug,
		'posts_per_page' => 1,
		'post_status' => 'publish'
	) );
	
	if ( empty( $templates ) ) {
		wp_send_json_error( 'Template not found' );
	}
	
	$template = $templates[0];
	$theme = get_post_meta( $template->ID, 'newfolio_theme', true );
	
	// Get what the default should be
	$defaults = get_newfolio_theme_defaults();
	$default_theme = isset($defaults[$template_slug]) ? $defaults[$template_slug] : 'light';
	
	// Debug: Log what we're returning
	error_log( "AJAX v2: Template '{$template_slug}' - Stored: '{$theme}', Default: '{$default_theme}'" );
	
	if ( $theme ) {
		wp_send_json_success( $theme );
	} else {
		wp_send_json_success( 'light' ); // Default value
	}
} );


// Set default theme values for templates (only run once)
add_action( 'init', function() {
	// Check if we've already run this
	if ( get_option( 'newfolio_defaults_applied' ) ) {
		return;
	}
	
	error_log( "DEFAULTS v2: Function started" );
	
	$defaults = get_newfolio_theme_defaults();
	
	$templates = get_posts( array(
		'post_type' => 'wp_template',
		'posts_per_page' => -1,
		'post_status' => 'publish'
	) );
	
	error_log( "DEFAULTS v2: Found " . count($templates) . " templates" );
	
	$applied_defaults = false;
	foreach ( $templates as $template ) {
		$current_theme = get_post_meta( $template->ID, 'newfolio_theme', true );
		
		error_log( "DEFAULTS v2: Template '{$template->post_name}' - Stored: '{$current_theme}', Default: '" . (isset($defaults[$template->post_name]) ? $defaults[$template->post_name] : 'light') . "'" );
		
		// Only set default if no theme is currently stored
		if ( empty( $current_theme ) ) {
			$default_theme = isset($defaults[$template->post_name]) ? $defaults[$template->post_name] : 'light';
			update_post_meta( $template->ID, 'newfolio_theme', $default_theme );
			error_log( "DEFAULTS v2: Applied '{$default_theme}' to '{$template->post_name}'" );
			$applied_defaults = true;
		}
	}
	
	// Mark as completed
	if ( $applied_defaults ) {
		update_option( 'newfolio_defaults_applied', true );
		error_log( "DEFAULTS v2: Marked as completed" );
	}
}, 10 );


// Create singular template post if it doesn't exist and set default
add_action( 'init', function() {
	$template_file = get_template_directory() . '/templates/singular.html';
	if ( file_exists( $template_file ) ) {
		$templates = get_posts( array(
			'post_type' => 'wp_template',
			'name' => 'singular',
			'posts_per_page' => 1,
			'post_status' => 'publish'
		) );
		
		if ( empty( $templates ) ) {
			$template_post = wp_insert_post( array(
				'post_type' => 'wp_template',
				'post_name' => 'singular',
				'post_title' => 'Singular',
				'post_status' => 'publish',
				'post_content' => file_get_contents( $template_file )
			) );
			
			if ( $template_post && ! is_wp_error( $template_post ) ) {
				// Don't set any theme - let the defaults function handle it
			}
		}
	}
}, 1 );

// Apply defaults when templates are reset or updated
add_action( 'rest_insert_wp_template', function( $post, $request, $creating ) {
	$current_theme = get_post_meta( $post->ID, 'newfolio_theme', true );
	
	// If no theme is set, apply default
	if ( empty( $current_theme ) ) {
		$defaults = get_newfolio_theme_defaults();
		
		$default_theme = isset($defaults[$post->post_name]) ? $defaults[$post->post_name] : 'light';
		update_post_meta( $post->ID, 'newfolio_theme', $default_theme );
	}
}, 10, 3 );

// Add dark mode class to body on front-end
add_filter( 'body_class', function( $classes ) {
	// Only apply theme classes on specific page types
	if ( is_home() ) {
		$template = 'home';
	} elseif ( is_front_page() ) {
		$template = 'front-page';
	} elseif ( is_single() ) {
		// Check if this is a snap post type
		if ( is_singular('snap') ) {
			$template = 'single-snap';
		} else {
			$template = 'singular';
		}
	} elseif ( is_page() ) {
		$template = 'page';
	} elseif ( is_archive() ) {
		$template = 'archive';
	} elseif ( is_search() ) {
		$template = 'search';
	} elseif ( is_404() ) {
		$template = '404';
	} else {
		// Don't apply theme classes for other page types
		return $classes;
	}
	
	// Find the template post
	$templates = get_posts( array(
		'post_type' => 'wp_template',
		'name' => $template,
		'posts_per_page' => 1,
		'post_status' => 'publish'
	) );
	
	if ( ! empty( $templates ) ) {
		$template_post = $templates[0];
		$theme = get_post_meta( $template_post->ID, 'newfolio_theme', true );
		
		if ( $theme === 'dark' ) {
			$classes[] = 'newfolio-darkmode';
		}
	}
	
	return $classes;
} );

// Enqueue ONE editor script (Site Editor only).
add_action( 'enqueue_block_editor_assets', function () {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( $screen && $screen->base !== 'site-editor' ) return;

	wp_enqueue_script(
		'newfolio-template-theme-dropdown',
		get_stylesheet_directory_uri() . '/assets/js/template-theme-dropdown.js',
		[ 'wp-data' ],
		filemtime( get_stylesheet_directory() . '/assets/js/template-theme-dropdown.js' ),
		true
	);
} );