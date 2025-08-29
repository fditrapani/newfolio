<?php
/**
 * The main template file
 *
 * @package Newfolio
 */

get_header(); ?>

<main id="main" class="site-main">
    <div class="container">
        <?php if ( have_posts() ) : ?>
            <header class="page-header">
                <h1 class="page-title">
                    <?php
                    if ( is_home() && ! is_front_page() ) {
                        single_post_title();
                    } elseif ( is_archive() ) {
                        the_archive_title();
                    } else {
                        _e( 'Posts', 'newfolio' );
                    }
                    ?>
                </h1>
            </header>

            <div class="posts-grid">
                <?php while ( have_posts() ) : the_post(); ?>
                    <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                        <header class="entry-header">
                            <?php if ( has_post_thumbnail() ) : ?>
                                <div class="post-thumbnail">
                                    <a href="<?php the_permalink(); ?>">
                                        <?php the_post_thumbnail( 'medium' ); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                            
                            <h2 class="entry-title">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h2>
                        </header>

                        <div class="entry-summary">
                            <?php the_excerpt(); ?>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>

            <?php
            the_posts_pagination( array(
                'prev_text' => __( 'Previous', 'newfolio' ),
                'next_text' => __( 'Next', 'newfolio' ),
            ) );
            ?>

        <?php else : ?>
            <div class="no-posts">
                <h1><?php _e( 'Nothing Found', 'newfolio' ); ?></h1>
                <p><?php _e( 'It looks like nothing was found at this location.', 'newfolio' ); ?></p>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php get_footer(); ?>
