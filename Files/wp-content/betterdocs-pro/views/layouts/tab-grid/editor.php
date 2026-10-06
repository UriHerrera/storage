<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<script>
    jQuery(document).ready(function($) {
        let tabs = jQuery('.betterdocs-tabs-nav-wrapper');
        tabs?.map((index) => {
            jQuery(jQuery(tabs[index]).children('a:first-child')).addClass('active');
            jQuery(jQuery(jQuery(tabs[index]) ).next()).children('div:first-child').addClass('active');
            jQuery(jQuery(tabs[index]).children()).on('click', function(e) {
                e.preventDefault();
                let listChildrens = jQuery(this).parent().children();
                let current_mkb_id = jQuery(this).attr('data-toggle-target');
                let contentDom = jQuery(jQuery(jQuery(this).parent()).next());
                listChildrens?.map((value) => {
                    if( $(listChildrens[value]).hasClass('active') ){
                        $(listChildrens[value]).removeClass('active');
                    }
                })
                jQuery(this).addClass('active');
                contentDom.children(`.betterdocs-tabgrid-content-wrapper[data-tab_target="${current_mkb_id}"]`).addClass('active').siblings().removeClass('active');
            });
        });
    });
</script>
