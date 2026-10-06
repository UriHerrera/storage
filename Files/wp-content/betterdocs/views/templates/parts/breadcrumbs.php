<?php


if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
if ( ! betterdocs()->settings->get( 'enable_breadcrumb' ) ) {
	return;
}

$view_object->get( 'widgets/breadcrumbs' );
