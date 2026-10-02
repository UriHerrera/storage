<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }


// View template — variables come from betterdocs(_pro)()->views->get(...)
// or extract()-equivalents in the loader. Composed HTML fragments are echoed
// from internal data sources; both rules below are acknowledged-by-design
// for this template surface.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
$shortcode_atts = betterdocs()->template_helper->shortcode_atts( [
    'attachment_heading_label'    => $attachment_heading_label,
    'default_attachment_name'     => $default_attachment_name,
    'show_attachment_icon'        => $show_attachment_icon,
    'show_attachment_size'        => $show_attachment_size,
    'show_attachments_in_new_tab' => $show_attachments_in_new_tab,
    'show_attachments'            => true
], '', '' );
echo do_shortcode("[betterdocs_attachments $shortcode_atts]");
