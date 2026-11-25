<?php
/**
 * Optimise Login: brands the WordPress login screen.
 */

defined( 'ABSPATH' ) || exit;

final class OPTK_Login extends OPTK_Module {

	public function id(): string {
		return 'login';
	}

	public function label(): string {
		return __( 'Optimise Login', 'optimise-creative-toolkit' );
	}

	public function description(): string {
		return __( 'Brands the login screen with your logo and colours.', 'optimise-creative-toolkit' );
	}

	public function fields(): array {
		return [
			[
				'key'     => 'logo_url',
				'label'   => __( 'Logo URL', 'optimise-creative-toolkit' ),
				'type'    => 'url',
				'default' => '',
				'help'    => __( 'Leave blank to use the site icon, or the site name if none is set.', 'optimise-creative-toolkit' ),
			],
			[
				'key'     => 'logo_width',
				'label'   => __( 'Logo width (px)', 'optimise-creative-toolkit' ),
				'type'    => 'number',
				'default' => 200,
			],
			[
				'key'     => 'accent',
				'label'   => __( 'Accent colour', 'optimise-creative-toolkit' ),
				'type'    => 'color',
				'default' => '#6C4860',
			],
			[
				'key'     => 'background',
				'label'   => __( 'Background colour', 'optimise-creative-toolkit' ),
				'type'    => 'color',
				'default' => '#FCF9F4',
			],
			[
				'key'     => 'credit',
				'label'   => __( 'Show "Site by Optimise Creative" link on the login page', 'optimise-creative-toolkit' ),
				'type'    => 'checkbox',
				'default' => 0,
			],
		];
	}

	public function boot(): void {
		add_action( 'login_enqueue_scripts', [ $this, 'enqueue' ] );
		add_filter( 'login_headerurl', [ $this, 'header_url' ] );
		add_filter( 'login_headertext', [ $this, 'header_text' ] );

		if ( $this->opt( 'credit' ) ) {
			add_action( 'login_footer', [ $this, 'credit' ] );
		}
	}

	/** Enqueue the stylesheet and the colour and logo variables. */
	public function enqueue(): void {
		wp_enqueue_style( 'optk-login', $this->url( 'login.css' ), [], OPTK_VERSION );
		wp_add_inline_style( 'optk-login', $this->inline_css() );
	}

	/** Build the inline CSS that sets the login custom properties. */
	private function inline_css(): string {
		$accent     = sanitize_hex_color( (string) $this->opt( 'accent', '#6C4860' ) ) ?: '#6C4860';
		$background = sanitize_hex_color( (string) $this->opt( 'background', '#FCF9F4' ) ) ?: '#FCF9F4';
		$width      = absint( $this->opt( 'logo_width', 200 ) ) ?: 200;

		$css = sprintf(
			'body.login { --optk-accent: %1$s; --optk-bg: %2$s; --optk-logo-w: %3$dpx; }',
			$accent,
			$background,
			$width
		);

		$logo = (string) $this->opt( 'logo_url', '' );
		if ( '' === $logo ) {
			$logo = (string) get_site_icon_url( 192 );
		}
		$logo = esc_url( $logo );

		if ( '' !== $logo ) {
			$css .= sprintf( ' body.login { --optk-logo: url("%s"); }', $logo );
		} else {
			// No logo available: show the site name as text instead.
			$css .= ' .login h1 a { background-image: none; text-indent: 0; width: auto; height: auto; font-size: 24px; }';
		}

		return $css;
	}

	/** Link the login logo to the home page. */
	public function header_url(): string {
		return home_url( '/' );
	}

	/** Use the site name as the login logo text. */
	public function header_text(): string {
		return get_bloginfo( 'name' );
	}

	/** Print the credit line under the login form. */
	public function credit(): void {
		printf(
			'<p class="optk-credit">%s <a href="%s" target="_blank" rel="noopener">Optimise Creative</a></p>',
			esc_html__( 'Site by', 'optimise-creative-toolkit' ),
			esc_url( optk_link( '' ) )
		);
	}
}
