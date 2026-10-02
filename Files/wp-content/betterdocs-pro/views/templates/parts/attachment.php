<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }


// View template — variables come from betterdocs(_pro)()->views->get(...)
// or extract()-equivalents in the loader. Composed HTML fragments are echoed
// from internal data sources; both rules below are acknowledged-by-design
// for this template surface.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
if ( empty( $attachments_data ) || ( ! $attachment_switch ) ) {
    return;
}

$attachment_open_on_new_tab = ( $attachment_new_tab ) ? 'target="_blank"' : '';
// Label / default-name come from block/shortcode attributes; coerce to string so
// a non-string attribute cannot make strlen() throw a TypeError on PHP 8.
$attachment_label           = is_scalar( $attachment_label ) ? (string) $attachment_label : '';
$attachment_default_name    = is_scalar( $attachment_default_name ) ? (string) $attachment_default_name : '';
$attachment_label           = strlen( $attachment_label ) > 0 ? esc_attr( $attachment_label ) : __( "Attachments", "betterdocs-pro" );
echo '<div class="betterdocs-attachment-wrapper">';
echo '<p class="betterdocs-attachment-heading">' . $attachment_label . '</p>';

echo '<div class="attachment-list">';
foreach ( $attachments_data as $data ) {
    $data = json_decode( $data );
    // Skip corrupt entries (e.g. integer-coerced "0" values). Without this a
    // non-object triggers a fatal on property_exists() under PHP 8, and media
    // items with an empty id render an "(.undefined)" row and an empty box.
    if ( ! is_object( $data ) ) {
        continue;
    }
    if ( ! property_exists( $data, 'contentType' ) ) {
        if ( empty( $data->id ) ) {
            continue;
        }
        // Every field below comes from client-supplied attachment JSON, so any of
        // them can be a non-string (array/object) on a crafted or imported entry.
        // subtype/type are used as array keys — a non-scalar key fatals ("cannot
        // access offset of type array") — and name/size are cast to string, which
        // fatals on an object. Coerce all of them to strings first.
        $subtype = isset( $data->subtype ) && is_scalar( $data->subtype ) ? (string) $data->subtype : '';
        $type    = isset( $data->type ) && is_scalar( $data->type ) ? (string) $data->type : '';
        $icon_placeholder = isset( $icon_obj[$subtype]['icon_svg'] ) ? $icon_obj[$subtype]['icon_svg'] : ( isset( $icon_obj[$type]['icon_svg'] ) ? $icon_obj[$type]['icon_svg'] : '' );
        $img_placeholder  = isset( $icon_obj[$subtype]['payload'] ) ? $icon_obj[$subtype]['payload'] : ( isset( $icon_obj[$type]['payload'] ) ? $icon_obj[$type]['payload'] : '' );
        // `name` and `filesizeHumanReadable` are escaped as untrusted text rather
        // than concatenated into markup.
        $attachment_name = isset( $data->name ) && is_scalar( $data->name ) ? (string) $data->name : '';
        $attachment_size = isset( $data->filesizeHumanReadable ) && is_scalar( $data->filesizeHumanReadable ) ? (string) $data->filesizeHumanReadable : '';
        echo '<div class="attachment-details">';
        // The icon payload setting may be a string or an array; only treat it as
        // an image when it is an array carrying a url. count() on a string fatals.
        $has_icon_image = is_array( $img_placeholder ) && ! empty( $img_placeholder['url'] );
        echo ( $has_icon_image && $show_attachment_icon ) ? '<img class="icon-image" src="' . esc_url( $img_placeholder['url'] ) . '">' : ( $show_attachment_icon ? '<div class="icon-wrapper">' . $icon_placeholder . '</div>' : '' );
        echo '<a href="' . esc_url( wp_get_attachment_url( $data->id ) ) . '"' . $attachment_open_on_new_tab . '/><p class="attachment-name">' . ( strlen( $attachment_default_name ) > 0 ? esc_html( $attachment_default_name ) : esc_html( $attachment_name ) ) . '</p>' . ( $show_attachment_size ? '<p class="attachment-size">' . esc_html( $attachment_size ) . '</p>' : '' ) . '</a>';
        echo '</div>';
    } else {
        // fileurl/filename/filesize come from client-supplied attachment JSON, so
        // a crafted entry can make any of them a non-string (e.g. filesize as an
        // array). str_contains()/strlen() throw a TypeError on those in PHP 8,
        // which would fatal the whole public page render — coerce to string first.
        $fileurl  = isset( $data->fileurl ) && is_scalar( $data->fileurl ) ? (string) $data->fileurl : '';
        $filename = isset( $data->filename ) && is_scalar( $data->filename ) ? (string) $data->filename : '';
        $filesize = isset( $data->filesize ) && is_scalar( $data->filesize ) ? (string) $data->filesize : '';
        $url_string_name = str_contains( $fileurl, 'https://www' ) ? str_replace( 'https://www.', '', $fileurl ) : ( str_contains( $fileurl, 'http://www' ) ? str_replace( 'http://www', '', $fileurl ) : ( str_contains( $fileurl, 'https://' ) ? str_replace( 'https://', '', $fileurl ) : ( str_contains( $fileurl, 'http://' ) ? str_replace( 'http://', '', $fileurl ) : str_replace('www.', '',  $fileurl) ) ) );
        echo '<div class="attachment-details">';
        echo $show_attachment_icon ? '<div class="icon-wrapper">' . $icon_obj['external']['icon_svg'] . '</div>' : '';
        echo '<a href="' . esc_url( $fileurl ) . '"' . $attachment_open_on_new_tab . '/>' . ( strlen( $filename ) > 0 ? '<p class="attachment-name">' . esc_attr( $filename ) . '</p>' : '<p class="attachment-name">' . esc_attr( $url_string_name ) . '</p>' ) . (  ( strlen( $filesize ) > 0 && $show_attachment_size ) ? '<p class="attachment-size">' . esc_attr( $filesize ) . '</p>' : '' ) . '</a>';
        echo '</div>';
    }
}
echo '</div>';
echo '</div>';
