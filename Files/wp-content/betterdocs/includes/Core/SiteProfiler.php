<?php

namespace WPDeveloper\BetterDocs\Core;

/**
 * SiteProfiler — Phase 1 of the AI "Generate Sample Docs" feature.
 *
 * Runs entirely inside WordPress (no AI call). Produces a small, deterministic,
 * privacy-safe profile of the site that the hosted proxy turns into tailored
 * docs/FAQs. Detection only targets; the proxy writes.
 *
 * The profile contains only public-facing labels (titles, category names) — no
 * user data, no order data, no PII.
 *
 * @since 4.5.3
 */
class SiteProfiler {
	/**
	 * Transient key for the cached profile (per-locale).
	 * @var string
	 */
	const CACHE_KEY = 'betterdocs_site_profile';

	/**
	 * How long to cache the computed profile so re-renders don't recompute.
	 * @var int
	 */
	const CACHE_TTL = HOUR_IN_SECONDS;

	/**
	 * Transient key for the cached content digest (per-locale). Kept separate from the
	 * base profile so the lightweight detect() path never carries the heavier excerpts.
	 * @var string
	 */
	const CONTENT_CACHE_KEY = 'betterdocs_site_content';

	/**
	 * Build the full site profile.
	 *
	 * @param bool $fresh        Skip the cache and recompute.
	 * @param bool $with_content Also attach the richer `content` digest (real page/post/
	 *                           product excerpts). Only the AI KB "outline" call needs it;
	 *                           detect() leaves it off to stay light.
	 * @return array
	 */
	public function build( $fresh = false, $with_content = false ) {
		$cache_key = self::CACHE_KEY . '_' . get_locale();

		$profile = null;
		if ( ! $fresh ) {
			$cached = get_transient( $cache_key );
			if ( is_array( $cached ) ) {
				$profile = $cached;
			}
		}

		if ( null === $profile ) {
			$profile = $this->build_base();
			set_transient( $cache_key, $profile, self::CACHE_TTL );
		}

		if ( $with_content ) {
			// Computed + cached separately so it never bloats the base profile transient.
			$profile['content'] = $this->content_digest( $fresh );
		}

		return $profile;
	}

	/**
	 * Compute the base (content-free) profile. Split out of build() so the heavier
	 * content digest can be attached on demand without polluting the base cache.
	 *
	 * @return array
	 */
	private function build_base() {
		$site = [
			'title'   => sanitize_text_field( get_bloginfo( 'name' ) ),
			'tagline' => sanitize_text_field( get_bloginfo( 'description' ) ),
			'url'     => esc_url_raw( home_url() ),
			'locale'  => get_locale(),
			'theme'   => sanitize_text_field( wp_get_theme()->get( 'Name' ) ),
		];

		$type   = $this->detect_type();
		$topics = $this->topic_hints();
		$niche  = $this->detect_niche( $site, $type, $topics );

		$profile = [
			'site'        => $site,
			'type'        => $type,
			// Human-friendly niche descriptor for the detection UI and the proxy
			// prompt (e.g. "AI / LLM platform"), inferred from real site content.
			'niche'       => $niche['key'],
			'niche_label' => $niche['label'],
			'signals'     => $this->collect_signals(),
			'woocommerce' => $this->woo_context(),
			'topics'      => $topics,
		];

		/**
		 * Filter the computed site profile before it is cached/returned.
		 *
		 * @param array $profile
		 */
		return apply_filters( 'betterdocs_site_profile', $profile );
	}

	/**
	 * Real, privacy-safe, size-bounded content excerpts that let the AI understand what
	 * the site actually does (not just page/nav titles). Only published, public content
	 * — already on the public web — is excerpted: no drafts, private, user or order data.
	 *
	 * Cached separately from the base profile and gated by filters so it can be trimmed
	 * or disabled per site.
	 *
	 * @param bool $fresh Skip the content cache and recompute.
	 * @return array
	 */
	public function content_digest( $fresh = false ) {
		/**
		 * Allow a site to opt out of sending real content excerpts to the AI proxy.
		 *
		 * @param bool $enabled
		 */
		if ( ! apply_filters( 'betterdocs_site_profile_include_content', true ) ) {
			return [];
		}

		$cache_key = self::CONTENT_CACHE_KEY . '_' . get_locale();
		if ( ! $fresh ) {
			$cached = get_transient( $cache_key );
			if ( is_array( $cached ) ) {
				return $cached;
			}
		}

		$content = [
			'summary' => $this->home_about_summary(),
			'pages'   => $this->page_excerpts(),
			'posts'   => $this->post_excerpts(),
		];

		if ( class_exists( 'WooCommerce' ) ) {
			$content['products'] = $this->product_excerpts();
		}

		$content = array_filter(
			$content,
			function ( $value ) {
				return ! ( is_array( $value ) && empty( $value ) ) && '' !== $value;
			}
		);

		/**
		 * Filter the computed content digest before it is cached/returned.
		 *
		 * @param array        $content
		 * @param SiteProfiler $profiler
		 */
		$content = apply_filters( 'betterdocs_site_profile_content', $content, $this );

		set_transient( $cache_key, $content, self::CACHE_TTL );

		return $content;
	}

	/**
	 * Short plain-text summary drawn from the front page and an About-style page — the
	 * best single signal of "what is this site about".
	 *
	 * @return string
	 */
	private function home_about_summary() {
		$parts = [];

		$front_id = (int) get_option( 'page_on_front' );
		if ( 'page' === get_option( 'show_on_front' ) && $front_id > 0 ) {
			$ex = $this->excerpt_of( get_post_field( 'post_content', $front_id ), 300 );
			if ( '' !== $ex ) {
				$parts[] = $ex;
			}
		}

		foreach ( [ 'about', 'about-us', 'company', 'who-we-are' ] as $slug ) {
			$page = get_page_by_path( $slug );
			if ( $page instanceof \WP_Post && 'publish' === $page->post_status ) {
				$ex = $this->excerpt_of( $page->post_content, 300 );
				if ( '' !== $ex ) {
					$parts[] = $ex;
					break;
				}
			}
		}

		return $this->excerpt_of( implode( ' ', $parts ), 600 );
	}

	/**
	 * Title + short excerpt for the first few published pages (About, Pricing, Features…).
	 *
	 * @return array
	 */
	private function page_excerpts() {
		// Exclude WooCommerce's utility pages (Shop/Cart/Checkout/My account) and the
		// privacy page: they carry no subject anyone documents or FAQs about, but on a
		// store they have the lowest menu_order and so used to consume the whole (small)
		// budget — starving the real content pages (About, Pricing, Financing, Delivery…).
		$exclude = array_filter(
			[
				(int) get_option( 'woocommerce_shop_page_id' ),
				(int) get_option( 'woocommerce_cart_page_id' ),
				(int) get_option( 'woocommerce_checkout_page_id' ),
				(int) get_option( 'woocommerce_myaccount_page_id' ),
				(int) get_option( 'wp_page_for_privacy_policy' ),
			]
		);

		$pages = get_posts(
			[
				'post_type'      => 'page',
				'posts_per_page' => 15,
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
				'post_status'    => 'publish',
				'post__not_in'   => $exclude,
			]
		);

		$out = [];
		foreach ( $pages as $page ) {
			$title = sanitize_text_field( get_the_title( $page ) );
			if ( '' === $title ) {
				continue;
			}
			$out[] = [
				'title'   => $title,
				'excerpt' => $this->excerpt_of( $page->post_content, 150 ),
			];
		}

		return array_values( $out );
	}

	/**
	 * Title + short excerpt for recent published blog posts — signals real topics.
	 *
	 * @return array
	 */
	private function post_excerpts() {
		$posts = get_posts(
			[
				'post_type'      => 'post',
				'posts_per_page' => 8,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'post_status'    => 'publish',
			]
		);

		$out = [];
		foreach ( $posts as $post ) {
			$title = sanitize_text_field( get_the_title( $post ) );
			if ( '' === $title ) {
				continue;
			}
			$raw = '' !== trim( (string) $post->post_excerpt ) ? $post->post_excerpt : $post->post_content;
			$out[] = [
				'title'   => $title,
				'excerpt' => $this->excerpt_of( $raw, 120 ),
			];
		}

		return array_values( $out );
	}

	/**
	 * Title + short description for a few recent WooCommerce products.
	 *
	 * @return array
	 */
	private function product_excerpts() {
		$products = get_posts(
			[
				'post_type'      => 'product',
				'posts_per_page' => 8,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'post_status'    => 'publish',
			]
		);

		$out = [];
		foreach ( $products as $product ) {
			$title = sanitize_text_field( get_the_title( $product ) );
			if ( '' === $title ) {
				continue;
			}
			$raw = '' !== trim( (string) $product->post_excerpt ) ? $product->post_excerpt : $product->post_content;
			$out[] = [
				'title'   => $title,
				'excerpt' => $this->excerpt_of( $raw, 120 ),
			];
		}

		return array_values( $out );
	}

	/**
	 * Turn raw post content into a clean, bounded, single-line plain-text excerpt.
	 * Strips shortcodes + tags (handles page-builder markup) and collapses whitespace.
	 *
	 * @param string $raw
	 * @param int    $chars
	 * @return string
	 */
	private function excerpt_of( $raw, $chars ) {
		$text = wp_strip_all_tags( strip_shortcodes( (string) $raw ) );
		$text = trim( preg_replace( '/\s+/', ' ', $text ) );
		if ( '' === $text ) {
			return '';
		}
		if ( mb_strlen( $text ) > $chars ) {
			$text = rtrim( mb_substr( $text, 0, $chars ) ) . '…';
		}
		return sanitize_text_field( $text );
	}

	/**
	 * Primary site type, keyed off active plugins (highest signal).
	 * First match wins.
	 *
	 * @return string
	 */
	public function detect_type() {
		$active = $this->active_plugins();
		$has    = function ( $needle ) use ( $active ) {
			foreach ( $active as $plugin ) {
				if ( stripos( $plugin, $needle ) !== false ) {
					return true;
				}
			}
			return false;
		};

		if ( $has( 'woocommerce' ) ) {
			return 'ecommerce';
		}
		if ( $has( 'learndash' ) || $has( 'tutor' ) || $has( 'lifterlms' ) || $has( 'sensei' ) ) {
			return 'course_lms';
		}
		if ( $has( 'easy-digital-downloads' ) ) {
			return 'digital_downloads';
		}
		if ( $has( 'paid-memberships-pro' ) || $has( 'memberpress' ) || $has( 'restrict-content' ) ) {
			return 'membership';
		}
		if ( $has( 'bbpress' ) || $has( 'buddypress' ) ) {
			return 'community';
		}
		if ( $has( 'elementor' ) || $has( 'beaver' ) || $has( 'divi' ) ) {
			return 'business_site';
		}

		return 'general';
	}

	/**
	 * Human-friendly site descriptor for the detection UI ("We detected …") and
	 * to ground the proxy prompt. Prefers a strong content-based niche signal
	 * (title/tagline/pages/categories/nav/existing docs); falls back to the
	 * plugin-detected type, then a generic label. No AI — keyword scoring only.
	 *
	 * @param array  $site
	 * @param string $type
	 * @param array  $topics
	 * @return array { key:string, label:string }
	 */
	public function detect_niche( array $site, $type, array $topics ) {
		$parts = array_merge(
			[ isset( $site['title'] ) ? $site['title'] : '', isset( $site['tagline'] ) ? $site['tagline'] : '' ],
			isset( $topics['page_titles'] ) ? (array) $topics['page_titles'] : [],
			isset( $topics['post_categories'] ) ? (array) $topics['post_categories'] : [],
			isset( $topics['nav_items'] ) ? (array) $topics['nav_items'] : [],
			isset( $topics['doc_categories'] ) ? (array) $topics['doc_categories'] : [],
			isset( $topics['doc_titles'] ) ? (array) $topics['doc_titles'] : []
		);

		// Pad + collapse whitespace so " ai " etc. match as whole words anywhere.
		$text = ' ' . preg_replace( '/\s+/', ' ', strtolower( implode( ' ', $parts ) ) ) . ' ';

		// Most-specific first. Leading spaces on short tokens avoid false positives
		// (e.g. " api" never matches "therapist").
		$niches = [
			'ai_llm'    => [
				'label'    => 'AI / LLM platform',
				'keywords' => [ ' llm', ' gpt', ' ai ', 'a.i.', 'artificial intelligence', 'machine learning', 'language model', 'large language', 'prompt', 'openai', 'anthropic', 'claude', 'chatbot', 'embedding', 'fine-tun', 'inference', 'generative', 'neural' ],
			],
			'developer' => [
				'label'    => 'developer platform',
				'keywords' => [ ' api', ' sdk', 'developer', 'endpoint', 'webhook', ' cli ', 'integration', 'documentation', 'rate limit', 'oauth', 'deploy' ],
			],
			'ecommerce' => [
				'label'    => 'online store',
				'keywords' => [ ' shop', ' store', ' cart', 'checkout', 'product', 'shipping', 'returns', ' order', 'fashion', 'clothing', 'apparel' ],
			],
			'education' => [
				'label'    => 'online learning site',
				'keywords' => [ ' course', 'lesson', ' learn', 'student', 'curriculum', 'enroll', 'academy', 'tutorial', ' class', 'teacher' ],
			],
			'agency'    => [
				'label'    => 'agency or services site',
				'keywords' => [ 'agency', 'clients', 'marketing', ' seo', 'branding', 'portfolio', 'services' ],
			],
			'health'    => [
				'label'    => 'health & wellness site',
				'keywords' => [ 'health', 'clinic', 'medical', 'wellness', 'therapy', 'patient', 'fitness', 'nutrition' ],
			],
			'finance'   => [
				'label'    => 'finance site',
				'keywords' => [ 'finance', ' bank', 'invest', 'crypto', 'trading', ' loan', 'insurance' ],
			],
			'food'      => [
				'label'    => 'food & restaurant site',
				'keywords' => [ 'restaurant', ' menu', 'recipe', ' food', ' cafe', 'coffee', 'cuisine', ' dish' ],
			],
		];

		$scores = [];
		foreach ( $niches as $key => $def ) {
			$score = 0;
			foreach ( $def['keywords'] as $kw ) {
				if ( strpos( $text, $kw ) !== false ) {
					$score++;
				}
			}
			$scores[ $key ] = $score;
		}

		// AI/LLM is the most notable/specific niche — let it win whenever there are
		// a couple of independent AI signals, even if "developer" also scores high.
		if ( $scores['ai_llm'] >= 2 ) {
			return [ 'key' => 'ai_llm', 'label' => $niches['ai_llm']['label'] ];
		}

		arsort( $scores );
		$best  = key( $scores );
		$top   = current( $scores );
		if ( $best && $top >= 2 ) {
			return [ 'key' => $best, 'label' => $niches[ $best ]['label'] ];
		}

		// Fall back to the plugin-detected type, then a generic label.
		$type_labels = [
			'ecommerce'         => 'online store',
			'course_lms'        => 'course / LMS site',
			'digital_downloads' => 'digital downloads store',
			'membership'        => 'membership site',
			'community'         => 'community site',
			'business_site'     => 'business site',
		];
		if ( isset( $type_labels[ $type ] ) ) {
			return [ 'key' => $type, 'label' => $type_labels[ $type ] ];
		}

		return [ 'key' => 'general', 'label' => 'website' ];
	}

	/**
	 * Raw signals — give the AI evidence, not just a label.
	 *
	 * @return array
	 */
	public function collect_signals() {
		$active = $this->active_plugins();
		$known  = [
			'woocommerce'    => 'WooCommerce',
			'learndash'      => 'LearnDash',
			'tutor'          => 'Tutor LMS',
			'lifterlms'      => 'LifterLMS',
			'easy-digital'   => 'Easy Digital Downloads',
			'memberpress'    => 'MemberPress',
			'bbpress'        => 'bbPress',
			'buddypress'     => 'BuddyPress',
			'elementor'      => 'Elementor',
			'wpforms'        => 'WPForms',
			'yoast'          => 'Yoast SEO',
		];

		$detected = [];
		foreach ( $known as $needle => $label ) {
			foreach ( $active as $plugin ) {
				if ( stripos( $plugin, $needle ) !== false ) {
					$detected[] = $label;
					break;
				}
			}
		}

		return [
			'active_plugins' => array_values( array_unique( $detected ) ),
			'theme'          => sanitize_text_field( wp_get_theme()->get( 'Name' ) ),
		];
	}

	/**
	 * WooCommerce context — only if Woo is active.
	 *
	 * @return array|null
	 */
	public function woo_context() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return null;
		}

		$cats      = get_terms(
			[
				'taxonomy'   => 'product_cat',
				'orderby'    => 'count',
				'order'      => 'DESC',
				'number'     => 8,
				'hide_empty' => true,
			]
		);
		$cat_names = is_wp_error( $cats ) ? [] : array_map( 'sanitize_text_field', wp_list_pluck( $cats, 'name' ) );

		$products      = get_posts(
			[
				'post_type'      => 'product',
				'posts_per_page' => 8,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'fields'         => 'ids',
			]
		);
		$product_names = array_map(
			function ( $id ) {
				return sanitize_text_field( get_the_title( $id ) );
			},
			$products
		);

		$context = [
			'product_categories' => array_values( $cat_names ),
			'sample_products'    => array_values( array_filter( $product_names ) ),
			'currency'           => function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : '',
			'currency_symbol'    => function_exists( 'get_woocommerce_currency_symbol' ) ? html_entity_decode( get_woocommerce_currency_symbol() ) : '',
		];

		// Store config used to ground store/product FAQs in real settings. Each
		// value is read defensively — anything missing simply drops out so the
		// FAQ copy can fall back to a generic-but-accurate answer. No PII.
		$context['store_location']     = $this->store_location();
		$context['payment_methods']    = $this->payment_methods();
		$context['shipping_regions']   = $this->shipping_regions();
		$context['tax_enabled']        = function_exists( 'wc_tax_enabled' ) ? (bool) wc_tax_enabled() : false;
		$context['prices_include_tax'] = 'yes' === get_option( 'woocommerce_prices_include_tax' );

		$terms_page = (int) get_option( 'woocommerce_terms_and_conditions_page_id', 0 );
		if ( $terms_page > 0 ) {
			$context['terms_page_url'] = esc_url_raw( (string) get_permalink( $terms_page ) );
		}

		$privacy_page = (int) get_option( 'wp_page_for_privacy_policy', 0 );
		if ( $privacy_page > 0 ) {
			$context['privacy_page_url'] = esc_url_raw( (string) get_permalink( $privacy_page ) );
		}

		// Return window only exists when a returns extension stores it; omit otherwise.
		$return_window = get_option( 'woocommerce_return_requests_window', '' );
		if ( '' !== $return_window && null !== $return_window ) {
			$context['return_window'] = (int) $return_window;
		}

		// Key WooCommerce pages — so store FAQ answers can link the real account /
		// checkout flow instead of describing it generically.
		$this->add_wc_page( $context, 'account_page_url', 'myaccount' );
		$this->add_wc_page( $context, 'checkout_page_url', 'checkout' );
		$this->add_wc_page( $context, 'shop_page_url', 'shop' );

		// Refund/returns policy page (WooCommerce has no canonical option — resolved
		// heuristically), used to link the real policy in returns/refunds answers.
		$refund = $this->refund_policy();
		if ( ! empty( $refund['url'] ) ) {
			$context['refund_policy_url'] = $refund['url'];
			if ( ! empty( $refund['excerpt'] ) ) {
				$context['refund_policy_excerpt'] = $refund['excerpt'];
			}
		}

		// Free-shipping threshold, when a Free Shipping method defines a minimum.
		$free_min = $this->free_shipping_min();
		if ( null !== $free_min ) {
			$context['free_shipping_min'] = $free_min;
		}

		return array_filter(
			$context,
			function ( $value ) {
				return ! ( is_array( $value ) && empty( $value ) ) && '' !== $value;
			}
		);
	}

	/**
	 * Store base location label (city/region, country) for shipping/payment FAQs.
	 *
	 * @return string
	 */
	private function store_location() {
		if ( ! function_exists( 'wc_get_base_location' ) ) {
			return '';
		}

		$base    = wc_get_base_location();
		$country = isset( $base['country'] ) ? (string) $base['country'] : '';
		$city    = (string) get_option( 'woocommerce_store_city', '' );

		if ( function_exists( 'WC' ) && WC()->countries && $country ) {
			$countries = WC()->countries->get_countries();
			$country   = isset( $countries[ $country ] ) ? $countries[ $country ] : $country;
		}

		$parts = array_filter( [ sanitize_text_field( $city ), sanitize_text_field( $country ) ] );

		return implode( ', ', $parts );
	}

	/**
	 * Titles of the enabled payment gateways (e.g. "PayPal", "Credit/Debit Card").
	 *
	 * @return array
	 */
	private function payment_methods() {
		if ( ! function_exists( 'WC' ) || ! WC()->payment_gateways ) {
			return [];
		}

		$gateways = WC()->payment_gateways->get_available_payment_gateways();
		$titles   = [];
		foreach ( (array) $gateways as $gateway ) {
			$title = isset( $gateway->title ) ? wp_strip_all_tags( (string) $gateway->title ) : '';
			if ( '' !== trim( $title ) ) {
				$titles[] = sanitize_text_field( $title );
			}
		}

		return array_values( array_unique( $titles ) );
	}

	/**
	 * Shipping zone names (region labels the store ships to).
	 *
	 * @return array
	 */
	private function shipping_regions() {
		if ( ! class_exists( '\WC_Shipping_Zones' ) ) {
			return [];
		}

		$zones  = \WC_Shipping_Zones::get_zones();
		$labels = [];
		foreach ( (array) $zones as $zone ) {
			$name = isset( $zone['zone_name'] ) ? sanitize_text_field( $zone['zone_name'] ) : '';
			if ( '' !== trim( $name ) ) {
				$labels[] = $name;
			}
		}

		return array_values( array_unique( $labels ) );
	}

	/**
	 * Add a WooCommerce page's permalink to the context when the page exists.
	 *
	 * @param array  $context Context array (by reference).
	 * @param string $key     Context key to set.
	 * @param string $wc_page WooCommerce page id key (myaccount|checkout|shop|cart).
	 * @return void
	 */
	private function add_wc_page( array &$context, $key, $wc_page ) {
		if ( ! function_exists( 'wc_get_page_id' ) ) {
			return;
		}
		$page_id = (int) wc_get_page_id( $wc_page );
		if ( $page_id > 0 ) {
			$url = get_permalink( $page_id );
			if ( $url ) {
				$context[ $key ] = esc_url_raw( (string) $url );
			}
		}
	}

	/**
	 * Resolve the store's Refund/Returns policy page. WooCommerce has no canonical
	 * option for it, so match common slugs; returns the URL + a short excerpt, or an
	 * empty array when none is found (callers fall back to the terms page).
	 *
	 * @return array { url?: string, excerpt?: string }
	 */
	private function refund_policy() {
		$slugs = [ 'refund_returns', 'refund-and-returns-policy', 'refund-policy', 'returns', 'return-policy', 'returns-policy' ];
		$page  = null;
		foreach ( $slugs as $slug ) {
			$found = get_page_by_path( $slug );
			if ( $found instanceof \WP_Post && 'publish' === $found->post_status ) {
				$page = $found;
				break;
			}
		}

		if ( ! $page instanceof \WP_Post ) {
			return [];
		}

		return [
			'url'     => esc_url_raw( (string) get_permalink( $page->ID ) ),
			'excerpt' => sanitize_text_field( wp_trim_words( wp_strip_all_tags( (string) $page->post_content ), 40, '…' ) ),
		];
	}

	/**
	 * The lowest Free Shipping minimum-order amount configured across shipping zones,
	 * or null when no Free Shipping method defines a positive threshold.
	 *
	 * @return float|null
	 */
	private function free_shipping_min() {
		if ( ! class_exists( '\WC_Shipping_Zones' ) ) {
			return null;
		}

		// All real zones plus the catch-all "Rest of the World" zone (id 0).
		$zone_ids = [ 0 ];
		foreach ( (array) \WC_Shipping_Zones::get_zones() as $zone ) {
			if ( isset( $zone['id'] ) ) {
				$zone_ids[] = (int) $zone['id'];
			}
		}

		$min = null;
		foreach ( array_unique( $zone_ids ) as $zone_id ) {
			$zone = \WC_Shipping_Zones::get_zone( $zone_id );
			if ( ! $zone ) {
				continue;
			}
			foreach ( (array) $zone->get_shipping_methods( true ) as $method ) {
				if ( ! isset( $method->id ) || 'free_shipping' !== $method->id ) {
					continue;
				}
				$amount = method_exists( $method, 'get_option' ) ? $method->get_option( 'min_amount' ) : ( isset( $method->min_amount ) ? $method->min_amount : '' );
				$amount = is_numeric( $amount ) ? (float) $amount : 0;
				if ( $amount > 0 && ( null === $min || $amount < $min ) ) {
					$min = $amount;
				}
			}
		}

		return $min;
	}

	/**
	 * Topic hints — primary nav menu + key pages + top post categories.
	 *
	 * @return array
	 */
	public function topic_hints() {
		// Primary nav menu item labels.
		$nav       = [];
		$locations = get_nav_menu_locations();
		if ( ! empty( $locations ) ) {
			$menu_id = reset( $locations );
			$items   = wp_get_nav_menu_items( $menu_id );
			if ( $items ) {
				$nav = array_slice(
					array_map(
						function ( $item ) {
							return sanitize_text_field( $item->title );
						},
						$items
					),
					0,
					12
				);
			}
		}

		// Key page titles (About, Pricing, Services, Contact, FAQ…).
		$pages       = get_posts(
			[
				'post_type'      => 'page',
				'posts_per_page' => 10,
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
				'fields'         => 'ids',
			]
		);
		$page_titles = array_map(
			function ( $id ) {
				return sanitize_text_field( get_the_title( $id ) );
			},
			$pages
		);

		// Top post categories for blog topic hints.
		$post_cats      = get_terms(
			[
				'taxonomy'   => 'category',
				'orderby'    => 'count',
				'order'      => 'DESC',
				'number'     => 6,
				'hide_empty' => true,
			]
		);
		$post_cat_names = is_wp_error( $post_cats ) ? [] : array_map( 'sanitize_text_field', wp_list_pluck( $post_cats, 'name' ) );

		// Existing documentation — lets the proxy build content that extends what
		// the site already covers. In particular, FAQ generation derives questions
		// from these doc topics ("detect FAQs from the docs too").
		$docs       = get_posts(
			[
				'post_type'      => 'docs',
				'posts_per_page' => 15,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'post_status'    => 'publish',
				'fields'         => 'ids',
			]
		);
		$doc_titles = array_map(
			function ( $id ) {
				return sanitize_text_field( get_the_title( $id ) );
			},
			$docs
		);

		$doc_cats      = get_terms(
			[
				'taxonomy'   => 'doc_category',
				'orderby'    => 'count',
				'order'      => 'DESC',
				'number'     => 6,
				'hide_empty' => true,
			]
		);
		$doc_cat_names = is_wp_error( $doc_cats ) ? [] : array_map( 'sanitize_text_field', wp_list_pluck( $doc_cats, 'name' ) );

		return [
			'nav_items'       => array_values( array_filter( $nav ) ),
			'page_titles'     => array_values( array_filter( $page_titles ) ),
			'post_categories' => array_values( $post_cat_names ),
			'doc_titles'      => array_values( array_filter( $doc_titles ) ),
			'doc_categories'  => array_values( $doc_cat_names ),
		];
	}

	/**
	 * Clear the cached profile (call after big content/plugin changes).
	 *
	 * @return void
	 */
	public function flush() {
		delete_transient( self::CACHE_KEY . '_' . get_locale() );
		delete_transient( self::CONTENT_CACHE_KEY . '_' . get_locale() );
	}

	/**
	 * Active plugins on this site (network-active included).
	 *
	 * @return array
	 */
	private function active_plugins() {
		$active = (array) get_option( 'active_plugins', [] );

		if ( is_multisite() ) {
			$network = (array) get_site_option( 'active_sitewide_plugins', [] );
			$active  = array_merge( $active, array_keys( $network ) );
		}

		return $active;
	}
}
