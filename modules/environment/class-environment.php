<?php
/**
 * Optimise Environment: shows which environment you are logged into.
 *
 * Colours the admin bar on local, development and staging sites, optionally
 * adds the environment to admin tab titles, and keeps non-production sites
 * out of search engines. Reads the core environment type set by
 * WP_ENVIRONMENT_TYPE in wp-config.php.
 *
 * @package OptimiseCreativeToolkit
 */

defined( 'ABSPATH' ) || exit;

final class OPTK_Environment extends OPTK_Module {

	/** {@inheritdoc} */
	public function id(): string {
		return 'environment';
	}

	/** {@inheritdoc} */
	public function label(): string {
		return 'Optimise Environment';
	}

	/** {@inheritdoc} */
	public function description(): string {
		return 'Colours the admin bar on local and staging sites so you never edit the wrong one.';
	}

	/** {@inheritdoc} */
	public function fields(): array {
		return [
			[
				'key'     => 'badge_production',
				'label'   => 'Also show a LIVE badge on production',
				'type'    => 'checkbox',
				'default' => 0,
			],
			[
				'key'     => 'title_prefix',
				'label'   => 'Add the environment to admin tab titles, e.g. [STAGING]',
				'type'    => 'checkbox',
				'default' => 1,
			],
			[
				'key'     => 'noindex',
				'label'   => 'Hide non-production sites from search engines',
				'type'    => 'checkbox',
				'default' => 1,
				'help'    => 'Adds noindex, nofollow on local, development and staging sites. Set WP_ENVIRONMENT_TYPE in wp-config.php.',
			],
		];
	}

	/** {@inheritdoc} */
	public function boot(): void {
		add_action( 'admin_bar_menu', [ $this, 'add_node' ], 1 );
		add_action( 'admin_enqueue_scripts', [ $this, 'styles' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'styles' ] );

		if ( $this->opt( 'title_prefix' ) && ! $this->is_prod() ) {
			add_filter( 'admin_title', [ $this, 'prefix_title' ] );
		}

		if ( $this->opt( 'noindex' ) && ! $this->is_prod() ) {
			add_filter( 'wp_robots', [ $this, 'set_robots' ] );
		}
	}

	/**
	 * Adds the environment badge to the top-right of the admin bar.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar Admin bar instance.
	 */
	public function add_node( $wp_admin_bar ): void {
		if ( $this->is_prod() && ! $this->opt( 'badge_production' ) ) {
			return;
		}

		$wp_admin_bar->add_node(
			[
				'id'     => 'optk-env',
				'parent' => 'top-secondary',
				'title'  => esc_html( $this->is_prod() ? 'LIVE' : strtoupper( $this->env() ) ),
				'meta'   => [ 'class' => 'optk-env' ],
			]
		);
	}

	/** Prints the admin bar colour rules for the current environment. */
	public function styles(): void {
		if ( ! is_admin_bar_showing() ) {
			return;
		}

		$colour = $this->colour();
		$css    = 'html body #wpadminbar .optk-env > .ab-item { background: ' . $colour . '; color: #fff; font-weight: 600; }';

		if ( ! $this->is_prod() ) {
			$css .= 'html body #wpadminbar { background: ' . $colour . '; }';
		}

		wp_add_inline_style( 'admin-bar', $css );
	}

	/**
	 * Prefixes the admin tab title with the environment, e.g. [STAGING].
	 *
	 * @param string|mixed $title Admin page title.
	 * @return string|mixed
	 */
	public function prefix_title( $title ) {
		if ( ! is_string( $title ) ) {
			return $title;
		}

		return '[' . strtoupper( $this->env() ) . '] ' . $title;
	}

	/**
	 * Asks search engines not to index or follow links on this site.
	 *
	 * @param array|mixed $robots Robots directives.
	 * @return array|mixed
	 */
	public function set_robots( $robots ) {
		if ( is_array( $robots ) ) {
			$robots['noindex']  = true;
			$robots['nofollow'] = true;
			unset( $robots['index'], $robots['follow'] );
		}

		return $robots;
	}

	/** Returns the core environment type: local, development, staging or production. */
	private function env(): string {
		return wp_get_environment_type();
	}

	/** Whether this site is the production environment. */
	private function is_prod(): bool {
		return 'production' === $this->env();
	}

	/** Brand colour for the current environment. */
	private function colour(): string {
		$colours = [
			'local'       => '#1a7f37',
			'development' => '#1a7f37',
			'staging'     => '#b45309',
			'production'  => '#b42318',
		];

		return $colours[ $this->env() ] ?? '#1a7f37';
	}
}
