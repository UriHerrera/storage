<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view template receives variables via extract(); prefixing is impractical.
    
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
// Get the author's ID
    $author_id = get_post_field( 'post_author', get_the_ID() );

    // Get the author's avatar with a specified size
    $avatar_size   = 40;
    $author_avatar = get_avatar( $author_id, $avatar_size );
    $authors_url   = site_url() . '/'.betterdocs()->settings->get('docs_slug').'/authors/' . $author_id . '/page/1';
?>

<a class="betterdocs-author-date" href="<?php echo esc_url( $authors_url ); ?>">
    <div class="betterdocs-author">
        <?php
            echo '<div class="author-avatar">' . wp_kses_post( $author_avatar ) . '</div>';
            echo '<span>' . esc_html( get_the_author_meta( 'display_name', $author_id ) ) . '</span>';
        ?>
    </div>
    <?php
        if ( isset( $updated_date ) && $updated_date == true ) {
            betterdocs()->views->get( 'template-parts/update-date' );
        }
    ?>
</a>
