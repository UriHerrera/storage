<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view template receives variables via extract(); prefixing is impractical.

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
if ( $total_pages <= 1 ) {
	return;
}

if ( $template != 'doc_category' ) {
	return;
}

	// Number of page links to show before and after current page
	$links_to_show = 2;
	// Current page, fallback to 1 if not set
	$current_page = $current_page ?: 1;
?>

<div class="betterdocs-pagination">
	<ul>
		<?php if ( $current_page > 1 ) : ?>
			<li class="prev">
				<a href="<?php echo esc_url( $link ) . 'page/' . ( intval( $current_page ) - 1 ); ?>">❮</a>
			</li>
		<?php endif; ?>

		<?php
		// Always show first page
		$pages_to_show = [ 1 ];

		// Calculate start and end of the middle range
		$start = max( 2, $current_page - $links_to_show );
		$end   = min( $total_pages - 1, $current_page + $links_to_show );

		// Add ellipsis after first page if needed
		if ( $start > 2 ) {
			$pages_to_show[] = '...';
		}

		// Add middle range pages
		for ( $i = $start; $i <= $end; $i++ ) {
			$pages_to_show[] = $i;
		}

		// Add ellipsis before last page if needed
		if ( $end < $total_pages - 1 ) {
			$pages_to_show[] = '...';
		}

		// Always show last page if total pages > 1
		if ( $total_pages > 1 ) {
			$pages_to_show[] = $total_pages;
		}

		// Output the page links
		foreach ( $pages_to_show as $i ) :
			if ( $i === '...' ) :
				?>
				<li class="ellipsis"><span><?php echo esc_html( $i ); ?></span></li>
				<?php
			else :
				?>
				<li class="<?php echo $current_page == $i ? 'active' : ''; ?>">
					<a href="<?php echo esc_url( $link ) . 'page/' . esc_html( $i ); ?>"><?php echo esc_html( $i ); ?></a>
				</li>
				<?php
			endif;
		endforeach;
		?>

		<?php if ( $current_page < $total_pages ) : ?>
			<li class="next">
				<a href="<?php echo esc_url( $link ) . 'page/' . ( intval( $current_page ) + 1 ); ?>">❯</a>
			</li>
		<?php endif; ?>
	</ul>
</div>
