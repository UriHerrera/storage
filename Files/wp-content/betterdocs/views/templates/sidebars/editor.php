<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<script>
    jQuery(document).ready(function($) {
        let listSidebarCatTitles = $(
            ".betterdocs-sidebar-content .betterdocs-sidebar-list-wrapper .betterdocs-sidebar-list .betterdocs-category-header"
        );
        let catTitleList = $(
            ".betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-single-category-wrapper .betterdocs-category-header"
        );
        let currentActiveCat = $(
            ".betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-single-category-wrapper.active"
        );
        let listSidebarCurrentCat = $(
            ".betterdocs-sidebar-content .betterdocs-sidebar-list-wrapper .betterdocs-sidebar-list.active"
        );

        let nestedCategoriesToggle = $(".betterdocs-nested-category-title");

        // When "Lazy Load Sidebar Docs & Subcategories" is on, non-active
        // categories render an empty body (data-bd-lazy="1") and the docs are
        // fetched on click. That fetch normally lives in category-toggler.js,
        // which binds on window.load and does NOT run inside the Elementor editor
        // preview — so replicate it here. The AJAX returns the category's full
        // doc list, so all docs display (no cap). No-ops for non-lazy bodies.
        function loadLazyBody($body) {
            if (!$body.length || $body.attr("data-bd-lazy") !== "1") return;
            if ($body.attr("data-bd-loaded") === "1" || $body.attr("data-bd-loading") === "1") return;
            var cfg = window.betterdocsCategoryGridConfig || {};
            // The config object is not reliably printed into the Elementor editor
            // preview, so fall back to the PHP-injected admin-ajax URL. Without this
            // the editor fetch bailed on `!cfg.ajax_url` and no docs loaded, even
            // though the front end (where the config IS present) worked fine.
            var ajaxUrl    = cfg.ajax_url || <?php echo wp_json_encode( esc_url_raw( admin_url( 'admin-ajax.php' ) ) ); ?>;
            var lazyAction = cfg.lazy_load_action || "betterdocs_lazy_category_body";
            if (!ajaxUrl) return;
            var category_icon = $body.closest(".betterdocs-sidebar-layout-7").length ? "folder" : "";
            $body.attr("data-bd-loading", "1");
            $.post(ajaxUrl, {
                action:        lazyAction,
                term_id:       $body.attr("data-bd-term-id"),
                category_icon: category_icon
            }).done(function (resp) {
                if (resp && resp.success && resp.data && typeof resp.data.html === "string") {
                    $body.html(resp.data.html).attr("data-bd-loaded", "1").removeAttr("data-bd-lazy");
                }
            }).always(function () {
                $body.removeAttr("data-bd-loading");
            });
        }

        function categoryAccordion() {
            let $parentThis = this;
            currentActiveCat
                .addClass("show")
                .find(".betterdocs-body")
                .css("display", "block");
            currentActiveCat
                .siblings()
                .find(".betterdocs-body")
                .css("display", "none");
            catTitleList.on("click", function (e) {
                e.preventDefault();
                let $parentCat = jQuery(e.target).closest(
                    ".betterdocs-single-category-wrapper"
                );
                let $body = $parentCat
                    .children(".betterdocs-single-category-inner")
                    .children(".betterdocs-body");
                if (!$body.length) {
                    $body = $parentCat.find(".betterdocs-body").first();
                }
                loadLazyBody($body);
                $body.slideToggle();
                $parentCat
                    .addClass("active")
                    .toggleClass("show")
                    .siblings()
                    .removeClass("active")
                    .find(".betterdocs-body")
                    .slideUp();
            });
        }
        categoryAccordion();

        function categoryListAccordion() {
            let $parentThis = this;
            listSidebarCurrentCat
                .find(".betterdocs-body")
                .css("display", "block");
            listSidebarCurrentCat
                .siblings()
                .find(".betterdocs-body")
                .css("display", "none");
            listSidebarCatTitles.on("click", function (e) {
                console.log(e.target);
                e.preventDefault();
                let $parentCat = jQuery(e.target).closest(
                    ".betterdocs-sidebar-list"
                );
                $parentCat.find(".betterdocs-body").slideToggle();
                $parentCat
                    .toggleClass("active")
                    .siblings()
                    .removeClass("active")
                    .find(".betterdocs-body")
                    .slideUp();
            });
        }
        categoryListAccordion();

        function nestedCategoryToggler() {
            nestedCategoriesToggle.on("click", function (e) {
                e.preventDefault();
                jQuery(this).children(".toggle-arrow").toggle();
                jQuery(this).next(".betterdocs-nested-category-list").slideToggle();
            });
        }
        nestedCategoryToggler();
    });
</script>


