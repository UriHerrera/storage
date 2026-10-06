<?php
// Compatibility entry point for layouts and extensions that still request sidebar-7.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

betterdocs()->views->get( 'templates/sidebars/documentation-tree', get_defined_vars() );
