<?php

namespace WPDeveloper\BetterDocs\Core;

/**
 * StoreFaqContent — deterministic, store-aware Product FAQ generator.
 *
 * Single source of truth for the WooCommerce "sample Product FAQs" starter set:
 * four groups (Payments & Billing, Shipping & Delivery, Returns & Refunds,
 * Orders & Account) of three questions each, curated to cover the highest
 * friction pre-/post-purchase buyer questions.
 *
 * It serves two callers:
 *   - SampleDocs::suggested_categories() uses definition() for the chips/titles
 *     shown on the "detected profile" screen.
 *   - SampleDocs::generate() uses generate() as the hybrid fallback when the AI
 *     proxy is unavailable (offline / quota / no model), producing real answers
 *     grounded in the site's actual store config (payment gateways, currency,
 *     tax, shipping regions, return window, policy pages) — no AI required.
 *
 * Answers degrade gracefully: when a datum is missing, the copy falls back to a
 * generic-but-accurate sentence rather than inventing specifics.
 *
 * @since 4.5.3
 */
class StoreFaqContent {
	/**
	 * The curated group + question structure. Titles are static; answers are
	 * filled per-site in generate().
	 *
	 * @return array
	 */
	public function definition() {
		return [
			[
				'name'        => __( 'Payments & Billing', 'betterdocs' ),
				'slug'        => 'payments-billing',
				'icon'        => 'help',
				'description' => __( 'How customers can pay and what to expect when checking out.', 'betterdocs' ),
				'questions'   => [
					'payment_methods'  => __( 'What payment methods do you accept?', 'betterdocs' ),
					'payment_security' => __( 'Is it safe to enter my card details at checkout?', 'betterdocs' ),
					'currency_tax'     => __( 'What currency are prices in, and will I be charged tax?', 'betterdocs' ),
				],
			],
			[
				'name'        => __( 'Shipping & Delivery', 'betterdocs' ),
				'slug'        => 'shipping-delivery',
				'icon'        => 'truck',
				'description' => __( 'Where you ship, how long it takes, and how to track an order.', 'betterdocs' ),
				'questions'   => [
					'shipping_coverage' => __( 'Where do you ship and how long does delivery take?', 'betterdocs' ),
					'shipping_cost'     => __( 'How much does shipping cost?', 'betterdocs' ),
					'order_tracking'    => __( 'How can I track my order?', 'betterdocs' ),
				],
			],
			[
				'name'        => __( 'Returns & Refunds', 'betterdocs' ),
				'slug'        => 'returns-refunds',
				'icon'        => 'refund',
				'description' => __( 'Your return window, how to request a refund, and what to expect.', 'betterdocs' ),
				'questions'   => [
					'return_policy' => __( 'What is your return & refund policy?', 'betterdocs' ),
					'return_how'    => __( 'How do I request a return or refund?', 'betterdocs' ),
					'refund_timing' => __( 'How long do refunds take, and who pays return shipping?', 'betterdocs' ),
				],
			],
			[
				'name'        => __( 'Orders & Account', 'betterdocs' ),
				'slug'        => 'orders-account',
				'icon'        => 'book',
				'description' => __( 'Placing, changing, and tracking orders, and whether an account is needed.', 'betterdocs' ),
				'questions'   => [
					'place_order'    => __( 'How do I place an order?', 'betterdocs' ),
					'modify_cancel'  => __( 'Can I change or cancel my order after checkout?', 'betterdocs' ),
					'account_status' => __( 'Do I need an account to buy, and how do I check order status?', 'betterdocs' ),
				],
			],
		];
	}

	/**
	 * Build the full categories array (proxy-shaped) with real store answers.
	 *
	 * @param array $profile The SiteProfiler profile (uses ['woocommerce'] + ['site']).
	 * @return array [{ name, slug, description, articles:[{ title, content_html, excerpt }] }]
	 */
	public function generate( array $profile ) {
		$woo  = isset( $profile['woocommerce'] ) && is_array( $profile['woocommerce'] ) ? $profile['woocommerce'] : [];
		$site = isset( $profile['site'] ) && is_array( $profile['site'] ) ? $profile['site'] : [];

		$categories = [];
		foreach ( $this->definition() as $group ) {
			$articles = [];
			foreach ( $group['questions'] as $id => $title ) {
				$html      = $this->answer_html( $id, $woo, $site );
				$articles[] = [
					'title'        => $title,
					'content_html' => $html,
					'excerpt'      => $this->excerpt( $html ),
				];
			}

			$categories[] = [
				'name'        => $group['name'],
				'slug'        => $group['slug'],
				'description' => $group['description'],
				'articles'    => $articles,
			];
		}

		return $categories;
	}

	/**
	 * The single "store-wide" FAQ group — the questions every shopper asks on any
	 * product page, regardless of what the product is (payments, shipping, returns,
	 * orders). One group rather than the four themed ones from definition(): it is
	 * flagged "show on all products", so it appears once on every product page, and a
	 * dozen questions in one accordion is a lot. A curated set of six spanning all four
	 * themes reads best.
	 *
	 * @return array { name, slug, icon, description, questions:[ id => title ] }
	 */
	public function consolidated_definition() {
		return [
			'name'        => __( 'Shipping, Returns & Payments', 'betterdocs' ),
			'slug'        => 'store-info',
			'icon'        => 'truck',
			'description' => __( 'Common questions about payments, delivery, returns and orders — shown on every product.', 'betterdocs' ),
			'questions'   => [
				'payment_methods'   => __( 'What payment methods do you accept?', 'betterdocs' ),
				'shipping_coverage' => __( 'Where do you ship and how long does delivery take?', 'betterdocs' ),
				'shipping_cost'     => __( 'How much does shipping cost?', 'betterdocs' ),
				'order_tracking'    => __( 'How can I track my order?', 'betterdocs' ),
				'return_policy'     => __( 'What is your return & refund policy?', 'betterdocs' ),
				'modify_cancel'     => __( 'Can I change or cancel my order after checkout?', 'betterdocs' ),
			],
		];
	}

	/**
	 * Build the single store-wide group (proxy-shaped) with real, settings-grounded
	 * answers — the deterministic Layer 1 of the WooCommerce product FAQ. Reuses the
	 * same per-question answer builders generate() uses.
	 *
	 * @param array $profile The SiteProfiler profile (uses ['woocommerce'] + ['site']).
	 * @return array { name, slug, description, articles:[{ title, content_html, excerpt }] }
	 */
	public function generate_consolidated( array $profile ) {
		$woo   = isset( $profile['woocommerce'] ) && is_array( $profile['woocommerce'] ) ? $profile['woocommerce'] : [];
		$site  = isset( $profile['site'] ) && is_array( $profile['site'] ) ? $profile['site'] : [];
		$group = $this->consolidated_definition();

		$articles = [];
		foreach ( $group['questions'] as $id => $title ) {
			$html       = $this->answer_html( $id, $woo, $site );
			$articles[] = [
				'title'        => $title,
				'content_html' => $html,
				'excerpt'      => $this->excerpt( $html ),
			];
		}

		return [
			'name'        => $group['name'],
			'slug'        => $group['slug'],
			'icon'        => $group['icon'],
			'description' => $group['description'],
			'articles'    => $articles,
		];
	}

	/* --------------------------------------------------------------------- */
	/* Answer builders                                                        */
	/* --------------------------------------------------------------------- */

	/**
	 * @return string Safe HTML (paragraphs/lists/links) for one answer.
	 */
	protected function answer_html( $id, array $woo, array $site ) {
		switch ( $id ) {
			case 'payment_methods':
				return $this->answer_payment_methods( $woo );
			case 'payment_security':
				return $this->answer_payment_security( $site, $woo );
			case 'currency_tax':
				return $this->answer_currency_tax( $woo );
			case 'shipping_coverage':
				return $this->answer_shipping_coverage( $woo );
			case 'shipping_cost':
				return $this->answer_shipping_cost( $woo );
			case 'order_tracking':
				return $this->answer_order_tracking( $woo );
			case 'return_policy':
				return $this->answer_return_policy( $woo );
			case 'return_how':
				return $this->answer_return_how( $woo );
			case 'refund_timing':
				return $this->answer_refund_timing( $woo );
			case 'place_order':
				return $this->answer_place_order( $woo );
			case 'modify_cancel':
				return $this->answer_modify_cancel();
			case 'account_status':
				return $this->answer_account_status( $woo );
		}

		return '';
	}

	protected function answer_payment_methods( array $woo ) {
		$methods = isset( $woo['payment_methods'] ) ? (array) $woo['payment_methods'] : [];

		if ( ! empty( $methods ) ) {
			$intro = sprintf(
				/* translators: %s: list of payment method names. */
				__( 'We accept %s.', 'betterdocs' ),
				$this->human_list( $methods )
			);
		} else {
			$intro = __( 'We accept all major payment methods, including popular credit and debit cards and digital wallets.', 'betterdocs' );
		}

		$second = __( 'You can review every available option on the checkout page before you place your order, and all payments are processed securely.', 'betterdocs' );

		return $this->p( $intro ) . $this->p( $second );
	}

	protected function answer_payment_security( array $site, array $woo = [] ) {
		$name = isset( $site['title'] ) && '' !== $site['title'] ? $site['title'] : __( 'our store', 'betterdocs' );

		$first  = __( 'Yes. Your payment is processed over a secure, encrypted (SSL) connection.', 'betterdocs' );
		$second = sprintf(
			/* translators: %s: site/store name. */
			__( 'Card details are handled by our trusted payment provider — %s never stores your full card number on its own servers.', 'betterdocs' ),
			$name
		);

		$privacy = isset( $woo['privacy_page_url'] ) ? (string) $woo['privacy_page_url'] : '';
		$third   = '';
		if ( '' !== $privacy ) {
			$third = $this->p(
				sprintf(
					/* translators: %s: link to the store's privacy policy page. */
					__( 'See our %s for how your information is collected and used.', 'betterdocs' ),
					'<a href="' . esc_url( $privacy ) . '">' . esc_html__( 'privacy policy', 'betterdocs' ) . '</a>'
				)
			);
		}

		return $this->p( $first ) . $this->p( $second ) . $third;
	}

	protected function answer_currency_tax( array $woo ) {
		$currency = isset( $woo['currency'] ) ? (string) $woo['currency'] : '';
		$symbol   = isset( $woo['currency_symbol'] ) ? (string) $woo['currency_symbol'] : '';

		if ( '' !== $currency ) {
			$label = '' !== $symbol ? sprintf( '%s (%s)', $currency, $symbol ) : $currency;
			$first = sprintf(
				/* translators: %s: currency code (and symbol). */
				__( 'All prices on our store are shown in %s.', 'betterdocs' ),
				$label
			);
		} else {
			$first = __( 'All prices are shown in our store currency, displayed throughout checkout.', 'betterdocs' );
		}

		if ( ! empty( $woo['tax_enabled'] ) ) {
			$second = ! empty( $woo['prices_include_tax'] )
				? __( 'Applicable taxes are already included in the price you see.', 'betterdocs' )
				: __( 'Any applicable taxes are calculated based on your location and shown at checkout before you pay.', 'betterdocs' );
		} else {
			$second = __( 'The total you see at checkout is the final amount you pay.', 'betterdocs' );
		}

		return $this->p( $first ) . $this->p( $second );
	}

	protected function answer_shipping_coverage( array $woo ) {
		$regions  = isset( $woo['shipping_regions'] ) ? (array) $woo['shipping_regions'] : [];
		$location = isset( $woo['store_location'] ) ? (string) $woo['store_location'] : '';

		if ( ! empty( $regions ) ) {
			$first = sprintf(
				/* translators: %s: list of shipping region names. */
				__( 'We currently ship to %s.', 'betterdocs' ),
				$this->human_list( $regions )
			);
		} else {
			$first = __( 'We ship to all the destinations available at checkout — enter your address to see the options for your area.', 'betterdocs' );
		}

		$second = __( 'Most orders are processed within 1–2 business days. Delivery time then depends on your destination and the shipping method you choose at checkout.', 'betterdocs' );

		if ( '' !== $location ) {
			$second .= ' ' . sprintf(
				/* translators: %s: store base location. */
				__( 'Orders ship from %s.', 'betterdocs' ),
				$location
			);
		}

		return $this->p( $first ) . $this->p( $second );
	}

	protected function answer_shipping_cost( array $woo = [] ) {
		$first = __( 'Shipping cost is calculated automatically at checkout based on your delivery address and the method you select, so you always see the exact amount before you pay.', 'betterdocs' );

		$free_min = isset( $woo['free_shipping_min'] ) ? (float) $woo['free_shipping_min'] : 0;
		if ( $free_min > 0 ) {
			$first .= ' ' . sprintf(
				/* translators: %s: formatted minimum order amount for free shipping. */
				__( 'Orders over %s qualify for free shipping.', 'betterdocs' ),
				$this->format_amount( $free_min, $woo )
			);
		}

		return $this->p( $first )
			. $this->p( __( 'Add your items to the cart and enter your address to view the available shipping rates for your order.', 'betterdocs' ) );
	}

	protected function answer_order_tracking( array $woo = [] ) {
		$first   = __( 'As soon as your order ships, we email you a confirmation with the details you need to follow its progress.', 'betterdocs' );
		$account = isset( $woo['account_page_url'] ) ? (string) $woo['account_page_url'] : '';

		if ( '' !== $account ) {
			$second = sprintf(
				/* translators: %s: link to the customer's account orders page. */
				__( 'If you have an account, you can also track every order any time from your %s.', 'betterdocs' ),
				'<a href="' . esc_url( $account ) . '">' . esc_html__( 'account\'s Orders page', 'betterdocs' ) . '</a>'
			);
		} else {
			$second = __( 'If you have an account, you can also see the current status of every order any time from your account\'s Orders page.', 'betterdocs' );
		}

		return $this->p( $first ) . $this->p( $second );
	}

	protected function answer_return_policy( array $woo ) {
		$window = isset( $woo['return_window'] ) ? (int) $woo['return_window'] : 0;

		if ( $window > 0 ) {
			$first = sprintf(
				/* translators: %d: number of days in the return window. */
				_n(
					'You can request a return or refund within %d day of receiving your order, as long as the item is unused and in its original condition.',
					'You can request a return or refund within %d days of receiving your order, as long as the item is unused and in its original condition.',
					$window,
					'betterdocs'
				),
				$window
			);
		} else {
			$first = __( 'If you are not completely happy with your purchase, you can request a return or refund — just get in touch and we will make it right.', 'betterdocs' );
		}

		return $this->p( $first ) . $this->policy_line( $woo );
	}

	protected function answer_return_how( array $woo = [] ) {
		$steps = '<ol>'
			. '<li>' . esc_html__( 'Contact us with your order number and the item you would like to return.', 'betterdocs' ) . '</li>'
			. '<li>' . esc_html__( 'We confirm eligibility and send you return instructions.', 'betterdocs' ) . '</li>'
			. '<li>' . esc_html__( 'Pack the item securely and ship it back to the address we provide.', 'betterdocs' ) . '</li>'
			. '<li>' . esc_html__( 'Once it arrives, we process your refund or exchange.', 'betterdocs' ) . '</li>'
			. '</ol>';

		return $this->p( __( 'Requesting a return is simple:', 'betterdocs' ) ) . $steps . $this->policy_line( $woo );
	}

	protected function answer_refund_timing( array $woo ) {
		$first  = __( 'Approved refunds are issued to your original payment method, typically within 5–10 business days after we receive the returned item.', 'betterdocs' );
		$second = __( 'Return shipping is the customer\'s responsibility unless the item arrived damaged, defective, or incorrect — in which case we cover it.', 'betterdocs' );

		return $this->p( $first ) . $this->p( $second ) . $this->policy_line( $woo );
	}

	protected function answer_place_order( array $woo = [] ) {
		$shop = isset( $woo['shop_page_url'] ) ? (string) $woo['shop_page_url'] : '';
		$first = '' !== $shop
			? sprintf(
				/* translators: %s: link to the shop page. */
				__( 'Browse %s, choose any options you need such as size or color, then click Add to Cart.', 'betterdocs' ),
				'<a href="' . esc_url( $shop ) . '">' . esc_html__( 'our products', 'betterdocs' ) . '</a>'
			)
			: __( 'Browse our products and choose any options you need, such as size or color, then click Add to Cart.', 'betterdocs' );

		$checkout = isset( $woo['checkout_page_url'] ) ? (string) $woo['checkout_page_url'] : '';
		$second   = '' !== $checkout
			? sprintf(
				/* translators: %s: link to the checkout page. */
				__( 'When you are ready, open your cart and proceed to %s, enter your shipping and payment details, and confirm your order. You will receive a confirmation email right away.', 'betterdocs' ),
				'<a href="' . esc_url( $checkout ) . '">' . esc_html__( 'checkout', 'betterdocs' ) . '</a>'
			)
			: __( 'When you are ready, open your cart and select Checkout, enter your shipping and payment details, and confirm your order. You will receive a confirmation email right away.', 'betterdocs' );

		return $this->p( $first ) . $this->p( $second );
	}

	protected function answer_modify_cancel() {
		return $this->p( __( 'If you need to change or cancel an order, please contact us as soon as possible and we will do our best to update it before it ships.', 'betterdocs' ) )
			. $this->p( __( 'Once an order has shipped it can no longer be changed, but you can still return it under our return policy.', 'betterdocs' ) );
	}

	protected function answer_account_status( array $woo = [] ) {
		$account = isset( $woo['account_page_url'] ) ? (string) $woo['account_page_url'] : '';
		$second  = '' !== $account
			? sprintf(
				/* translators: %s: link to the customer's account page. */
				__( 'Creating an %s lets you track orders, save your addresses, and reorder faster. Either way, you will receive an email confirmation for every order and can reply to it any time with questions.', 'betterdocs' ),
				'<a href="' . esc_url( $account ) . '">' . esc_html__( 'account', 'betterdocs' ) . '</a>'
			)
			: __( 'Creating an account lets you track orders, save your addresses, and reorder faster. Either way, you will receive an email confirmation for every order and can reply to it any time with questions.', 'betterdocs' );

		return $this->p( __( 'An account is not required — you are welcome to check out as a guest.', 'betterdocs' ) ) . $this->p( $second );
	}

	/* --------------------------------------------------------------------- */
	/* Helpers                                                                */
	/* --------------------------------------------------------------------- */

	/**
	 * A trailing "see our policies" line, linked to the Terms page when known.
	 *
	 * @return string
	 */
	protected function policy_line( array $woo ) {
		// Prefer the real refund/returns policy page; fall back to terms & conditions.
		$url   = isset( $woo['refund_policy_url'] ) ? (string) $woo['refund_policy_url'] : '';
		$label = __( 'refund & returns policy', 'betterdocs' );

		if ( '' === $url ) {
			$url   = isset( $woo['terms_page_url'] ) ? (string) $woo['terms_page_url'] : '';
			$label = __( 'terms & policies', 'betterdocs' );
		}

		if ( '' === $url ) {
			return '';
		}

		return $this->p(
			sprintf(
				/* translators: %s: link to the store's refund/returns or terms policy page. */
				__( 'For full details, please see our %s.', 'betterdocs' ),
				'<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>'
			)
		);
	}

	/**
	 * Format a money amount using the store's currency symbol when known.
	 *
	 * @return string
	 */
	protected function format_amount( $amount, array $woo ) {
		$amount = (float) $amount;
		// Trim a trailing ".00" for whole amounts, keep cents otherwise.
		$number = ( floor( $amount ) === $amount ) ? number_format( $amount ) : number_format( $amount, 2 );
		$symbol = isset( $woo['currency_symbol'] ) ? (string) $woo['currency_symbol'] : '';

		if ( '' !== $symbol ) {
			return $symbol . $number;
		}

		$code = isset( $woo['currency'] ) ? (string) $woo['currency'] : '';
		return '' !== $code ? $number . ' ' . $code : $number;
	}

	/**
	 * Wrap text in a paragraph. Text is escaped; callers that embed a link build
	 * the HTML themselves and are wp_kses_post-sanitized downstream.
	 *
	 * @return string
	 */
	protected function p( $text ) {
		// Allow the few inline tags our answers use (links); escape the rest.
		return '<p>' . wp_kses( $text, [ 'a' => [ 'href' => [], 'title' => [] ] ] ) . '</p>';
	}

	/**
	 * Natural-language list join: "A", "A and B", "A, B, and C".
	 *
	 * @return string
	 */
	protected function human_list( array $items ) {
		$items = array_values( array_filter( array_map( 'trim', array_map( 'strval', $items ) ) ) );
		$count = count( $items );

		if ( 0 === $count ) {
			return '';
		}
		if ( 1 === $count ) {
			return $items[0];
		}
		if ( 2 === $count ) {
			/* translators: 1: first item, 2: second item. */
			return sprintf( __( '%1$s and %2$s', 'betterdocs' ), $items[0], $items[1] );
		}

		$last = array_pop( $items );
		/* translators: 1: comma-separated list of items, 2: final item. */
		return sprintf( __( '%1$s, and %2$s', 'betterdocs' ), implode( ', ', $items ), $last );
	}

	/**
	 * Short plain-text excerpt from an answer's HTML.
	 *
	 * @return string
	 */
	protected function excerpt( $html ) {
		return wp_trim_words( wp_strip_all_tags( $html ), 22, '…' );
	}
}
