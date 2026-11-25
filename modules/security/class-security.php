<?php
/**
 * Optimise Security Headers: adds standard browser security headers and hides usernames.
 *
 * @package OptimiseCreativeToolkit
 */

defined( 'ABSPATH' ) || exit;

final class OPTK_Security extends OPTK_Module {

	/** {@inheritdoc} */
	public function id(): string {
		return 'security';
	}

	/** {@inheritdoc} */
	public function label(): string {
		return __( 'Optimise Security Headers', 'optimise-creative-toolkit' );
	}

	/** {@inheritdoc} */
	public function description(): string {
		return __( 'Adds standard browser security headers and stops visitors finding your usernames.', 'optimise-creative-toolkit' );
	}

	/** {@inheritdoc} */
	public function fields(): array {
		return [
			[
				'key'     => 'nosniff',
				'label'   => __( 'X-Content-Type-Options: nosniff', 'optimise-creative-toolkit' ),
				'type'    => 'checkbox',
				'default' => 1,
			],
			[
				'key'     => 'frame',
				'label'   => __( 'X-Frame-Options: SAMEORIGIN', 'optimise-creative-toolkit' ),
				'type'    => 'checkbox',
				'default' => 1,
				'help'    => __( 'Stops other sites embedding yours in a frame.', 'optimise-creative-toolkit' ),
			],
			[
				'key'     => 'referrer',
				'label'   => __( 'Referrer-Policy: strict-origin-when-cross-origin', 'optimise-creative-toolkit' ),
				'type'    => 'checkbox',
				'default' => 1,
			],
			[
				'key'     => 'permissions',
				'label'   => __( 'Permissions-Policy: block camera, microphone and location', 'optimise-creative-toolkit' ),
				'type'    => 'checkbox',
				'default' => 1,
			],
			[
				'key'     => 'hsts',
				'label'   => __( 'Strict-Transport-Security (HSTS)', 'optimise-creative-toolkit' ),
				'type'    => 'checkbox',
				'default' => 0,
				'help'    => __( 'Only switch on once the whole site, including subdomains you use, works over HTTPS. Browsers remember it for a year.', 'optimise-creative-toolkit' ),
			],
			[
				'key'     => 'users',
				'label'   => __( 'Hide usernames from visitors', 'optimise-creative-toolkit' ),
				'type'    => 'checkbox',
				'default' => 1,
				'help'    => __( 'Blocks ?author= scans and the public REST users list. Logged-in users are unaffected.', 'optimise-creative-toolkit' ),
			],
		];
	}

	/** {@inheritdoc} */
	public function boot(): void {
		add_action( 'send_headers', [ $this, 'send_security_headers' ] );

		if ( $this->opt( 'users' ) ) {
			add_action( 'template_redirect', [ $this, 'block_author_scan' ], 1 );
			add_filter( 'rest_endpoints', [ $this, 'hide_rest_users' ] );
		}
	}

	/** Sends the enabled security headers with the front-end response. */
	public function send_security_headers(): void {
		if ( headers_sent() ) {
			return;
		}

		if ( $this->opt( 'nosniff' ) ) {
			header( 'X-Content-Type-Options: nosniff' );
		}

		if ( $this->opt( 'frame' ) ) {
			header( 'X-Frame-Options: SAMEORIGIN' );
		}

		if ( $this->opt( 'referrer' ) ) {
			header( 'Referrer-Policy: strict-origin-when-cross-origin' );
		}

		if ( $this->opt( 'permissions' ) ) {
			header( 'Permissions-Policy: camera=(), microphone=(), geolocation=()' );
		}

		if ( $this->opt( 'hsts' ) && is_ssl() ) {
			header( 'Strict-Transport-Security: max-age=31536000; includeSubDomains' );
		}
	}

	/** Sends logged-out visitors using ?author= to the home page. */
	public function block_author_scan(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! is_user_logged_in() && isset( $_GET['author'] ) ) {
			wp_safe_redirect( home_url( '/' ), 301 );
			exit;
		}
	}

	/**
	 * Removes the public REST users endpoints for logged-out requests.
	 *
	 * @param array|mixed $endpoints REST endpoints keyed by route.
	 * @return array|mixed
	 */
	public function hide_rest_users( $endpoints ) {
		if ( ! is_array( $endpoints ) || is_user_logged_in() ) {
			return $endpoints;
		}

		unset( $endpoints['/wp/v2/users'], $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );

		return $endpoints;
	}
}
