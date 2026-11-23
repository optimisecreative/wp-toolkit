<?php
/**
 * Removes all Optimise Creative Toolkit data on uninstall.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'optimise_toolkit' );
