<?php

/**
 * Uninstall Function
 *
 * This file is used as it is preferred over register_uninstall_hook.
 * http://www.wptavern.com/plugin-developers-use-uninstall-php-please
 */

// If uninstall not called from WordPress, exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit();

// Include main functions file so we have access to Admin_Screen_Search class
include_once( 'admin-screen-search.php' );

Admin_Screen_Search::uninstall();