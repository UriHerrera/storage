<?php
    if ( ! defined( 'WPINC' ) ) {
        die;
    }

    // Marks that the related-articles metabox was actually rendered and submitted,
    // so save_related_articles() can tell "the user emptied the metabox" from
    // "this save path never included the metabox" (Quick Edit / Bulk Edit / REST /
    // programmatic save) and only clear the stored set for the former.
    wp_nonce_field( 'betterdocs_related_articles_metabox', 'betterdocs_related_articles_nonce' );
?>
<div id="betterdocs-related-articles-root"></div>
