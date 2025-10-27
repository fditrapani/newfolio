<?php
   /**
    * Title: Snap
    * Slug: newfolio/snap
    * Categories: images, text
    * Description: Display structured data for Snap layouts
    */
   ?>
   <!-- wp:group {"align":"full", className":"snap-container"} -->
   <div class="wp-block-group alignfull snap-container">
        <!-- wp:group {"className":"snap-images"} -->
        <div class="wp-block-group snap-images">
            <!-- wp:image -->
                <figure class="wp-block-image"><img alt=""/></figure>
            <!-- /wp:image -->
        </div>
        <!-- /wp:group -->  

        <!-- wp:group {"className":"snap-description"} -->
        <div class="wp-block-group snap-description">
            <!-- wp:heading {"level":2} -->
                <h2>Project</h2>
            <!-- /wp:heading -->
            <!-- wp:paragraph {"fontSize":"medium"} -->
                <p class="has-medium-font-size">Description</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:group -->  
    </div>
   <!-- /wp:group -->