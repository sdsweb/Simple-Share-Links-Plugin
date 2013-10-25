<?php
/**
 * This file is called on uninstallation of the plugin.
 *
 * @see http://codex.wordpress.org/Function_Reference/register_uninstall_hook
 */

// If uninstall not called from WordPress exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) 
    exit();

// Remove any options associated with the plugin
delete_option( 'sds_sb_options' );