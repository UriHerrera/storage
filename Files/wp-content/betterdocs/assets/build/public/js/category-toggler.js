(() => {
	"use strict";

	window.addEventListener("load", () => {
		const $ = jQuery;
		const $activeSidebarCategory = $(".betterdocs-sidebar-content .betterdocs-category-grid-wrapper .betterdocs-single-category-wrapper.active");

		function loadCategoryBody($body) {
			if ("1" === $body.attr("data-bd-loaded") || "1" === $body.attr("data-bd-loading")) {
				return $.Deferred().resolve().promise();
			}

			const config = window.betterdocsCategoryGridConfig || {};
			if (!config.ajax_url) {
				return $.Deferred().reject().promise();
			}

			$body.html('<ul class="betterdocs-skeleton" aria-hidden="true"><li><span class="betterdocs-skeleton-bar"></span></li><li><span class="betterdocs-skeleton-bar"></span></li><li><span class="betterdocs-skeleton-bar"></span></li><li><span class="betterdocs-skeleton-bar"></span></li><li><span class="betterdocs-skeleton-bar"></span></li></ul>');
			$body.attr("data-bd-loading", "1");

			const categoryIcon = $body.closest(".betterdocs-sidebar-layout-7").length ? "folder" : "";
			const request = $.post(config.ajax_url, {
				action: config.lazy_load_action || "betterdocs_lazy_category_body",
				term_id: $body.attr("data-bd-term-id"),
				kb_slug: config.kb_slug || "",
				multiple_kb: config.multiple_kb ? 1 : 0,
				category_icon: categoryIcon
			});
			const delay = $.Deferred();

			setTimeout(() => delay.resolve(), 1000);

			return $.when(request, delay.promise())
				.done((response) => {
					const payload = $.isArray(response) ? response[0] : response;
					if (payload && payload.success && payload.data && "string" === typeof payload.data.html) {
						$body.html(payload.data.html);
						$body.attr("data-bd-loaded", "1");
						$body.removeAttr("data-bd-lazy");
					}
				})
				.always(() => {
					$body.removeAttr("data-bd-loading");
				});
		}

		function setToggleState($button, expanded) {
			const $screenReaderText = $button.find(".screen-reader-text");
			const expandLabel = $button.data("expand-label") || $screenReaderText.data("expand-label");
			const collapseLabel = $button.data("collapse-label") || $screenReaderText.data("collapse-label");
			const label = expanded ? collapseLabel : expandLabel;

			$button.attr("aria-expanded", expanded ? "true" : "false");
			if (label) {
				$button.attr("aria-label", label);
			}
			if ($screenReaderText.length && label) {
				$screenReaderText.text(label);
			}
		}

		$activeSidebarCategory.addClass("show").find(".betterdocs-body").css("display", "block");
		$activeSidebarCategory.siblings().find(".betterdocs-body").css("display", "none");

		$(".betterdocs-category-grid-wrapper .betterdocs-category-collapse").each(function () {
			const $button = $(this);
			const $wrapper = $button.closest(".betterdocs-single-category-wrapper");
			const $body = $wrapper.children(".betterdocs-single-category-inner").children(".betterdocs-body").first();
			setToggleState($button, $body.is(":visible"));
		});

		$(document).on("click", ".betterdocs-category-grid-wrapper .betterdocs-category-collapse", function (event) {
			event.preventDefault();
			event.stopPropagation();

			const $button = $(this);
			const $wrapper = $button.closest(".betterdocs-single-category-wrapper");
			let $body = $wrapper.children(".betterdocs-single-category-inner").children(".betterdocs-body");
			if (!$body.length) {
				$body = $wrapper.find(".betterdocs-body").first();
			}
			if (!$body.length) {
				return;
			}

			const expanding = !$body.is(":visible");
			const lazy = "1" === $body.attr("data-bd-lazy");
			const loaded = "1" === $body.attr("data-bd-loaded");

			if (expanding && lazy && !loaded) {
				loadCategoryBody($body);
			}

			$body.stop(true, true)[expanding ? "slideDown" : "slideUp"]();
			$wrapper.toggleClass("show", expanding);
			setToggleState($button, expanding);
		});

		$(document).on("click", ".betterdocs-nested-category-title .betterdocs-category-toggle, .betterdocs-nested-category-title a[href=\"#\"]", function (event) {
			event.preventDefault();
			event.stopPropagation();

			const $control = $(this);
			const $title = $control.closest(".betterdocs-nested-category-title");
			const $list = $title.closest(".betterdocs-nested-category-wrapper").children(".betterdocs-nested-category-list");
			const expanded = $list.hasClass("active") || $list.is(":visible");
			const willExpand = !expanded;

			$list.stop(true, true)[willExpand ? "slideDown" : "slideUp"]("fast", "swing", function () {
				$(this).toggleClass("active", willExpand);
			});
			$title.toggleClass("is-expanded", willExpand);
			$list.attr("aria-hidden", willExpand ? "false" : "true");

			const $button = $title.find(".betterdocs-category-toggle").first();
			if ($button.length) {
				setToggleState($button, willExpand);
			}
		});

		const sidebar = document.querySelector(".betterdocs-sidebar-layout-2");
		if (sidebar && "IntersectionObserver" in window) {
			const lazyBodies = sidebar.querySelectorAll('.betterdocs-body[data-bd-lazy="1"]:not([data-bd-loaded="1"])');
			if (lazyBodies.length) {
				const observer = new IntersectionObserver((entries) => {
					entries.forEach((entry) => {
						if (entry.isIntersecting) {
							observer.unobserve(entry.target);
							const $body = $(entry.target);
							loadCategoryBody($body).done(() => {
								$body.css("display", "block");
							});
						}
					});
				}, { rootMargin: "200px 0px" });

				lazyBodies.forEach((body) => observer.observe(body));
			}
		}
	});
})();
