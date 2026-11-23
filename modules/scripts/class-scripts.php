<?php
/**
 * Optimise Scripts: prints tracking and verification code in the head, after the body tag and in the footer.
 *
 * @package OptimiseCreativeToolkit
 */

defined( 'ABSPATH' ) || exit;

final class OPTK_Scripts extends OPTK_Module {

	/** {@inheritdoc} */
	public function id(): string {
		return 'scripts';
	}

	/** {@inheritdoc} */
	public function label(): string {
		return 'Optimise Scripts';
	}

	/** {@inheritdoc} */
	public function description(): string {
		return 'Adds tracking and verification code (GA4, Tag Manager, Meta Pixel, cookie banners) without editing the theme.';
	}

	/** {@inheritdoc} */
	public function fields(): array {
		return [
			[
				'key'     => 'head',
				'label'   => 'Head code',
				'type'    => 'code',
				'default' => '',
				'help'    => 'Printed inside <head>. Use for GA4, Tag Manager, verification tags.',
			],
			[
				'key'     => 'body',
				'label'   => 'Body open code',
				'type'    => 'code',
				'default' => '',
				'help'    => 'Printed right after <body>. Use for the Tag Manager noscript tag. Needs a theme that supports wp_body_open.',
			],
			[
				'key'     => 'footer',
				'label'   => 'Footer code',
				'type'    => 'code',
				'default' => '',
				'help'    => 'Printed before </body>. Use for chat widgets and pixels.',
			],
			[
				'key'     => 'skip_logged_in',
				'label'   => 'Do not print code for logged-in admins and editors',
				'type'    => 'checkbox',
				'default' => 1,
				'help'    => 'Keeps your own visits out of analytics.',
			],
		];
	}

	/** {@inheritdoc} */
	public function boot(): void {
		add_action( 'wp_head', [ $this, 'print_head' ], 1 );
		add_action( 'wp_body_open', [ $this, 'print_body' ], 1 );
		add_action( 'wp_footer', [ $this, 'print_footer' ], 100 );
	}

	/** Prints the head code. */
	public function print_head(): void {
		$this->output( 'head' );
	}

	/** Prints the body open code. */
	public function print_body(): void {
		$this->output( 'body' );
	}

	/** Prints the footer code. */
	public function print_footer(): void {
		$this->output( 'footer' );
	}

	/**
	 * Echoes the saved code for one slot, wrapped in marker comments.
	 *
	 * @param string $key Field key: 'head', 'body' or 'footer'.
	 */
	private function output( string $key ): void {
		if ( is_admin() || ( $this->opt( 'skip_logged_in' ) && current_user_can( 'edit_posts' ) ) ) {
			return;
		}

		$code = (string) $this->opt( $key, '' );
		if ( '' === trim( $code ) ) {
			return;
		}

		// Only unfiltered_html users can save this field, so it is printed as stored.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo "\n<!-- Optimise Scripts -->\n" . $code . "\n<!-- /Optimise Scripts -->\n";
	}
}
