<?php
/**
 * Plugin Name:       Optimise Creative Toolkit
 * Plugin URI:        https://optimisecreative.com/wp-toolkit/
 * Description:       Lightweight speed, SEO, security and admin tools from Optimise Creative. Switch on only the modules you need.
 * Version:           0.1.0
 * Requires at least: 6.3
 * Requires PHP:      8.0
 * Author:            Optimise Creative
 * Author URI:        https://optimisecreative.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       optimise-creative-toolkit
 */

defined( 'ABSPATH' ) || exit;

define( 'OPTK_VERSION', '0.1.0' );
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
 * Builds a link to optimisecreative.com with UTM tags, so visits from the plugin can be measured.
 * Links only; the plugin never sends data anywhere.
 *
 * @param string $path    Path on the site, e.g. 'contact/'.
 * @param string $content Where in the plugin the link sits, e.g. 'about-card'.
 */
function optk_link( string $path = '', string $content = '' ): string {
	$args = [
		'utm_source'   => 'optimise-creative-toolkit',
		'utm_medium'   => 'plugin',
		'utm_campaign' => 'wp-admin',
	];
	if ( '' !== $content ) {
		$args['utm_content'] = $content;
	}
	return add_query_arg( $args, 'https://optimisecreative.com/' . ltrim( $path, '/' ) );
}

add_action( 'plugins_loaded', static function () {
	( new OPTK_Plugin() )->init();
} );
