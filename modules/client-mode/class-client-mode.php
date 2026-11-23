<?php
/**
 * Optimise Client Mode: restricts client admin accounts, leaving developer accounts untouched.
 *
 * @package OptimiseCreativeToolkit
 */

defined( 'ABSPATH' ) || exit;

final class OPTK_Client_Mode extends OPTK_Module {

	/**
	 * Cached result of is_restricted() for this request.
	 *
	 * @var bool|null
	 */
	private ?bool $restricted = null;

	/** {@inheritdoc} */
	public function id(): string {
		return 'client-mode';
	}

	/** {@inheritdoc} */
	public function label(): string {
		return 'Optimise Client Mode';
	}

	/** {@inheritdoc} */
	public function description(): string {
		return 'Simplifies the dashboard for clients and stops them changing plugins, themes or core.';
	}

	/** {@inheritdoc} */
	public function fields(): array {
		return [
			[
				'key'     => 'developers',
				'label'   => 'Developer usernames',
				'type'    => 'textarea',
				'default' => '',
				'help'    => 'One per line or comma-separated. These accounts are never restricted. If empty, nobody is restricted.',
			],
			[
				'key'     => 'hide_menus',
				'label'   => 'Hide Plugins, Appearance → Themes, Tools and Settings menus',
				'type'    => 'checkbox',
				'default' => 1,
			],
			[
				'key'     => 'block_caps',
				'label'   => 'Block installing, updating, editing and switching plugins, themes and core',
				'type'    => 'checkbox',
				'default' => 1,
			],
			[
				'key'     => 'widget',
				'label'   => 'Show a support widget on the dashboard',
				'type'    => 'checkbox',
				'default' => 1,
			],
			[
				'key'     => 'support_name',
				'label'   => 'Support name',
				'type'    => 'text',
				'default' => '',
			],
			[
				'key'     => 'support_email',
				'label'   => 'Support email',
				'type'    => 'text',
				'default' => '',
			],
			[
				'key'     => 'support_phone',
				'label'   => 'Support phone',
				'type'    => 'text',
				'default' => '',
			],
			[
				'key'     => 'welcome',
				'label'   => 'Widget message',
				'type'    => 'textarea',
				'default' => 'Need a hand with your website? Get in touch and we will sort it.',
			],
		];
	}

	/** {@inheritdoc} */
	public function boot(): void {
		if ( $this->opt( 'hide_menus' ) ) {
			add_action( 'admin_menu', [ $this, 'hide_admin_menus' ], 999 );
		}

		if ( $this->opt( 'block_caps' ) ) {
			add_filter( 'user_has_cap', [ $this, 'filter_caps' ], 10, 4 );
		}

		if ( $this->opt( 'widget' ) ) {
			add_action( 'wp_dashboard_setup', [ $this, 'add_dashboard_widget' ] );
		}
	}

	/**
	 * Whether the current user is subject to Client Mode restrictions.
	 *
	 * Developer accounts, and every visitor when no developers are listed, are never restricted.
	 * Does not call current_user_can(), so it is safe to use inside user_has_cap.
	 *
	 * @return bool
	 */
	private function is_restricted(): bool {
		if ( null !== $this->restricted ) {
			return $this->restricted;
		}

		if ( ! is_user_logged_in() ) {
			$this->restricted = false;

			return $this->restricted;
		}

		$names = preg_split( '/[\s,]+/', strtolower( (string) $this->opt( 'developers', '' ) ), -1, PREG_SPLIT_NO_EMPTY );
		$names = is_array( $names ) ? $names : [];

		if ( empty( $names ) ) {
			$this->restricted = false;

			return $this->restricted;
		}

		$this->restricted = ! in_array( strtolower( wp_get_current_user()->user_login ), $names, true );

		return $this->restricted;
	}

	/** Removes the plugin, theme, tool and settings menus for restricted users. */
	public function hide_admin_menus(): void {
		if ( ! $this->is_restricted() ) {
			return;
		}

		remove_menu_page( 'plugins.php' );
		remove_menu_page( 'tools.php' );
		remove_menu_page( 'options-general.php' );
		remove_submenu_page( 'themes.php', 'themes.php' );
		remove_submenu_page( 'themes.php', 'theme-editor.php' );
	}

	/**
	 * Revokes plugin, theme and core management capabilities for restricted users.
	 *
	 * manage_options is deliberately left alone so clients keep access to their content and settings.
	 *
	 * @param array   $allcaps All capabilities the user has, keyed by name.
	 * @param array   $caps    Capabilities being checked.
	 * @param array   $args    Additional arguments passed to the check.
	 * @param WP_User $user    The user being checked.
	 * @return array
	 */
	public function filter_caps( $allcaps, $caps, $args, $user ) {
		if ( ! is_array( $allcaps ) || ! is_object( $user ) ) {
			return $allcaps;
		}

		if ( ! $this->is_restricted() || (int) $user->ID !== get_current_user_id() ) {
			return $allcaps;
		}

		$blocked = [
			'install_plugins',
			'activate_plugins',
			'update_plugins',
			'delete_plugins',
			'edit_plugins',
			'install_themes',
			'switch_themes',
			'update_themes',
			'delete_themes',
			'edit_themes',
			'update_core',
		];

		foreach ( $blocked as $cap ) {
			if ( isset( $allcaps[ $cap ] ) ) {
				$allcaps[ $cap ] = false;
			}
		}

		return $allcaps;
	}

	/** Adds the support widget to the dashboard for restricted users. */
	public function add_dashboard_widget(): void {
		if ( $this->is_restricted() ) {
			wp_add_dashboard_widget( 'optk_client_support', 'Website support', [ $this, 'render_widget' ] );
		}
	}

	/** Renders the support widget: the welcome message and the support contact details. */
	public function render_widget(): void {
		$welcome = trim( (string) $this->opt( 'welcome', '' ) );

		if ( '' !== $welcome ) {
			echo '<p>' . nl2br( esc_html( $welcome ) ) . '</p>';
		}

		$name  = trim( (string) $this->opt( 'support_name', '' ) );
		$email = trim( (string) $this->opt( 'support_email', '' ) );
		$phone = trim( (string) $this->opt( 'support_phone', '' ) );
		$items = [];

		if ( '' !== $name ) {
			$items[] = '<strong>' . esc_html( $name ) . '</strong>';
		}

		if ( '' !== $email ) {
			$safe_email = antispambot( $email );
			$items[]    = '<a href="mailto:' . esc_attr( $safe_email ) . '">' . esc_html( $safe_email ) . '</a>';
		}

		if ( '' !== $phone ) {
			$tel     = preg_replace( '/\s+/', '', $phone );
			$items[] = '<a href="tel:' . esc_attr( (string) $tel ) . '">' . esc_html( $phone ) . '</a>';
		}

		if ( ! empty( $items ) ) {
			echo wp_kses(
				'<p>' . implode( '<br />', $items ) . '</p>',
				[
					'p'      => [],
					'br'     => [],
					'strong' => [],
					'a'      => [ 'href' => [] ],
				],
				[ 'http', 'https', 'mailto', 'tel' ]
			);
		}
	}
}
