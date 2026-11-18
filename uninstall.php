<?php
/**
 * Removes the plugin's single option when it is deleted from the Plugins screen.
 * Visitors' choice cookies expire on their own.
 */
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'seo_cookies' );
