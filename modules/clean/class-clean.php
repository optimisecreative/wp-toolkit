<?php
/**
 * Optimise Clean: switches off WordPress features most sites never use.
 *
 * @package OptimiseCreativeToolkit
 */

defined( 'ABSPATH' ) || exit;

final class OPTK_Clean extends OPTK_Module {

	/** {@inheritdoc} */
	public function id(): string {
		return 'clean';
	}

	/** {@inheritdoc} */
	public function label(): string {
		return __( 'Optimise Clean', 'optimise-creative-toolkit' );
	}

	/** {@inheritdoc} */
	public function description(): string {
		return __( 'Removes WordPress features most sites never use, for a lighter front end.', 'optimise-creative-toolkit' );
	}

	/** {@inheritdoc} */
	public function fields(): array {
		return [
			[
				'key'     => 'emojis',
				'label'   => __( 'Remove emoji scripts', 'optimise-creative-toolkit' ),
				'type'    => 'checkbox',
				'default' => 1,
			],
			[
				'key'     => 'embeds',
				'label'   => __( 'Remove oEmbed and embed script', 'optimise-creative-toolkit' ),
				'type'    => 'checkbox',
				'default' => 1,
			],
			[
				'key'     => 'xmlrpc',
				'label'   => __( 'Disable XML-RPC', 'optimise-creative-toolkit' ),
				'type'    => 'checkbox',
				'default' => 1,
			],
			[
				'key'     => 'head_links',
				'label'   => __( 'Remove unused head links', 'optimise-creative-toolkit' ),
				'type'    => 'checkbox',
				'default' => 1,
			],
			[
				'key'     => 'jquery_migrate',
				'label'   => __( 'Remove jQuery Migrate on front end', 'optimise-creative-toolkit' ),
				'type'    => 'checkbox',
				'default' => 1,
			],
			[
				'key'     => 'dashicons',
				'label'   => __( 'Remove Dashicons for guests', 'optimise-creative-toolkit' ),
				'type'    => 'checkbox',
				'default' => 1,
			],
			[
				'key'     => 'heartbeat',
				'label'   => __( 'Slow Heartbeat API to 60 seconds', 'optimise-creative-toolkit' ),
				'type'    => 'checkbox',
				'default' => 0,
			],
		];
	}

	/** {@inheritdoc} */
	public function boot(): void {
		if ( $this->opt( 'emojis' ) ) {
			remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
			remove_action( 'wp_print_styles', 'print_emoji_styles' );
			remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
			remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
			remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
			add_filter( 'tiny_mce_plugins', [ $this, 'drop_tinymce_emoji_plugin' ] );
			// Admin emoji hooks are added after plugins load, so remove them once they exist.
			add_action( 'admin_init', [ $this, 'remove_admin_emoji' ] );
			add_filter( 'wp_resource_hints', [ $this, 'drop_emoji_hint' ], 10, 2 );
		}

		if ( $this->opt( 'embeds' ) ) {
			add_action( 'wp_footer', [ $this, 'deregister_wp_embed' ] );
			remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
			remove_action( 'wp_head', 'wp_oembed_add_host_js' );
		}

		if ( $this->opt( 'xmlrpc' ) ) {
			add_filter( 'xmlrpc_enabled', '__return_false' );
			// xmlrpc_enabled only blocks methods that need a login; this removes the rest, including pingback.ping.
			add_filter( 'xmlrpc_methods', '__return_empty_array', 999 );
			remove_action( 'wp_head', 'rsd_link' );
			add_filter( 'wp_headers', [ $this, 'drop_pingback_header' ] );
		}

		if ( $this->opt( 'head_links' ) ) {
			remove_action( 'wp_head', 'wlwmanifest_link' );
			remove_action( 'wp_head', 'wp_generator' );
			remove_action( 'wp_head', 'wp_shortlink_wp_head' );
			remove_action( 'wp_head', 'rest_output_link_wp_head' );
			remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head' );
		}

		if ( $this->opt( 'jquery_migrate' ) ) {
			add_action( 'wp_default_scripts', [ $this, 'drop_jquery_migrate_dependency' ] );
		}

		if ( $this->opt( 'dashicons' ) ) {
			add_action( 'wp_enqueue_scripts', [ $this, 'dequeue_dashicons_for_guests' ] );
		}

		if ( $this->opt( 'heartbeat' ) ) {
			add_filter( 'heartbeat_settings', [ $this, 'set_heartbeat_interval' ] );
			add_action( 'init', [ $this, 'deregister_heartbeat_on_front_end' ] );
		}
	}

	/** Removes the admin emoji script and styles, which core hooks in after plugins load. */
	public function remove_admin_emoji(): void {
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
	}

	/**
	 * Drops the emoji CDN dns-prefetch hint that older WordPress versions add.
	 *
	 * @param array|mixed $urls          URLs to hint.
	 * @param string      $relation_type Hint type, e.g. 'dns-prefetch'.
	 * @return array|mixed
	 */
	public function drop_emoji_hint( $urls, $relation_type ) {
		if ( 'dns-prefetch' !== $relation_type || ! is_array( $urls ) ) {
			return $urls;
		}

		return array_values(
			array_filter(
				$urls,
				static function ( $url ) {
					$href = is_array( $url ) ? (string) ( $url['href'] ?? '' ) : (string) $url;
					return false === strpos( $href, '/images/core/emoji/' );
				}
			)
		);
	}

	/**
	 * Removes the emoji plugin from the TinyMCE plugin list.
	 *
	 * @param array|mixed $plugins TinyMCE plugin names.
	 * @return array|mixed
	 */
	public function drop_tinymce_emoji_plugin( $plugins ) {
		return is_array( $plugins ) ? array_diff( $plugins, [ 'wpemoji' ] ) : $plugins;
	}


	/** Deregisters the wp-embed script in the footer. */
	public function deregister_wp_embed(): void {
		wp_deregister_script( 'wp-embed' );
	}

	/**
	 * Removes the X-Pingback response header.
	 *
	 * @param array $headers Response headers.
	 * @return array
	 */
	public function drop_pingback_header( $headers ) {
		if ( is_array( $headers ) ) {
			unset( $headers['X-Pingback'] );
		}

		return $headers;
	}

	/**
	 * Drops the jquery-migrate dependency from jQuery on the front end.
	 *
	 * @param WP_Scripts $scripts Registered scripts.
	 */
	public function drop_jquery_migrate_dependency( $scripts ): void {
		if ( is_admin() || ! isset( $scripts->registered['jquery'] ) ) {
			return;
		}

		$jquery       = $scripts->registered['jquery'];
		$jquery->deps = array_values( array_diff( $jquery->deps, [ 'jquery-migrate' ] ) );
	}

	/** Dequeues Dashicons for logged-out visitors. */
	public function dequeue_dashicons_for_guests(): void {
		if ( ! is_user_logged_in() ) {
			wp_dequeue_style( 'dashicons' );
		}
	}

	/**
	 * Sets the Heartbeat API interval to 60 seconds.
	 *
	 * @param array $settings Heartbeat settings.
	 * @return array
	 */
	public function set_heartbeat_interval( $settings ) {
		$settings['interval'] = 60;

		return $settings;
	}

	/** Deregisters the Heartbeat script on the front end. */
	public function deregister_heartbeat_on_front_end(): void {
		if ( ! is_admin() ) {
			wp_deregister_script( 'heartbeat' );
		}
	}
}
