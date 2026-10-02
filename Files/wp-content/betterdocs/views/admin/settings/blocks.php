<?php
	// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

?>

<div class="betterdocs-settings-documentation">
	<div class="betterdocs-settings-row">
		<div class="betterdocs-admin-block betterdocs-admin-block-docs">
			<header class="betterdocs-admin-block-header">
				<div class="betterdocs-admin-block-header-icon">
					<img src="<?php echo esc_url( betterdocs()->assets->icon( 'icons/icon-documentation.svg', true ) ); ?>" alt="betterdocs-documentation">
				</div>
			</header>
			<div class="betterdocs-admin-block-content">
				<h4 class="betterdocs-admin-title"><?php esc_html_e( 'Documentation', 'betterdocs' ); ?></h4>
				<p><?php esc_html_e( 'Get started by spending some time with the documentation to get familiar with BetterDocs. Build an awesome Knowledge Base for your customers with ease.', 'betterdocs' ); ?></p>
				<div class="betterdocs-admin-block-btn">
					<a rel="nofollow" href="https://betterdocs.co/docs/" target="_blank"><?php esc_html_e( 'Documentation', 'betterdocs' ); ?></a>
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none">
						<path d="M15.1393 7.52959L10.4726 2.86292C10.3469 2.74148 10.1785 2.67429 10.0037 2.67581C9.8289 2.67733 9.66169 2.74744 9.53809 2.87104C9.41448 2.99465 9.34437 3.16186 9.34285 3.33666C9.34133 3.51145 9.40853 3.67985 9.52997 3.80559L13.0586 7.33426H1.33464C1.15782 7.33426 0.988255 7.40449 0.863231 7.52952C0.738207 7.65454 0.667969 7.82411 0.667969 8.00092C0.667969 8.17773 0.738207 8.3473 0.863231 8.47233C0.988255 8.59735 1.15782 8.66759 1.33464 8.66759H13.0586L9.52997 12.1963C9.4663 12.2578 9.41551 12.3313 9.38057 12.4127C9.34563 12.494 9.32724 12.5815 9.32647 12.67C9.3257 12.7585 9.34257 12.8463 9.37609 12.9282C9.40961 13.0102 9.45911 13.0846 9.5217 13.1472C9.5843 13.2098 9.65873 13.2593 9.74067 13.2928C9.8226 13.3263 9.91038 13.3432 9.9989 13.3424C10.0874 13.3417 10.1749 13.3233 10.2562 13.2883C10.3376 13.2534 10.4111 13.2026 10.4726 13.1389L15.1393 8.47226C15.2643 8.34724 15.3345 8.1777 15.3345 8.00092C15.3345 7.82415 15.2643 7.65461 15.1393 7.52959Z" fill="#00B884"/>
					</svg>
				</div>
			</div>
		</div>
		<div class="betterdocs-admin-block betterdocs-admin-block-contribute">
			<header class="betterdocs-admin-block-header">
				<div class="betterdocs-admin-block-header-icon">
					<img src="<?php echo esc_url( betterdocs()->assets->icon( 'icons/icon-join-community.svg', true ) ); ?>" alt="betterdocs-contribute">
				</div>
			</header>
			<div class="betterdocs-admin-block-content">
				<h4 class="betterdocs-admin-title"><?php esc_html_e( 'Join Our Community', 'betterdocs' ); ?></h4>
				<p><?php echo esc_html__( 'Join the Facebook community and discuss with fellow developers and users. Best way to connect with people and get feedback on your projects.', 'betterdocs' ); ?></p>
				<div class="betterdocs-admin-block-btn">
					<a rel="nofollow" href="https://www.facebook.com/groups/wpdeveloper.net/" target="_blank"><?php esc_html_e( 'Join Now', 'betterdocs' ); ?></a>
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none">
						<path d="M15.1393 7.52959L10.4726 2.86292C10.3469 2.74148 10.1785 2.67429 10.0037 2.67581C9.8289 2.67733 9.66169 2.74744 9.53809 2.87104C9.41448 2.99465 9.34437 3.16186 9.34285 3.33666C9.34133 3.51145 9.40853 3.67985 9.52997 3.80559L13.0586 7.33426H1.33464C1.15782 7.33426 0.988255 7.40449 0.863231 7.52952C0.738207 7.65454 0.667969 7.82411 0.667969 8.00092C0.667969 8.17773 0.738207 8.3473 0.863231 8.47233C0.988255 8.59735 1.15782 8.66759 1.33464 8.66759H13.0586L9.52997 12.1963C9.4663 12.2578 9.41551 12.3313 9.38057 12.4127C9.34563 12.494 9.32724 12.5815 9.32647 12.67C9.3257 12.7585 9.34257 12.8463 9.37609 12.9282C9.40961 13.0102 9.45911 13.0846 9.5217 13.1472C9.5843 13.2098 9.65873 13.2593 9.74067 13.2928C9.8226 13.3263 9.91038 13.3432 9.9989 13.3424C10.0874 13.3417 10.1749 13.3233 10.2562 13.2883C10.3376 13.2534 10.4111 13.2026 10.4726 13.1389L15.1393 8.47226C15.2643 8.34724 15.3345 8.1777 15.3345 8.00092C15.3345 7.82415 15.2643 7.65461 15.1393 7.52959Z" fill="#00B884"/>
					</svg>
				</div>
			</div>
		</div>
		<div class="betterdocs-admin-block betterdocs-admin-block-need-help">
			<header class="betterdocs-admin-block-header">
				<div class="betterdocs-admin-block-header-icon">
					<img src="<?php echo esc_url( betterdocs()->assets->icon( 'icons/icon-need-help.svg', true ) ); ?>" alt="betterdocs-help">
				</div>
			</header>
			<div class="betterdocs-admin-block-content">
				<h4 class="betterdocs-admin-title"><?php esc_html_e( 'Need Help?', 'betterdocs' ); ?></h4>
				<p><?php esc_html_e( 'Stuck with something? Get help from live chat or support ticket.', 'betterdocs' ); ?></p>
				<div class="betterdocs-admin-block-btn">
					<a rel="nofollow" href="https://wpdeveloper.com/support" target="_blank"><?php esc_html_e( 'Initiate a Chat', 'betterdocs' ); ?></a>
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none">
						<path d="M15.1393 7.52959L10.4726 2.86292C10.3469 2.74148 10.1785 2.67429 10.0037 2.67581C9.8289 2.67733 9.66169 2.74744 9.53809 2.87104C9.41448 2.99465 9.34437 3.16186 9.34285 3.33666C9.34133 3.51145 9.40853 3.67985 9.52997 3.80559L13.0586 7.33426H1.33464C1.15782 7.33426 0.988255 7.40449 0.863231 7.52952C0.738207 7.65454 0.667969 7.82411 0.667969 8.00092C0.667969 8.17773 0.738207 8.3473 0.863231 8.47233C0.988255 8.59735 1.15782 8.66759 1.33464 8.66759H13.0586L9.52997 12.1963C9.4663 12.2578 9.41551 12.3313 9.38057 12.4127C9.34563 12.494 9.32724 12.5815 9.32647 12.67C9.3257 12.7585 9.34257 12.8463 9.37609 12.9282C9.40961 13.0102 9.45911 13.0846 9.5217 13.1472C9.5843 13.2098 9.65873 13.2593 9.74067 13.2928C9.8226 13.3263 9.91038 13.3432 9.9989 13.3424C10.0874 13.3417 10.1749 13.3233 10.2562 13.2883C10.3376 13.2534 10.4111 13.2026 10.4726 13.1389L15.1393 8.47226C15.2643 8.34724 15.3345 8.1777 15.3345 8.00092C15.3345 7.82415 15.2643 7.65461 15.1393 7.52959Z" fill="#00B884"/>
					</svg>
				</div>
			</div>
		</div>
		<div class="betterdocs-admin-block betterdocs-admin-block-community">
			<header class="betterdocs-admin-block-header">
				<div class="betterdocs-admin-block-header-icon">
					<img src="<?php echo esc_url( betterdocs()->assets->icon( 'icons/icon-show-love.svg', true ) ); ?>" alt="betterdocs-commuinity">
				</div>
			</header>
			<div class="betterdocs-admin-block-content">
				<h4 class="betterdocs-admin-title"><?php esc_html_e( 'Show Your Love', 'betterdocs' ); ?></h4>
				<p><?php esc_html_e( 'We love to have you in BetterDocs family. We are making it more awesome everyday. Take your 2 minutes to review the plugin and spread the love to encourage us to keep it going.', 'betterdocs' ); ?></p>
				<div class="betterdocs-admin-block-btn">
					<a rel="nofollow" href="https://betterdocs.co/wp/review" target="_blank"><?php esc_html_e( 'Leave a Review', 'betterdocs' ); ?></a>
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none">
						<path d="M15.1393 7.52959L10.4726 2.86292C10.3469 2.74148 10.1785 2.67429 10.0037 2.67581C9.8289 2.67733 9.66169 2.74744 9.53809 2.87104C9.41448 2.99465 9.34437 3.16186 9.34285 3.33666C9.34133 3.51145 9.40853 3.67985 9.52997 3.80559L13.0586 7.33426H1.33464C1.15782 7.33426 0.988255 7.40449 0.863231 7.52952C0.738207 7.65454 0.667969 7.82411 0.667969 8.00092C0.667969 8.17773 0.738207 8.3473 0.863231 8.47233C0.988255 8.59735 1.15782 8.66759 1.33464 8.66759H13.0586L9.52997 12.1963C9.4663 12.2578 9.41551 12.3313 9.38057 12.4127C9.34563 12.494 9.32724 12.5815 9.32647 12.67C9.3257 12.7585 9.34257 12.8463 9.37609 12.9282C9.40961 13.0102 9.45911 13.0846 9.5217 13.1472C9.5843 13.2098 9.65873 13.2593 9.74067 13.2928C9.8226 13.3263 9.91038 13.3432 9.9989 13.3424C10.0874 13.3417 10.1749 13.3233 10.2562 13.2883C10.3376 13.2534 10.4111 13.2026 10.4726 13.1389L15.1393 8.47226C15.2643 8.34724 15.3345 8.1777 15.3345 8.00092C15.3345 7.82415 15.2643 7.65461 15.1393 7.52959Z" fill="#00B884"/>
					</svg>
				</div>
			</div>
		</div>
	</div>
</div>
