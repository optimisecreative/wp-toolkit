<?php
/**
 * Plugin Name:       Optimise Creative Toolkit
 * Plugin URI:        https://optimisecreative.com/wp-toolkit/
 * Description:       Lightweight speed, SEO, security and admin tools from Optimise Creative. Switch on only the modules you need.
 * Version:           1.0.1
 * Requires at least: 6.3
 * Requires PHP:      8.0
 * Author:            Optimise Creative
 * Author URI:        https://optimisecreative.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       optimise-creative-toolkit
 */

defined( 'ABSPATH' ) || exit;

define( 'OPTK_VERSION', '1.0.1' );
define( 'OPTK_FILE', __FILE__ );
define( 'OPTK_DIR', plugin_dir_path( __FILE__ ) );
define( 'OPTK_URL', plugin_dir_url( __FILE__ ) );
define( 'OPTK_OPTION', 'optimise_toolkit' );

require_once OPTK_DIR . 'includes/abstract-module.php';
require_once OPTK_DIR . 'includes/class-settings.php';
require_once OPTK_DIR . 'includes/class-transfer.php';
require_once OPTK_DIR . 'includes/class-plugin.php';

foreach ( glob( OPTK_DIR . 'modules/*/class-*.php' ) as $optk_module_file ) {
	require_once $optk_module_file;
}
unset( $optk_module_file );

/**
 * Returns a plain link to a page on optimisecreative.com. No tracking parameters are added.
 *
 * @param string $path Path on the site, e.g. 'contact/'.
 */
function optk_link( string $path = '' ): string {
	return 'https://optimisecreative.com/' . ltrim( $path, '/' );
}

// Boot on init: module settings use translated strings, which WordPress expects to load no earlier than init.
add_action( 'init', static function () {
	( new OPTK_Plugin() )->init();
}, 0 );
