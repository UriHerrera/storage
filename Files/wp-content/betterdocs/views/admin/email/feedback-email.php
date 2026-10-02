<strong><?php 
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
esc_html_e( 'Name', 'betterdocs' ); ?></strong>: <?php echo esc_html( $name ); ?> <br>
<strong><?php esc_html_e( 'Docs', 'betterdocs' ); ?></strong>: <?php echo esc_html( $article ); ?> <br>
<?php echo esc_html( $message_text ); ?>
