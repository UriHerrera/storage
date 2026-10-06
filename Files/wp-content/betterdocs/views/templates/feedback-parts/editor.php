<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<script>
    jQuery(document).ready(function($) {
        var $ = jQuery;
        let formLink = $(".feedback-form-link");
        let formModal = $("#betterdocs-form-modal");
        let formModalContent = $("#betterdocs-form-modal .modal-content");
        //select all the a tag with name equal to modal
        formLink.click(function(e) {
            e.preventDefault();
            formModal.fadeIn(500);
        });

        //if outside of modal content is clicked
        $(document).mouseup(function(e) {
            if (
            !formModalContent.is(e.target) &&
            formModalContent.has(e.target).length === 0
            ) {
                formModal.fadeOut();
            }
        });

        //if close button is clicked
        $(".betterdocs-modalwindow .close").click(function(e) {
            e.preventDefault();
            formModal.fadeOut(500);
        });
    });
</script>
