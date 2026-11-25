<?php
/**
 * Optimise Mail From: sends site emails from your business name and address.
 *
 * @package OptimiseCreativeToolkit
 */

defined( 'ABSPATH' ) || exit;

final class OPTK_Mail_From extends OPTK_Module {

	/** {@inheritdoc} */
	public function id(): string {
		return 'mail-from';
	}

	/** {@inheritdoc} */
	public function label(): string {
		return __( 'Optimise Mail From', 'optimise-creative-toolkit' );
	}

	/** {@inheritdoc} */
	public function description(): string {
		return __( 'Sends site emails from your business name and address instead of "WordPress".', 'optimise-creative-toolkit' );
	}

	/** {@inheritdoc} */
	public function fields(): array {
		return [
			[
				'key'     => 'from_name',
				'label'   => __( 'From name', 'optimise-creative-toolkit' ),
				'type'    => 'text',
				'default' => '',
				'help'    => __( 'Leave blank to use the site title.', 'optimise-creative-toolkit' ),
			],
			[
				'key'     => 'from_email',
				'label'   => __( 'From email', 'optimise-creative-toolkit' ),
				'type'    => 'text',
				'default' => '',
				'help'    => __( 'Use an address on this site\'s domain so emails are not flagged as spam. Leave blank to keep the WordPress default.', 'optimise-creative-toolkit' ),
			],
			[
				'key'     => 'only_default',
				'label'   => __( 'Only replace the WordPress defaults', 'optimise-creative-toolkit' ),
				'type'    => 'checkbox',
				'default' => 1,
				'help'    => __( 'Leaves alone emails where another plugin has already set a sender, such as contact forms.', 'optimise-creative-toolkit' ),
			],
		];
	}

	/** {@inheritdoc} */
	public function boot(): void {
		add_filter( 'wp_mail_from_name', [ $this, 'filter_name' ], 20 );
		add_filter( 'wp_mail_from', [ $this, 'filter_email' ], 20 );
	}

	/**
	 * Replaces the sender name with the custom name, or the site title if blank.
	 *
	 * @param string $name Sender name proposed by WordPress or another plugin.
	 * @return string
	 */
	public function filter_name( $name ) {
		if ( $this->opt( 'only_default' ) && 'WordPress' !== $name ) {
			return $name;
		}

		$custom = trim( (string) $this->opt( 'from_name' ) );

		return '' !== $custom ? $custom : wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	}

	/**
	 * Replaces the sender address with the custom address when it is valid.
	 *
	 * @param string $email Sender address proposed by WordPress or another plugin.
	 * @return string
	 */
	public function filter_email( $email ) {
		$custom = sanitize_email( (string) $this->opt( 'from_email' ) );

		if ( ! is_email( $custom ) ) {
			return $email;
		}

		if ( $this->opt( 'only_default' ) && ! str_starts_with( (string) $email, 'wordpress@' ) ) {
			return $email;
		}

		return $custom;
	}
}
