<?php

namespace WPDeveloper\BetterDocs\Utils;

// Helper utilities mix per-language URL detection (read-only $_GET reads),
// dynamic alphabet-letter / glossary queries composed via $wpdb->prepare,
// and meta-key term lookups that are core to BetterDocs functionality.
// phpcs:disable WordPress.Security.NonceVerification.Recommended
// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:disable PluginCheck.Security.DirectDB.UnescapedDBParameter
// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_tax_query
// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key
// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_query

use function BetterLinksPro\Dependencies\GuzzleHttp\json_decode;
use function WPML\PHP\Logger\error;

class Helper extends Base {

	/**
	 * Mask an API key for safe display.
	 *
	 * Prefix-aware: when the key carries a recognizable provider prefix
	 * (OpenAI sk-/sk-proj-, Anthropic sk-ant-/sk-ant-api03-, Gemini AIza) that
	 * prefix is kept visible so an admin can tell which provider/key is set,
	 * then a fixed 8-asterisk block, then the last 4 chars. Keys without a known
	 * prefix fall back to first 3 + 8 asterisks + last 4. The asterisk count is
	 * always fixed so the real key length is never leaked.
	 */
	public static function mask_api_key( $key ) {
		if ( ! is_string( $key ) || $key === '' ) {
			return '';
		}
		$key = trim( $key );
		if ( $key === '' ) {
			return '';
		}

		// Longest prefixes first so sk-proj-/sk-ant- win over the bare sk-.
		$prefixes = array( 'sk-ant-api03-', 'sk-ant-', 'sk-proj-', 'sk-', 'AIza' );
		foreach ( $prefixes as $prefix ) {
			if ( strncmp( $key, $prefix, strlen( $prefix ) ) === 0
				&& strlen( $key ) >= strlen( $prefix ) + 4 ) {
				return $prefix . str_repeat( '*', 8 ) . substr( $key, -4 );
			}
		}

		if ( strlen( $key ) < 8 ) {
			return str_repeat( '*', strlen( $key ) );
		}
		return substr( $key, 0, 3 ) . str_repeat( '*', 8 ) . substr( $key, -4 );
	}

	/**
	 * Drop the scheme + host from an absolute URL so it stays same-origin.
	 *
	 * Returns the ROOT-RELATIVE form of $url ("/wp-admin/admin-ajax.php",
	 * "/wp-json/wp/v2/docs?search=foo", "/subdir/..." for a WP install in a
	 * sub-directory) so the browser resolves it against the CURRENT page's origin
	 * instead of the host baked into the URL. Path, query and fragment are kept
	 * verbatim — only scheme+host are dropped — so sub-directory, multisite and
	 * plain-permalink (?rest_route=) URLs all survive intact. A URL with no usable
	 * path is returned unchanged.
	 *
	 * This is the origin-agnostic primitive behind frontend_ajax_url(); callers
	 * whose output is embedded on a THIRD-PARTY site (e.g. the Instant Answer
	 * cross-domain snippet) must keep the absolute URL and simply not call this.
	 *
	 * @param string $url Absolute URL.
	 * @return string Root-relative URL, or $url unchanged when it has no path.
	 */
	public static function relative_url( $url ) {
		if ( ! is_string( $url ) || $url === '' ) {
			return $url;
		}

		$parts = wp_parse_url( $url );

		if ( ! is_array( $parts ) || empty( $parts['path'] ) ) {
			return $url;
		}

		$relative = $parts['path'];

		if ( isset( $parts['query'] ) && $parts['query'] !== '' ) {
			$relative .= '?' . $parts['query'];
		}

		if ( isset( $parts['fragment'] ) && $parts['fragment'] !== '' ) {
			$relative .= '#' . $parts['fragment'];
		}

		return $relative;
	}

	/**
	 * Same-origin admin-ajax URL for front-end requests (live search, etc.).
	 *
	 * admin_url() always resolves to the configured Site Address host, so when a
	 * knowledge base is served on a HOST different from WP's Site Address — a
	 * subdomain (e.g. faq.example.com), a domain alias, or a reverse proxy — the
	 * AJAX request becomes cross-origin and is silently blocked or redirected by
	 * the browser (the live search then returns no results).
	 *
	 * Emitting a ROOT-RELATIVE path ("/wp-admin/admin-ajax.php", or
	 * "/subdir/wp-admin/admin-ajax.php" for a WP install in a sub-directory) lets
	 * the browser resolve it against the CURRENT page's origin, so the request
	 * always stays same-origin regardless of the Site Address. The path component
	 * is taken verbatim from admin_url(), so sub-directory and multisite install
	 * paths are preserved; only the scheme+host is dropped. When the parsed path
	 * is empty, or in the admin area, the absolute URL is returned unchanged so
	 * nothing else is affected.
	 *
	 * Override with the `betterdocs_frontend_ajax_url` filter if a site genuinely
	 * needs an absolute or a different endpoint.
	 *
	 * @return string Root-relative admin-ajax path on the front end, else the absolute URL.
	 */
	public static function frontend_ajax_url() {
		$ajax_url = admin_url( 'admin-ajax.php' );

		if ( ! is_admin() ) {
			$ajax_url = self::relative_url( $ajax_url );
		}

		/**
		 * Filter the front-end admin-ajax URL used by BetterDocs live search.
		 *
		 * @param string $ajax_url Root-relative path (front end) or absolute URL.
		 */
		return apply_filters( 'betterdocs_frontend_ajax_url', $ajax_url );
	}

	/**
	 * Resolve the WPML-translated base slug of a taxonomy for the CURRENT language.
	 *
	 * WPML registers each translatable taxonomy's rewrite slug as a string named
	 * "URL <taxonomy> tax slug" in the "WordPress" domain (e.g. "URL doc_tag tax slug").
	 * BetterDocs stores only the default-language slug in its settings, so routing and
	 * term links must read the translated value back here. Returns the trimmed default
	 * slug unchanged when WPML is inactive or the string has no translation.
	 *
	 * @param string $taxonomy     Taxonomy key, e.g. 'doc_tag'.
	 * @param string $default_slug Default-language base slug from settings.
	 * @return string Translated base slug for the active language (falls back to default).
	 */
	public static function wpml_translated_tax_slug( $taxonomy, $default_slug ) {
		$default_slug = trim( (string) $default_slug, '/' );

		if ( $default_slug === '' || ! has_filter( 'wpml_translate_single_string' ) ) {
			return $default_slug;
		}

		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPML-owned filter; name must be used verbatim.
		$translated = apply_filters( 'wpml_translate_single_string', $default_slug, 'WordPress', 'URL ' . $taxonomy . ' tax slug' );
		$translated = trim( (string) $translated, '/' );

		return $translated !== '' ? $translated : $default_slug;
	}

	public static function get_plugins( $plugin_basename = null ) {
		if ( ! function_exists( 'get_plugins' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugins = get_plugins();
		return $plugin_basename == null ? $plugins : isset( $plugins[ $plugin_basename ] );
	}

	public static function is_plugin_active( $plugin_basename ) {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active( $plugin_basename );
	}

	/**
	 * Whether an SEO plugin already emits FAQPage schema on the current page.
	 *
	 * True only when Yoast or Rank Math is active AND its FAQ block is present
	 * in the post's content, so BetterDocs can skip its own FAQPage JSON-LD and
	 * avoid duplicate structured data. Defaults to the queried object when no
	 * post is given.
	 *
	 * @param int|\WP_Post|null $post
	 * @return bool
	 */
	public static function seo_plugin_outputs_faq_schema( $post = null ) {
		if ( null === $post ) {
			$post = get_queried_object();
		}

		$post = get_post( $post );
		if ( ! $post instanceof \WP_Post ) {
			return false;
		}

		if ( self::is_plugin_active( 'wordpress-seo/wp-seo.php' ) && has_block( 'yoast/faq-block', $post ) ) {
			return true;
		}

		if ( self::is_plugin_active( 'seo-by-rank-math/rank-math.php' ) && has_block( 'rank-math/faq-block', $post ) ) {
			return true;
		}

		// Extension seam for Pro / other SEO integrations.
		return (bool) apply_filters( 'betterdocs_seo_plugin_outputs_faq_schema', false, $post );
	}

	public static function get_tax( $tax = '' ) {
		global $wp_query;

		if ( is_tax( 'knowledge_base' ) ) {
			$_taxes = $wp_query->tax_query->queried_terms;
			if ( array_key_exists( 'doc_category', $_taxes ) ) {
				$tax = 'doc_category';
			} else {
				$tax = 'knowledge_base';
			}
		} elseif ( is_tax( 'doc_category' ) ) {
			$tax = 'doc_category';
		} elseif ( is_tax( 'doc_tag' ) ) {
			$tax = 'doc_tag';
		}

		return $tax;
	}

	public function is_templates() {
		global $wp_query;
		$slug = betterdocs()->settings->get( 'encyclopedia_root_slug', 'encyclopedia' );

		$tax = $this->get_tax();
		if ( is_post_type_archive( 'docs' ) || $tax === 'knowledge_base' || $tax === 'doc_category' || $tax === 'doc_tag' || is_singular( 'docs' ) || is_tax( 'glossaries' ) ) {
			return true;
		}

		if ( isset( $wp_query->query['pagename'] ) && $wp_query->query['pagename'] === $slug ) {
			return true;
		}

		return false;
	}

	public function is_el_templates() {
		$_return_val = betterdocs()->editor->get( 'elementor' )->is_templates();

		if ( $_return_val !== null ) {
			return $_return_val;
		}

		$this->is_templates();
	}

	/**
	 * Which tab to show.
	 *
	 * 1. Drag and Drop UI
	 * 2. Post List UI
	 *
	 * * 1. dnd
	 * * 2. classic
	 *
	 * look into views/admin/docs-ui directory to know more.
	 *
	 * @return string
	 */
	public static function admin_tab() {
		$admin_ui = 'grid';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only admin UI selection, no state change.
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only admin UI selection, no state change.
		$mode = isset( $_GET['mode'] ) ? sanitize_text_field( wp_unslash( $_GET['mode'] ) ) : '';
		if ( $page === 'betterdocs-admin' && ! empty( $mode ) ) {
			$admin_ui = $mode === 'grid' ? 'grid' : 'list';
		}

		return $admin_ui;
	}

	public static function is_active( $prev, $current, $class = 'active' ) {
		if ( $current == $prev ) {
			return $class;
		}

		return '';
	}

	public function get_users( $args ) {
		$cache_key = 'betterdocs_cache_admin_user_roles';
		$users     = betterdocs()->database->get_cache( $cache_key );

		if ( false === $users ) {
			$users = get_users( $args );
			betterdocs()->database->set_cache( $cache_key, $users );
		}

		return $users;
	}

	/**
	 * Normalize Menu Array
	 * Menu creator helper
	 *
	 * @since 2.5.0
	 *
	 * @param string $title
	 * @param string $slug
	 * @param string $cap
	 * @param array  $callback
	 *
	 * @return array
	 */
	public static function normalize_menu( $title, $slug, $cap = 'edit_docs', $callback = null, $optional = [] ) {
		$args = [
			'page_title' => $title,
			'menu_title' => $title,
			'capability' => $cap,
			'menu_slug'  => $slug
		];

		if ( $callback != null ) {
			$args['callback'] = $callback;
		}

		return wp_parse_args( $optional, $args );
	}

	/**
	 * Check if the current theme is a block theme.
	 *
	 * @since x.x.x
	 * @return bool
	 */
	public function current_theme_is_fse_theme() {
		if ( function_exists( 'wp_is_block_theme' ) ) {
			return (bool) wp_is_block_theme();
		}
		if ( function_exists( 'gutenberg_is_fse_theme' ) ) {
			return (bool) gutenberg_is_fse_theme();
		}

		return false;
	}

	protected static function is_assoc_array( $array ) {
		return array_keys( $array ) !== range( 0, count( $array ) - 1 );
	}

	public static function merge( &$array1, &$array2 ) {
		$merged = $array1;

		foreach ( $array2 as $key => &$value ) {
			if ( is_array( $value ) && self::is_assoc_array( $value ) && isset( $merged[ $key ] ) && is_array( $merged[ $key ] ) ) {
				$merged[ $key ] = self::merge( $merged[ $key ], $value );
			} elseif ( is_array( $value ) && isset( $merged[ $key ] ) && is_array( $merged[ $key ] ) ) {
				$merged[ $key ] = array_merge( $merged[ $key ], $value );
			} else {
				$merged[ $key ] = $value;
			}
		}

		return $merged;
	}

	public static function get_custom_excerpt( $content, $numOfWords ) {
		$content      = strip_shortcodes( $content );
		$content      = wp_strip_all_tags( $content );
		$words        = explode( ' ', $content );
		$excerptWords = array_slice( $words, 0, $numOfWords );
		$excerpt      = implode( ' ', $excerptWords );
		if ( count( $words ) > $numOfWords ) {
			$excerpt .= '...';
		}
		return $excerpt;
	}

	/**
	 * Get current language from various multilingual plugins
	 *
	 * @return string|null Current language code
	 */
	public static function get_current_language() {
		$current_language = null;

		// WPML Support
		if ( is_plugin_active( 'sitepress-multilingual-cms/sitepress.php' ) ) {
			global $sitepress;
			if ( $sitepress && $sitepress->is_setup_complete() ) {
				$current_language = defined( 'ICL_LANGUAGE_CODE' ) ? ICL_LANGUAGE_CODE : $sitepress->get_current_language();
			}
		}
		// Polylang Support
		elseif ( function_exists( 'pll_current_language' ) ) {
			$current_language = pll_current_language();
		}
		// qTranslate-X Support
		elseif ( function_exists( 'qtranxf_getLanguage' ) ) {
			$current_language = qtranxf_getLanguage();
		}
		// Weglot Support
		elseif ( function_exists( 'weglot_get_current_language' ) ) {
			$current_language = weglot_get_current_language();
		}
		// TranslatePress Support
		elseif ( class_exists( 'TRP_Translate_Press' ) && function_exists( 'trp_get_current_language' ) ) {
			$current_language = trp_get_current_language();
		}

		return $current_language;
	}

	/**
	 * Check if any multilingual plugin is active
	 *
	 * @return bool
	 */
	public static function is_multilingual_active() {
		return is_plugin_active( 'sitepress-multilingual-cms/sitepress.php' ) ||
			   function_exists( 'pll_current_language' ) ||
			   function_exists( 'qtranxf_getLanguage' ) ||
			   function_exists( 'weglot_get_current_language' ) ||
			   ( class_exists( 'TRP_Translate_Press' ) && function_exists( 'trp_get_current_language' ) );
	}

	/**
	 * Configured/active languages from whichever multilingual plugin is present.
	 *
	 * Returns a list of { value, label } pairs (language code + display name).
	 * Used to populate the optional language selector in the Write-with-AI modal;
	 * returns an empty array when no multilingual plugin is active so the
	 * selector stays hidden. Mirrors the Pro cross-domain language options.
	 *
	 * @return array<int,array{value:string,label:string}>
	 */
	public static function get_active_languages() {
		$options = array();

		// WPML
		if ( is_plugin_active( 'sitepress-multilingual-cms/sitepress.php' ) ) {
			global $sitepress;
			if ( $sitepress && method_exists( $sitepress, 'get_active_languages' ) ) {
				$active_languages = $sitepress->get_active_languages();
				if ( is_array( $active_languages ) ) {
					foreach ( $active_languages as $code => $lang ) {
						$options[] = array(
							'value' => (string) $code,
							'label' => isset( $lang['native_name'] ) ? $lang['native_name'] : (string) $code,
						);
					}
				}
			}
		} elseif ( function_exists( 'pll_languages_list' ) ) {
			// Polylang
			$languages = pll_languages_list( array( 'fields' => array() ) );
			if ( is_array( $languages ) ) {
				foreach ( $languages as $lang ) {
					if ( is_object( $lang ) && isset( $lang->slug ) ) {
						$options[] = array(
							'value' => (string) $lang->slug,
							'label' => isset( $lang->name ) ? $lang->name : (string) $lang->slug,
						);
					}
				}
			}
		}

		/**
		 * Filter the language options exposed to the Write-with-AI modal.
		 *
		 * @param array $options List of { value, label } language pairs.
		 */
		return apply_filters( 'betterdocs_active_languages', $options );
	}

	/**
	 * Check if we should apply language filtering
	 * Only apply on frontend or when specifically requested
	 *
	 * @return bool
	 */
	public static function should_apply_language_filtering() {
		// Don't apply language filtering in admin context unless it's a frontend request
		if ( is_admin() ) {
			// Allow language filtering for REST API requests that are frontend-facing
			if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
				// Check if this is a frontend REST request (not admin)
				$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
				// Don't filter admin REST requests for glossaries management
				if ( strpos( $request_uri, '/wp/v2/glossaries' ) !== false ) {
					return false; // Don't filter admin glossaries management
				}
			}
			return false; // Don't filter other admin requests
		}

		// Apply filtering on frontend
		return true;
	}

	/**
	 * Get current admin language for multilingual sites
	 * This is specifically for admin context where we need to detect
	 * the language being used for editing terms/posts
	 *
	 * @return string|null Current admin language code
	 */
	public static function get_current_admin_language() {
		$current_language = null;

		// Explicit language passed by the admin client takes priority.
		// Covers AJAX (POST) and REST/admin requests (GET) where WPML may
		// otherwise resolve to the site's default language instead of the
		// admin UI language.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- read-only UI language hint, sanitized; not a state-changing form submission.
		if ( isset( $_POST['lang'] ) && ! empty( $_POST['lang'] ) ) {
			return self::sanitize_language_code( wp_unslash( $_POST['lang'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- see note above.
		}

		// Limit GET handling to admin/REST contexts so a frontend ?lang= switch
		// doesn't hijack admin meta-key resolution.
		if ( isset( $_GET['lang'] ) && ! empty( $_GET['lang'] )
			&& ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) ) {
			return self::sanitize_language_code( wp_unslash( $_GET['lang'] ) );
		}

		// WPML Support - Admin language detection
		if ( is_plugin_active( 'sitepress-multilingual-cms/sitepress.php' ) ) {
			global $sitepress;
			if ( $sitepress && $sitepress->is_setup_complete() ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only language detection from URL.
				$tag_id = isset( $_GET['tag_ID'] ) ? (int) $_GET['tag_ID'] : 0;
				// For term editing, check if we have a specific term language
				if ( $tag_id && function_exists( 'wpml_get_language_information' ) ) {
					$term_info = wpml_get_language_information( null, $tag_id );
					if ( ! is_wp_error( $term_info ) && $term_info && isset( $term_info['language_code'] ) ) {
						$current_language = $term_info['language_code'];
					}

				}

				// Check for language parameter in URL
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only language detection from URL.
				if ( ! $current_language && isset( $_GET['lang'] ) ) {
					$current_language = sanitize_text_field( wp_unslash( $_GET['lang'] ) );
				}

				// Check WPML admin language cookie (persists during AJAX)
				if ( ! $current_language && isset( $_COOKIE['_icl_current_admin_language'] ) ) {
					$current_language = sanitize_text_field( wp_unslash( $_COOKIE['_icl_current_admin_language'] ) );
				}

				// Fallback to admin language or current language
				if ( ! $current_language ) {
					$current_language = defined( 'ICL_LANGUAGE_CODE' ) ? ICL_LANGUAGE_CODE : $sitepress->get_current_language();
				}
			}
		}
		// Polylang Support - Admin language detection
		elseif ( function_exists( 'pll_current_language' ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only language detection from URL.
			$tag_id = isset( $_GET['tag_ID'] ) ? (int) $_GET['tag_ID'] : 0;
			// For term editing, get language from term ID
			if ( $tag_id && function_exists( 'pll_get_term_language' ) ) {
				$term_lang = pll_get_term_language( $tag_id );
				if ( $term_lang ) {
					$current_language = $term_lang;
				}
			}

			// Check for language parameter in URL
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only language detection from URL.
			if ( ! $current_language && isset( $_GET['lang'] ) ) {
				$current_language = sanitize_text_field( wp_unslash( $_GET['lang'] ) );
			}

			// Fallback to current admin language
			if ( ! $current_language ) {
				$current_language = pll_current_language( 'slug' );
			}
		}
		// Other multilingual plugins
		elseif ( function_exists( 'qtranxf_getLanguage' ) ) {
			$current_language = qtranxf_getLanguage();
		}
		elseif ( function_exists( 'weglot_get_current_language' ) ) {
			$current_language = weglot_get_current_language();
		}
		elseif ( class_exists( 'TRP_Translate_Press' ) && function_exists( 'trp_get_current_language' ) ) {
			$current_language = trp_get_current_language();
		}

		return self::sanitize_language_code( $current_language );
	}

	/**
	 * Normalize a language code to the character set real language codes use
	 * (`en`, `en_US`, `zh-Hans`). Values reach this from `?lang=`, `$_POST['lang']`
	 * and the WPML admin cookie, and `sanitize_text_field()` leaves quotes intact —
	 * so anything used to build a meta key or SQL fragment must be narrowed here.
	 * Defense in depth: callers that reach SQL must still bind their values.
	 *
	 * @param string|null $language Raw language code.
	 * @return string|null Normalized code, or null when nothing usable remains.
	 */
	private static function sanitize_language_code( $language ) {
		if ( ! is_string( $language ) || '' === $language ) {
			return null;
		}

		$language = preg_replace( '/[^A-Za-z0-9_-]/', '', $language );

		return '' !== $language ? $language : null;
	}

	/**
	 * Generate language-specific meta key for category ordering
	 * Always falls back to base key if language-specific key doesn't exist
	 *
	 * @param string $base_key The base meta key (e.g., 'doc_category_order')
	 * @param string|null $language Language code, if null will auto-detect
	 * @return string Language-specific meta key or base key as fallback
	 */
	public static function get_language_specific_meta_key( $base_key, $language = null ) {
		// If no multilingual plugin is active, return the base key
		if ( ! self::is_multilingual_active() ) {
			return $base_key;
		}

		// Get current admin language if not provided
		if ( $language === null ) {
			$language = self::get_current_admin_language();
		}

		// If no language detected, return base key for backward compatibility
		if ( ! $language ) {
			return $base_key;
		}

		// Always return base key for now - we'll handle fallback in the query functions
		// This ensures compatibility without requiring migration
		return $base_key;
	}

	/**
	 * Get the meta key to write to.
	 *
	 * Unlike `get_meta_key_with_fallback`, this never falls back to the base
	 * key when the language-specific key is empty — that fallback is what
	 * caused secondary-language drag-and-drop saves to clobber the base meta
	 * (and on WPML setups that copy term meta from the original language,
	 * the next read would re-overwrite it from the primary language).
	 *
	 * @param string      $base_key The base meta key.
	 * @param string|null $language Language code, auto-detected when null.
	 * @return string Language-specific key when multilingual + language known, else base.
	 */
	public static function get_meta_key_for_save( $base_key, $language = null ) {
		if ( ! self::is_multilingual_active() ) {
			return $base_key;
		}

		if ( $language === null ) {
			$language = self::get_current_admin_language();
		}

		if ( ! $language ) {
			return $base_key;
		}

		return $base_key . '_' . $language;
	}

	/**
	 * Get the appropriate meta key with fallback logic
	 * This function checks if language-specific meta exists, if not falls back to base key
	 *
	 * @param string $base_key The base meta key
	 * @param int $term_id The term ID to check
	 * @param string|null $language Language code
	 * @return string The meta key to use
	 */
	public static function get_meta_key_with_fallback( $base_key, $term_id = null, $language = null ) {
		// If no multilingual plugin is active, return the base key
		if ( ! self::is_multilingual_active() ) {
			return $base_key;
		}

		// Get current admin language if not provided
		if ( $language === null ) {
			$language = self::get_current_admin_language();
		}

		// If no language detected, return base key
		if ( ! $language ) {
			return $base_key;
		}

		$lang_meta_key = $base_key . '_' . $language;

		// If we have a specific term ID, check if language-specific meta exists
		if ( $term_id ) {
			$lang_value = get_term_meta( $term_id, $lang_meta_key, true );
			if ( ! empty( $lang_value ) ) {
				return $lang_meta_key;
			}
			// Fall back to base key if language-specific doesn't exist
			return $base_key;
		}

		// For queries without specific term ID, we need to check if ANY terms have language-specific meta
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- live multilingual meta-key resolution; result varies per active language.
		$has_lang_meta = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->termmeta} tm
			INNER JOIN {$wpdb->term_taxonomy} tt ON tm.term_id = tt.term_id
			WHERE tm.meta_key = %s AND tt.taxonomy = 'doc_category' AND tm.meta_value != ''",
			$lang_meta_key
		) );

		// If language-specific meta exists for some terms, use it (terms without it will have empty values)
		// Otherwise, fall back to base key
		return $has_lang_meta > 0 ? $lang_meta_key : $base_key;
	}

	/**
	 * Migrate existing category orders to language-specific meta keys
	 * This should be called when a multilingual plugin is activated
	 *
	 * @param string $base_key The base meta key (e.g., 'doc_category_order')
	 * @param string $taxonomy The taxonomy to migrate
	 * @return bool Success status
	 */
	public static function migrate_category_orders_to_multilingual( $base_key = 'doc_category_order', $taxonomy = 'doc_category' ) {
		// Only run if multilingual plugin is active
		if ( ! self::is_multilingual_active() ) {
			return false;
		}

		global $wpdb;

		// Get all terms with the base meta key
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- one-shot multilingual migration; cache would be stale immediately after writes.
		$terms_with_order = $wpdb->get_results( $wpdb->prepare(
			"SELECT tm.term_id, tm.meta_value, t.slug
			FROM {$wpdb->termmeta} tm
			INNER JOIN {$wpdb->terms} t ON tm.term_id = t.term_id
			INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
			WHERE tm.meta_key = %s AND tt.taxonomy = %s",
			$base_key,
			$taxonomy
		) );

		if ( empty( $terms_with_order ) ) {
			return true; // Nothing to migrate
		}

		// Get available languages
		$languages = self::get_available_languages();

		if ( empty( $languages ) ) {
			return false; // No languages found
		}

		// Migrate orders for each language
		foreach ( $languages as $language ) {
			$language_meta_key = $base_key . '_' . $language;

			foreach ( $terms_with_order as $term_data ) {
				// Check if language-specific meta already exists
				$existing_value = get_term_meta( $term_data->term_id, $language_meta_key, true );

				if ( empty( $existing_value ) ) {
					// Copy the base order to language-specific key
					update_term_meta( $term_data->term_id, $language_meta_key, $term_data->meta_value );
				}
			}
		}

		return true;
	}

	/**
	 * Migrate existing document orders to language-specific meta keys
	 * This should be called when a multilingual plugin is activated
	 *
	 * @param string $base_key The base meta key (e.g., '_docs_order')
	 * @param string $taxonomy The taxonomy to migrate
	 * @return bool Success status
	 */
	public static function migrate_docs_orders_to_multilingual( $base_key = '_docs_order', $taxonomy = 'doc_category' ) {
		// Only run if multilingual plugin is active
		if ( ! self::is_multilingual_active() ) {
			return false;
		}

		global $wpdb;

		// Get all terms with the base meta key for document ordering
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- one-shot multilingual migration; cache would be stale immediately after writes.
		$terms_with_docs_order = $wpdb->get_results( $wpdb->prepare(
			"SELECT tm.term_id, tm.meta_value, t.slug
			FROM {$wpdb->termmeta} tm
			INNER JOIN {$wpdb->terms} t ON tm.term_id = t.term_id
			INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
			WHERE tm.meta_key = %s AND tt.taxonomy = %s AND tm.meta_value != ''",
			$base_key,
			$taxonomy
		) );

		if ( empty( $terms_with_docs_order ) ) {
			return true; // Nothing to migrate
		}

		// Get available languages
		$languages = self::get_available_languages();

		if ( empty( $languages ) ) {
			return false; // No languages found
		}

		// Migrate document orders for each language
		foreach ( $languages as $language ) {
			$language_meta_key = $base_key . '_' . $language;

			foreach ( $terms_with_docs_order as $term_data ) {
				// Check if language-specific meta already exists
				$existing_value = get_term_meta( $term_data->term_id, $language_meta_key, true );

				if ( empty( $existing_value ) ) {
					// Copy the base document order to language-specific key
					update_term_meta( $term_data->term_id, $language_meta_key, $term_data->meta_value );
				}
			}
		}

		return true;
	}

	/**
	 * Migrate both category and document orders to multilingual format
	 * This is a convenience method that runs both migrations
	 *
	 * @return bool Success status
	 */
	public static function migrate_all_orders_to_multilingual() {
		$category_result = self::migrate_category_orders_to_multilingual();
		$docs_result = self::migrate_docs_orders_to_multilingual();

		return $category_result && $docs_result;
	}

	/**
	 * Get available languages from multilingual plugins
	 *
	 * @return array Array of language codes
	 */
	public static function get_available_languages() {
		$languages = [];

		// WPML Support
		if ( is_plugin_active( 'sitepress-multilingual-cms/sitepress.php' ) ) {
			global $sitepress;
			if ( $sitepress && $sitepress->is_setup_complete() ) {
				$active_languages = $sitepress->get_active_languages();
				if ( is_array( $active_languages ) ) {
					$languages = array_keys( $active_languages );
				}
			}
		}
		// Polylang Support
		elseif ( function_exists( 'pll_languages_list' ) ) {
			$languages = pll_languages_list();
		}

		return $languages;
	}

	/**
	 * Rich list of active site languages for the React admin language bar.
	 *
	 * @return array<int,array{code:string,label:string,native:string,flag:string}>
	 *               Empty when no supported multilingual plugin is active.
	 */
	public static function get_admin_languages() {
		$languages = [];

		// WPML
		if ( is_plugin_active( 'sitepress-multilingual-cms/sitepress.php' ) ) {
			global $sitepress;
			if ( $sitepress && $sitepress->is_setup_complete() ) {
				$active = $sitepress->get_active_languages();
				if ( is_array( $active ) ) {
					foreach ( $active as $code => $lang ) {
						$languages[] = [
							'code'   => $code,
							'label'  => isset( $lang['english_name'] ) ? $lang['english_name'] : $code,
							'native' => isset( $lang['native_name'] ) ? $lang['native_name'] : ( isset( $lang['display_name'] ) ? $lang['display_name'] : $code ),
							'flag'   => isset( $lang['country_flag_url'] ) ? $lang['country_flag_url'] : '',
						];
					}
				}
			}
		}
		// Polylang
		elseif ( function_exists( 'pll_languages_list' ) ) {
			$list = pll_languages_list( [ 'fields' => '' ] ); // full PLL_Language objects
			if ( is_array( $list ) ) {
				foreach ( $list as $lang ) {
					if ( ! is_object( $lang ) ) {
						continue;
					}
					$languages[] = [
						'code'   => isset( $lang->slug ) ? $lang->slug : '',
						'label'  => isset( $lang->name ) ? $lang->name : ( isset( $lang->slug ) ? $lang->slug : '' ),
						'native' => isset( $lang->name ) ? $lang->name : '',
						'flag'   => isset( $lang->flag_url ) ? $lang->flag_url : '',
					];
				}
			}
		}

		return $languages;
	}

	/**
	 * Read a term's language code via the active multilingual plugin.
	 *
	 * @param \WP_Term $term
	 * @return string Language code, or '' when unavailable.
	 */
	public static function get_term_language( $term ) {
		if ( ! is_object( $term ) || empty( $term->term_id ) ) {
			return '';
		}

		// Polylang — takes the term_id.
		if ( function_exists( 'pll_get_term_language' ) ) {
			$lang = pll_get_term_language( $term->term_id, 'slug' );
			return $lang ? $lang : '';
		}

		// WPML — element_id is the term_taxonomy_id (NOT the term_id); WPML
		// normalizes the element_type to `tax_<taxonomy>` internally.
		if ( is_plugin_active( 'sitepress-multilingual-cms/sitepress.php' ) && ! empty( $term->term_taxonomy_id ) ) {
			$lang = apply_filters( 'wpml_element_language_code', null, [
				'element_id'   => $term->term_taxonomy_id,
				'element_type' => isset( $term->taxonomy ) ? $term->taxonomy : 'doc_category',
			] );
			return $lang ? $lang : '';
		}

		return '';
	}

	/**
	 * Stamp a term's language via the active multilingual plugin. Standalone
	 * assignment only — it sets/re-stamps the term's own language and does not
	 * link it into an existing translation group.
	 *
	 * @param \WP_Term $term
	 * @param string   $lang_code
	 */
	public static function set_term_language( $term, $lang_code ) {
		$lang_code = sanitize_text_field( (string) $lang_code );
		if ( $lang_code === '' || ! is_object( $term ) || empty( $term->term_id ) ) {
			return;
		}

		// Polylang
		if ( function_exists( 'pll_set_term_language' ) ) {
			pll_set_term_language( $term->term_id, $lang_code );
			return;
		}

		// WPML — element_id is the term_taxonomy_id; element_type is tax_<taxonomy>;
		// trid=null sets it as a standalone original in the chosen language.
		if ( is_plugin_active( 'sitepress-multilingual-cms/sitepress.php' ) && ! empty( $term->term_taxonomy_id ) ) {
			$taxonomy = isset( $term->taxonomy ) ? $term->taxonomy : 'doc_category';
			do_action( 'wpml_set_element_language_details', [
				'element_id'           => $term->term_taxonomy_id,
				'element_type'         => 'tax_' . $taxonomy,
				'trid'                 => null,
				'language_code'        => $lang_code,
				'source_language_code' => null,
			] );
		}
	}

	/**
	 * The site's default language code, or '' when no multilingual plugin is active.
	 */
	public static function get_default_language() {
		if ( is_plugin_active( 'sitepress-multilingual-cms/sitepress.php' ) ) {
			global $sitepress;
			if ( $sitepress ) {
				return (string) $sitepress->get_default_language();
			}
		}
		if ( function_exists( 'pll_default_language' ) ) {
			return (string) pll_default_language( 'slug' );
		}
		return '';
	}

	/**
	 * All terms in a term's translation group, keyed by language code.
	 *
	 * @param \WP_Term $term
	 * @return array<string,array{term_id:int,name:string}>
	 */
	public static function get_term_translations( $term ) {
		if ( ! is_object( $term ) || empty( $term->term_id ) ) {
			return [];
		}
		$taxonomy = isset( $term->taxonomy ) ? $term->taxonomy : 'doc_category';
		$out      = [];

		// Polylang
		if ( function_exists( 'pll_get_term_translations' ) ) {
			$group = pll_get_term_translations( $term->term_id ); // [lang => term_id]
			if ( is_array( $group ) ) {
				foreach ( $group as $lang => $tid ) {
					$t = get_term( (int) $tid, $taxonomy );
					if ( $t && ! is_wp_error( $t ) ) {
						$out[ $lang ] = [ 'term_id' => (int) $tid, 'name' => $t->name ];
					}
				}
			}
			return $out;
		}

		// WPML
		if ( is_plugin_active( 'sitepress-multilingual-cms/sitepress.php' ) && ! empty( $term->term_taxonomy_id ) ) {
			$el_type = 'tax_' . $taxonomy;
			$trid    = apply_filters( 'wpml_element_trid', null, $term->term_taxonomy_id, $el_type );
			if ( ! $trid ) {
				return $out;
			}
			$translations = apply_filters( 'wpml_get_element_translations', null, $trid, $el_type );
			if ( is_array( $translations ) ) {
				foreach ( $translations as $lang => $tr ) {
					$tid = isset( $tr->term_id ) ? (int) $tr->term_id : 0;
					if ( ! $tid ) {
						continue;
					}
					$t = get_term( $tid, $taxonomy );
					$out[ $lang ] = [
						'term_id' => $tid,
						'name'    => ( $t && ! is_wp_error( $t ) ) ? $t->name : ( isset( $tr->name ) ? $tr->name : '' ),
					];
				}
			}
		}

		return $out;
	}

	/**
	 * Candidate source terms for the "This is a translation of" dropdown — terms in
	 * $source_lang (default language) that aren't yet translated into $target_lang.
	 *
	 * @return array<int,array{term_id:int,name:string}>
	 */
	public static function get_translation_candidates( $taxonomy, $target_lang, $source_lang ) {
		$candidates = [];
		$target_lang = sanitize_text_field( (string) $target_lang );
		$source_lang = sanitize_text_field( (string) $source_lang );
		if ( $taxonomy === '' || $source_lang === '' ) {
			return $candidates;
		}

		// WPML
		if ( is_plugin_active( 'sitepress-multilingual-cms/sitepress.php' ) ) {
			global $sitepress;
			if ( $sitepress && method_exists( $sitepress, 'get_elements_without_translations' ) ) {
				$ttids = $sitepress->get_elements_without_translations( 'tax_' . $taxonomy, $target_lang, $source_lang );
				foreach ( (array) $ttids as $ttid ) {
					$t = get_term_by( 'term_taxonomy_id', (int) $ttid, $taxonomy );
					if ( $t && ! is_wp_error( $t ) ) {
						$candidates[] = [ 'term_id' => (int) $t->term_id, 'name' => $t->name ];
					}
				}
			}
			return $candidates;
		}

		// Polylang — source-lang terms whose group lacks the target language.
		if ( function_exists( 'pll_get_term_translations' ) && function_exists( 'pll_get_term_language' ) ) {
			$terms = get_terms( [ 'taxonomy' => $taxonomy, 'hide_empty' => false, 'lang' => $source_lang ] );
			foreach ( (array) $terms as $t ) {
				if ( is_wp_error( $t ) ) {
					continue;
				}
				$group = pll_get_term_translations( $t->term_id );
				if ( ! isset( $group[ $target_lang ] ) ) {
					$candidates[] = [ 'term_id' => (int) $t->term_id, 'name' => $t->name ];
				}
			}
		}

		return $candidates;
	}

	/**
	 * Set a term's language and (optionally) link it into the translation group of
	 * $translation_of_term_id. Empty $translation_of_term_id = standalone.
	 *
	 * @param \WP_Term $term
	 * @param string   $lang_code
	 * @param int      $translation_of_term_id
	 */
	public static function link_term_translation( $term, $lang_code, $translation_of_term_id = 0 ) {
		$lang_code = sanitize_text_field( (string) $lang_code );
		if ( $lang_code === '' || ! is_object( $term ) || empty( $term->term_id ) ) {
			return;
		}
		$taxonomy               = isset( $term->taxonomy ) ? $term->taxonomy : 'doc_category';
		$translation_of_term_id = (int) $translation_of_term_id;

		// Polylang
		if ( function_exists( 'pll_set_term_language' ) ) {
			pll_set_term_language( $term->term_id, $lang_code );
			if ( $translation_of_term_id && function_exists( 'pll_save_term_translations' ) ) {
				$group = function_exists( 'pll_get_term_translations' )
					? (array) pll_get_term_translations( $translation_of_term_id )
					: [];
				$group[ $lang_code ] = $term->term_id;
				pll_save_term_translations( $group );
			}
			return;
		}

		// WPML
		if ( is_plugin_active( 'sitepress-multilingual-cms/sitepress.php' ) && ! empty( $term->term_taxonomy_id ) ) {
			$el_type = 'tax_' . $taxonomy;
			$trid    = null;
			$src     = null;

			if ( $translation_of_term_id ) {
				$source = get_term( $translation_of_term_id, $taxonomy );
				if ( $source && ! is_wp_error( $source ) ) {
					$trid = apply_filters( 'wpml_element_trid', null, $source->term_taxonomy_id, $el_type );
					$src  = self::get_term_language( $source );
				}
			}

			do_action( 'wpml_set_element_language_details', [
				'element_id'           => $term->term_taxonomy_id,
				'element_type'         => $el_type,
				'trid'                 => $trid,
				'language_code'        => $lang_code,
				'source_language_code' => $src,
			] );
		}
	}

	// NOTE: get_current_letter_docs() and docs_sort_by_letter() used to live here.
	// Encyclopedia is a Pro-only feature and these had no callers in Free at all,
	// so they now ship as BetterDocsPro\Utils\EncyclopediaQuery. The generic
	// helpers they lean on (get_current_language, is_multilingual_active,
	// should_apply_language_filtering, get_custom_excerpt) stay here and are
	// called through this class from Pro.

    public static function get_glossaries() {
        global $wpdb;

        $lang_join = '';
        $lang_where = '';

        // Add language filtering if multilingual plugin is active and we should apply filtering
        $current_language = self::get_current_language();
        if ( $current_language && self::is_multilingual_active() && self::should_apply_language_filtering() ) {
            // Restrict language code to a safe character set before SQL interpolation.
            $current_language = preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $current_language );
            // For WPML, use icl_translations table
            if ( is_plugin_active( 'sitepress-multilingual-cms/sitepress.php' ) ) {
                $lang_join = " LEFT JOIN {$wpdb->prefix}icl_translations icl_t ON icl_t.element_id = t.term_id AND icl_t.element_type = 'tax_glossaries'";
                $lang_where = " AND (icl_t.language_code = '$current_language' OR icl_t.language_code IS NULL)";
            }
            // For Polylang, use term_relationships with language taxonomy
            elseif ( function_exists( 'pll_current_language' ) ) {
                $lang_join = " LEFT JOIN {$wpdb->term_relationships} tr ON t.term_id = tr.object_id LEFT JOIN {$wpdb->term_taxonomy} tt_lang ON tr.term_taxonomy_id = tt_lang.term_taxonomy_id AND tt_lang.taxonomy = 'language' LEFT JOIN {$wpdb->terms} t_lang ON tt_lang.term_id = t_lang.term_id";
                $lang_where = " AND (t_lang.slug = '$current_language' OR t_lang.slug IS NULL)";
            }
        }

        $query = "
            SELECT t.name
            FROM {$wpdb->terms} t
            INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
            $lang_join
            WHERE tt.taxonomy = 'glossaries'
            $lang_where
            ORDER BY t.name ASC
        ";

		$glossaries = $wpdb->get_col( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return $glossaries;
	}

	/**
	 * Determine live search template layout, when live search is not selected from customizer(this will work when live search template is not selected from customizer)
	 *
	 * @param string $layout
	 * @return string $layout
	 */
	public static function determine_search_layout( $layout ) {
		if ( $layout ) {
			return $layout;
		}

		$search_layout       = betterdocs()->customizer->defaults->get( 'betterdocs_search_layout_select' );
		$docs_layout         = betterdocs()->customizer->defaults->get( 'betterdocs_docs_layout_select' );
		$archive_page_layout = betterdocs()->customizer->defaults->get( 'betterdocs_archive_layout_select' );
		$single_layout       = betterdocs()->customizer->defaults->get( 'betterdocs_single_layout_select' );

        if ( is_post_type_archive( 'docs' ) ) {
            if ( $docs_layout != "layout-7" && ! $search_layout ) {
                $layout = 'layout-1';
            } else if ( $docs_layout == 'layout-7' && ! $search_layout ) {
                $layout = 'layout-2';
            }
        } else if ( is_tax( 'doc_tag' ) && ! $search_layout ) {
            $layout = 'layout-1';
        } else if ( is_tax( 'doc_category' ) ) {
           if ( $archive_page_layout != 'layout-7' && $archive_page_layout != 'layout-8' && ! $search_layout ) {
                $layout = 'layout-1';
            } else if ( ( $archive_page_layout == 'layout-7' && ! $search_layout ) || ( $archive_page_layout == 'layout-8' && ! $search_layout ) ) {
                $layout = 'layout-2';
            }
        } else if ( is_singular( 'docs' ) ) {
            if ( $single_layout != 'layout-8' && $single_layout != 'layout-9' && ! $search_layout ) {
                $layout = 'layout-1';
            } else if ( ( $single_layout == 'layout-8' && ! $search_layout ) || ( $single_layout == 'layout-9' && ! $search_layout ) ) {
                $layout = 'layout-2';
            }
        }

        return $layout;
    }

    // NOTE: the alphabet-range helpers (get_character_range, unicodeRange,
    // mb_ord_fallback, mb_chr_fallback) moved to BetterDocsPro\Utils\EncyclopediaQuery
    // along with the two methods above — same reason: Encyclopedia is Pro-only
    // and nothing in Free ever called them.

    public static function get_the_top_most_parent( $term_id ) {
        while ( $term_id != 0 ) {
            $parent_id = wp_get_term_taxonomy_parent_id( $term_id, 'doc_category' );

            if ( $parent_id == 0 ) {
                break;
            }

            $term_id = $parent_id;
        }
        return $term_id;
    }

    public static function get_highest_docs_term() {
        $terms = get_terms( [
            'taxonomy'   => 'doc_category', // Change to your desired taxonomy
            'hide_empty' => true, // Only show terms with posts
            'orderby'    => 'count', // Order by post count
            'order'      => 'DESC', // Descending order
            'number'     => 1 // Get only the top term
        ] );
        return isset( $terms[0] ) ? $terms[0] : [];
    }

    public static function delete_specific_faq_posts_by_faq_category( $term_id, $taxonomy = 'betterdocs_faq_category' ) {
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- targeted bulk delete by FAQ category; tax filter is required.
        $args = [
            'post_type'      => 'betterdocs_faq',
            'posts_per_page' => -1,
            // EVERY status, explicitly. WP_Query defaults to 'publish', so "delete this
            // group and its FAQs" was deleting only the published ones — the drafts (and
            // pending/scheduled/private/trashed FAQs) survived, and the wp_delete_term()
            // that follows then stripped their category, leaving them orphaned under
            // "Uncategorized". Note 'any' is NOT enough here: it excludes trash.
            'post_status'    => [ 'publish', 'draft', 'pending', 'future', 'private', 'trash' ],
            'tax_query'      => [
                [
                    'taxonomy' => $taxonomy,
                    'field'    => 'id',
                    'terms'    => $term_id,
                    'operator' => 'IN'
                ]
            ],
            'fields'         => 'ids'
        ];

        $query = new \WP_Query( $args );

        if ( $query->have_posts() ) {
            foreach ( $query->posts as $doc_id ) {
                wp_delete_post( $doc_id, true );
            }
        }
    }

	/**
	 * Function To Normalize Repeater Field For Quick Builder
	 *
	 * @param array $fields
	 * @param array $include_field_keys
	 *
	 * @return array
	 */
	public static function normalize_repeater_field( $fields, $include_field_keys = [] ) {
		if( empty( $include_field_keys ) ) {
			return $fields;
		}

		$normalized_fields = [];

		foreach( $fields as $field ) {
			foreach( $include_field_keys as $field_key ) {
				if( ! isset( $normalized_fields[$field_key] ) ) {
					$normalized_fields[$field_key] = isset( $field[$field_key] ) && ! empty( $field[$field_key] ) ? $field[$field_key] : [];
				} else {
					array_push( $normalized_fields[$field_key], ...( isset( $field[$field_key] ) && ! empty( $field[$field_key] ) ? $field[$field_key] : [] ) );
					$normalized_fields[$field_key] = array_unique( $normalized_fields[$field_key] );
				}
			}
		}

		return $normalized_fields;
	}

	public static function get_local_plugin_data( $basename = '' ) {
        if ( empty( $basename ) ) {
            return false;
        }

        if ( !function_exists( 'get_plugins' ) ) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $plugins = get_plugins();

        if ( !isset( $plugins[ $basename ] ) ) {
            return false;
        }

        return $plugins[ $basename ];
    }

    /**
     * Get default file icon based on programming language
     *
     * @param string $language Programming language identifier
     * @return string Emoji icon for the language
     */
    public static function get_file_icon_by_language( $language ) {
        $icons = [
            'javascript' => '📄',
            'typescript' => '📘',
            'jsx' => '⚛️',
            'tsx' => '⚛️',
            'html' => '🌐',
            'css' => '🎨',
            'scss' => '🎨',
            'sass' => '🎨',
            'less' => '🎨',
            'php' => '🐘',
            'python' => '🐍',
            'java' => '☕',
            'csharp' => '🔷',
            'cpp' => '⚙️',
            'c' => '⚙️',
            'ruby' => '💎',
            'go' => '🐹',
            'rust' => '🦀',
            'swift' => '🦉',
            'kotlin' => '🎯',
            'sql' => '🗃️',
            'json' => '📋',
            'yaml' => '📋',
            'xml' => '📄',
            'markdown' => '📝',
            'curl' => '💻',
            'bash' => '💻',
            'shell' => '💻',
            'powershell' => '💻',
            'dockerfile' => '🐳',
        ];

        return isset( $icons[$language] ) ? $icons[$language] : '📄';
    }

    /**
     * Echo the copy-to-clipboard button used by the Code Snippet and Code
     * Snippet Tab templates.
     *
     * Both icons ship in the markup and CSS cross-fades between them on
     * `.is-copied`, so the frontend script never rewrites the SVG. The tooltip
     * carries its own strings as data attributes so the script can swap
     * "Copy" → "Copied!" without hard-coding English.
     *
     * @return void
     */
    public static function code_snippet_copy_button() {
        ?>
        <div class="betterdocs-code-snippet-copy-container">
            <button class="betterdocs-code-snippet-copy-button"
                    type="button"
                    aria-label="<?php esc_attr_e( 'Copy code to clipboard', 'betterdocs' ); ?>">
                <span class="betterdocs-code-snippet-copy-icon" aria-hidden="true">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="9" y="9" width="12.5" height="12.5" rx="3" stroke="currentColor" stroke-width="1.7"/>
                        <path d="M15.5 5.75V5A2.5 2.5 0 0 0 13 2.5H5A2.5 2.5 0 0 0 2.5 5v8A2.5 2.5 0 0 0 5 15.5h.75" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                    </svg>
                </span>
                <span class="betterdocs-code-snippet-copied-icon" aria-hidden="true">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M20 6.5 9.5 17 4 11.5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
            </button>
            <span class="betterdocs-code-snippet-tooltip"
                  role="status"
                  data-copy-label="<?php esc_attr_e( 'Copy', 'betterdocs' ); ?>"
                  data-copied-label="<?php esc_attr_e( 'Copied!', 'betterdocs' ); ?>"
                  data-error-label="<?php esc_attr_e( 'Copy failed', 'betterdocs' ); ?>"><?php esc_html_e( 'Copy', 'betterdocs' ); ?></span>
        </div>
        <?php
    }

    /**
     * Human-readable label for a programming-language identifier, used as the
     * language-dropdown label on multi-language code snippets. Mirrors the
     * block's LANGUAGE_OPTIONS; falls back to an upper-cased identifier.
     *
     * @param string $language Programming language identifier
     * @return string
     */
    public static function get_language_label( $language ) {
        $labels = [
            'javascript' => 'JavaScript',
            'typescript' => 'TypeScript',
            'php'        => 'PHP',
            'python'     => 'Python',
            'java'       => 'Java',
            'ruby'       => 'Ruby',
            'curl'       => 'cURL',
            'bash'       => 'Bash',
            'shell'      => 'Shell',
            'json'       => 'JSON',
            'yaml'       => 'YAML',
            'html'       => 'HTML',
            'css'        => 'CSS',
            'scss'       => 'SCSS',
            'sql'        => 'SQL',
            'xml'        => 'XML',
            'cpp'        => 'C++',
            'csharp'     => 'C#',
            'c'          => 'C',
            'go'         => 'Go',
            'rust'       => 'Rust',
            'swift'      => 'Swift',
            'kotlin'     => 'Kotlin',
            'markdown'   => 'Markdown'
        ];

        if ( isset( $labels[ $language ] ) ) {
            return $labels[ $language ];
        }

        return ucwords( str_replace( [ '-', '_' ], ' ', (string) $language ) );
    }

	/**
	 * Check if AI Chatbot is enabled
	 *
	 * @return bool
	 */
	public function is_ai_chatbot_enabled() {
		$chatbot_active = is_plugin_active( 'betterdocs-ai-chatbot/betterdocs-ai-chatbot.php' );
		$chatbot_license_valid = get_option( 'betterdocs_chatbot_software__license_status' ) === 'valid';
		$chatbot_enabled = betterdocs()->settings->get( 'enable_ai_chatbot', false );

		// AI Search Suggestions are enabled if all conditions are met
		return $chatbot_active && $chatbot_license_valid && $chatbot_enabled;
	}

	/**
	 * Check if tags are enabled and post has tags
	 *
	 * @return bool
	 */
	public function is_tag_enabled() {
		global $post;
		$product_terms = wp_get_object_terms( $post->ID, 'doc_tag' );
		$enable_tags = betterdocs()->settings->get( 'enable_tags', false );
		return ! empty( $product_terms ) && $enable_tags;
	}

	/**
	 * Check if AI Search Suggestions are enabled
	 *
	 * @return bool
	 */
	public function is_ai_search_suggestions_enabled() {
		$ai_search_suggestions_active = is_plugin_active( 'betterdocs-ai-search-suggestions/betterdocs-ai-search-suggestions.php' );
		$ai_search_suggestions_license_valid = get_option( 'betterdocs_ai_search_suggestions_software__license_status' ) === 'valid';
		$ai_search_suggestions_enabled = betterdocs()->settings->get( 'enable_ai_powered_search', false );

		return $ai_search_suggestions_active && $ai_search_suggestions_license_valid && $ai_search_suggestions_enabled;
	}

	/**
	 * Get the maximum order value from the 'doc_category_order' term meta
	 *
	 * @return int
	 */
	public static function get_max_doc_category_order_from_term_meta() {
		global $wpdb;
		$sql    = $wpdb->prepare( "SELECT MAX(CAST(meta_value AS UNSIGNED)) AS max FROM {$wpdb->termmeta} WHERE meta_key = %s ", 'doc_category_order' );
		$result = $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- query is prepared above.
		return $result;
	}

	/**
	 * Encyclopedia / glossary query helpers — backward-compatibility shims.
	 *
	 * These methods were moved out of Free and into BetterDocs Pro's own
	 * `EncyclopediaQuery` class as of Pro 4.3.1 (the "move glossaries to Pro"
	 * release). BetterDocs Pro 4.3.0 and earlier, however, still call
	 * `Helper::get_character_range()` / `get_current_letter_docs()` /
	 * `docs_sort_by_letter()` from their encyclopedia blocks, widgets,
	 * shortcodes, templates and AJAX callbacks. When a site runs new Free with
	 * an older Pro (< 4.3.1) still active, those calls would fatal with
	 * "Call to undefined method". Keeping these shims here lets that older Pro
	 * keep rendering until it is updated. Pro 4.3.1+ uses its own copy and never
	 * touches these, so there is no double-execution or conflict.
	 *
	 * @deprecated Retained only for BetterDocs Pro < 4.3.1 compatibility.
	 */
	public static function get_current_letter_docs( $current_letter, $limit = 0 ) {
		global $wpdb;

		$limit     = absint( $limit );
		$limit_sql = $limit > 0 ? $wpdb->prepare( 'LIMIT %d', $limit ) : '';

		// Check if the encyclopedia_prefix parameter is set

        $encyclopeia_suorce     = betterdocs()->settings->get( 'encyclopedia_source', 'docs' );
        $enable_glossaries      = betterdocs()->settings->get( 'enable_glossaries', false );
        $encyclopedia_root_slug = betterdocs()->settings->get( 'encyclopedia_root_slug', 'encyclopdia' );
        // Sanitize values that may be interpolated into raw SQL fragments below.
        $encyclopedia_root_slug = sanitize_title( $encyclopedia_root_slug );

		// if($enable_glossaries && $encyclopeia_suorce === 'glossaries'){
		if ( $enable_glossaries && $encyclopeia_suorce === 'glossaries' ) {
			$lang_join = '';
			$lang_where = '';

			// Add language filtering if multilingual plugin is active and we should apply filtering
			$current_language = self::get_current_language();
			if ( $current_language && self::is_multilingual_active() && self::should_apply_language_filtering() ) {
				// Restrict language code to a safe character set before SQL interpolation.
				$current_language = preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $current_language );
				// For WPML, use icl_translations table
				if ( is_plugin_active( 'sitepress-multilingual-cms/sitepress.php' ) ) {
					$lang_join = " LEFT JOIN {$wpdb->prefix}icl_translations icl_t ON icl_t.element_id = t.term_id AND icl_t.element_type = 'tax_glossaries'";
					$lang_where = " AND (icl_t.language_code = '$current_language' OR icl_t.language_code IS NULL)";
				}
				// For Polylang, use term_relationships with language taxonomy
				elseif ( function_exists( 'pll_current_language' ) ) {
					$lang_join = " LEFT JOIN {$wpdb->term_relationships} tr ON t.term_id = tr.object_id LEFT JOIN {$wpdb->term_taxonomy} tt_lang ON tr.term_taxonomy_id = tt_lang.term_taxonomy_id AND tt_lang.taxonomy = 'language' LEFT JOIN {$wpdb->terms} t_lang ON tt_lang.term_id = t_lang.term_id";
					$lang_where = " AND (t_lang.slug = '$current_language' OR t_lang.slug IS NULL)";
				}
			}

			$query = "
                SELECT
                    t.term_id,
                    t.name AS post_title,
                    t.slug as slug,
                    '' AS post_excerpt,
                    CONCAT('" . get_home_url() . "/$encyclopedia_root_slug/', t.slug) AS permalink,
                    tt.description AS post_content,
                    JSON_OBJECT(
                        'status', COALESCE(MAX(CASE WHEN m.meta_key = 'status' THEN m.meta_value END), ''),
                        'glossary_term_description', COALESCE(MAX(CASE WHEN m.meta_key = 'glossary_term_description' THEN m.meta_value END), '')
                    ) AS meta_data
                FROM
                    {$wpdb->terms} t
                INNER JOIN
                    {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
                LEFT JOIN
                    {$wpdb->termmeta} m ON t.term_id = m.term_id
                $lang_join
                WHERE
                    tt.taxonomy = 'glossaries'
                AND
                    SUBSTRING(t.name, 1, 1) = %s
                $lang_where
                GROUP BY
                    t.term_id
                ORDER BY
                    t.name ASC
                $limit_sql
            ";
		} else {
			$lang_join = '';
			$lang_where = '';

			// Add language filtering for docs if multilingual plugin is active and we should apply filtering
			$current_language = self::get_current_language();
			if ( $current_language && self::is_multilingual_active() && self::should_apply_language_filtering() ) {
				// Restrict language code to a safe character set before SQL interpolation.
				$current_language = preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $current_language );
				// For WPML, use icl_translations table
				if ( is_plugin_active( 'sitepress-multilingual-cms/sitepress.php' ) ) {
					$lang_join = " LEFT JOIN {$wpdb->prefix}icl_translations icl_t ON icl_t.element_id = {$wpdb->posts}.ID AND icl_t.element_type = 'post_docs'";
					$lang_where = " AND (icl_t.language_code = '$current_language' OR icl_t.language_code IS NULL)";
				}
				// For Polylang, use term_relationships with language taxonomy
				elseif ( function_exists( 'pll_current_language' ) ) {
					$lang_join = " LEFT JOIN {$wpdb->term_relationships} tr ON {$wpdb->posts}.ID = tr.object_id LEFT JOIN {$wpdb->term_taxonomy} tt_lang ON tr.term_taxonomy_id = tt_lang.term_taxonomy_id AND tt_lang.taxonomy = 'language' LEFT JOIN {$wpdb->terms} t_lang ON tt_lang.term_id = t_lang.term_id";
					$lang_where = " AND (t_lang.slug = '$current_language' OR t_lang.slug IS NULL)";
				}
			}

			$query = "
                SELECT ID, post_title, post_excerpt, guid, post_content
                FROM {$wpdb->posts}
                $lang_join
                WHERE post_type = 'docs'
                AND post_status = 'publish'
                AND SUBSTRING(post_title, 1, 1) = %s
                $lang_where
                ORDER BY post_date DESC
                $limit_sql
            ";
		}

		$current_letter_docs = $wpdb->get_results( $wpdb->prepare( $query, $current_letter ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return $current_letter_docs;
	}

    public static function docs_sort_by_letter( $limit = 10 ) {
        global $wpdb;
        $enable_non_latin = betterdocs()->settings->get( 'encyclopedia_enable_non_latin' );
        $script           = betterdocs()->settings->get( 'encyclopedia_non_latin_option' );
        $letters          = Helper::get_character_range( $enable_non_latin, $script );

        $docs_by_letter     = [];
        $encyclopeia_suorce = betterdocs()->settings->get( 'encyclopedia_source', 'docs' );
        $enable_glossaries  = betterdocs()->settings->get( 'enable_glossaries', false );

        foreach ( $letters as $letter ) {
            $posts = self::get_current_letter_docs( $letter, $limit );

            if ( is_array( $posts ) && ! empty( $posts ) ) {
                foreach ( $posts as $post ) {
                    $description               = isset($post['meta_data']) ? \json_decode( $post['meta_data'], true ) : '';
                    $glossary_term_description = $description['glossary_term_description'] ?? '';

                    // Remove any <p> tags or other unwanted HTML tags
                    $glossary_term_description = wp_strip_all_tags( $glossary_term_description );
                    $post_excerpt              = wp_strip_all_tags( $post['post_excerpt'] ?? '' );

                    // Prepare post data
                    if ( $enable_glossaries && $encyclopeia_suorce === 'glossaries' ) {
                        // For glossaries
                        $permalink = '';

                        if ( isset( $post['slug'] ) ) {
                            $term_link = get_term_link( $post['slug'], 'glossaries' );

                            if ( ! is_wp_error( $term_link ) ) {
                                $permalink = $term_link;
                            }
                        }

                        $post_data = [
                            'id'           => $post['term_id'] ?? '',
                            'post_title'   => $post['post_title'] ?? '',
                            'post_excerpt' => ! empty( $post_excerpt )
                            ? $post_excerpt
                            : ( ! empty( $glossary_term_description )
                                ? self::get_custom_excerpt( $glossary_term_description, 15 )
                                : self::get_custom_excerpt( wp_strip_all_tags( $post['post_content'] ?? '' ), 15 ) ),
                            'permalink'    => $permalink,
                        ];
                    } else {
                        // For docs
                        $post_data = [
                            'id'           => $post['ID'] ?? '',
                            'post_title'   => $post['post_title'] ?? '',
                            'post_excerpt' => ! empty( $post_excerpt )
                            ? $post_excerpt
                            : self::get_custom_excerpt( wp_strip_all_tags( $post['post_content'] ?? '' ), 15 ),
                            'permalink'    => isset( $post['ID'] ) ? get_the_permalink( $post['ID'] ) : ''
                        ];
                    }

                    $docs_by_letter[$letter][] = $post_data;
                }
            }
        }

        return $docs_by_letter;
    }
    public static function mb_ord_fallback( $char ) {
        $code = unpack( 'N', mb_convert_encoding( $char, 'UCS-4BE', 'UTF-8' ) );
        return $code[1];
    }

    public static function mb_chr_fallback( $code ) {
        return mb_convert_encoding( pack( 'N', $code ), 'UTF-8', 'UCS-4BE' );
    }
    public static function unicodeRange( $start, $end ) {
        $range = [];
        for ( $i = self::mb_ord_fallback( $start ); $i <= self::mb_ord_fallback( $end ); $i++ ) {
            $range[] = self::mb_chr_fallback( $i );
        }
        return $range;
    }

    public static function get_character_range( $enable_non_latin, $script ) {
        if ( $enable_non_latin ) {
            switch ( $script ) {
                case 'arabic':
                    return self::unicodeRange( 'ء', 'ي' );
                case 'cyrillic':
                    return self::unicodeRange( 'А', 'Я' );
                case 'hebrew':
                    return self::unicodeRange( 'א', 'ת' );
                case 'greek':
                    return self::unicodeRange( 'Α', 'Ω' );
                default:
                    return range( 'A', 'Z' );
            }
        }

        return range( 'A', 'Z' );
    }
}
