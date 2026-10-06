<?php
    if ( ! defined( 'WPINC' ) ) {
        die;
    }

    // Marks that the attachments metabox was actually rendered and submitted, so
    // save_docs_attachments() can tell "the user emptied the metabox" from "this
    // save path never included the metabox" (Quick Edit / Bulk Edit / REST /
    // programmatic save) and only clear the stored attachments for the former.
    wp_nonce_field( 'betterdocs_attachments_metabox', 'betterdocs_attachments_nonce' );
?>

<div id="betterdocs-attachment-overlay"></div>
<div id="betterdocs-attachment-root"></div>
