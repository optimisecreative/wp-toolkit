<?php
/**
 * Settings page: Settings → Optimise Creative Toolkit.
 *
 * Stores everything in one option:
 * [
 *   'enabled' => [ 'clean', 'login' ],
 *   'modules' => [ 'clean' => [ 'emojis' => 1, ... ], 'login' => [ ... ] ],
 * ]
 */

defined( 'ABSPATH' ) || exit;

final class OPTK_Settings {

	private const PAGE = 'optimise-creative-toolkit';

	/**
	 * Branded page header shared by every toolkit screen.
	 *
	 * @param string $title    Page title (already translated).
	 * @param string $subtitle Optional line under the title (already translated).
	 */
	public static function header( string $title, string $subtitle = '' ): void {
		?>
		<div class="optk-header">
			<a class="optk-header-logo" href="<?php echo esc_url( optk_link( '' ) ); ?>" target="_blank" rel="noopener">
				<img src="<?php echo esc_url( OPTK_URL . 'assets/logo.png' ); ?>" width="64" height="37" alt="<?php esc_attr_e( 'Optimise Creative website (opens in a new tab)', 'optimise-creative-toolkit' ); ?>">
			</a>
			<div class="optk-header-text">
				<h1><?php echo esc_html( $title ); ?></h1>
				<?php if ( '' !== $subtitle ) : ?>
					<p><?php echo esc_html( $subtitle ); ?></p>
				<?php endif; ?>
			</div>
			<span class="optk-badge">
				<?php
				/* translators: %s: plugin version number. */
				printf( esc_html__( 'v%s', 'optimise-creative-toolkit' ), esc_html( OPTK_VERSION ) );
				?>
			</span>
		</div>
		<?php
	}

	/** @var OPTK_Module[] */
	private array $modules;

	public function __construct( array $modules ) {
		$this->modules = $modules;
	}

	public function init(): void {
		add_action( 'admin_menu', [ $this, 'menu' ] );
		add_action( 'admin_init', [ $this, 'register' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'assets' ] );
		add_filter( 'plugin_action_links_' . plugin_basename( OPTK_FILE ), [ $this, 'action_link' ] );
		( new OPTK_Transfer() )->init();
	}

	public function menu(): void {
		add_options_page(
			__( 'Optimise Creative Toolkit', 'optimise-creative-toolkit' ),
			__( 'Optimise Creative Toolkit', 'optimise-creative-toolkit' ),
			'manage_options',
			self::PAGE,
			[ $this, 'render' ]
		);
	}

	public function register(): void {
		register_setting( self::PAGE, OPTK_OPTION, [
			'type'              => 'array',
			'sanitize_callback' => [ $this, 'sanitize' ],
			'default'           => [ 'enabled' => [], 'modules' => [] ],
		] );
	}

	public function assets( string $hook ): void {
		if ( 'settings_page_' . self::PAGE !== $hook ) {
			return;
		}
		wp_enqueue_style( 'optk-admin', OPTK_URL . 'assets/admin.css', [], OPTK_VERSION );
	}

	public function action_link( array $links ): array {
		$url = admin_url( 'options-general.php?page=' . self::PAGE );
		array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'optimise-creative-toolkit' ) . '</a>' );
		return $links;
	}

	public function sanitize( $input ): array {
		$input = is_array( $input ) ? $input : [];
		$clean = [ 'enabled' => [], 'modules' => [] ];

		foreach ( (array) ( $input['enabled'] ?? [] ) as $id ) {
			$id = sanitize_key( $id );
			if ( isset( $this->modules[ $id ] ) ) {
				$clean['enabled'][] = $id;
			}
		}

		foreach ( $this->modules as $id => $module ) {
			$raw = $input['modules'][ $id ] ?? [];
			foreach ( $module->fields() as $field ) {
				$key = $field['key'];
				$clean['modules'][ $id ][ $key ] = $this->sanitize_field( $field, $raw[ $key ] ?? null );
			}
		}

		return $clean;
	}

	private function sanitize_field( array $field, $value ) {
		switch ( $field['type'] ) {
			case 'checkbox':
				return empty( $value ) ? 0 : 1;
			case 'number':
				return (int) $value;
			case 'url':
				return esc_url_raw( (string) $value );
			case 'color':
				return sanitize_hex_color( (string) $value ) ?: ( $field['default'] ?? '' );
			case 'textarea':
				return sanitize_textarea_field( (string) $value );
			case 'select':
				return array_key_exists( (string) $value, $field['options'] ?? [] ) ? (string) $value : ( $field['default'] ?? '' );
			default:
				return sanitize_text_field( (string) $value );
		}
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$saved   = get_option( OPTK_OPTION, [] );
		$enabled = (array) ( $saved['enabled'] ?? [] );
		?>
		<div class="wrap optk-wrap">
			<?php self::header( __( 'Optimise Creative Toolkit', 'optimise-creative-toolkit' ), __( 'Switch on the modules this site needs. Modules you leave off do nothing to your site.', 'optimise-creative-toolkit' ) ); ?>

			<form method="post" action="options.php">
				<?php settings_fields( self::PAGE ); ?>
				<div class="optk-cards">
					<?php foreach ( $this->modules as $id => $module ) :
						$is_on = in_array( $id, $enabled, true );
						$base  = OPTK_OPTION . '[modules][' . $id . ']';
						?>
						<div class="optk-card<?php echo $is_on ? ' is-on' : ''; ?>">
							<label class="optk-toggle">
								<input type="checkbox" name="<?php echo esc_attr( OPTK_OPTION ); ?>[enabled][]" value="<?php echo esc_attr( $id ); ?>" <?php checked( $is_on ); ?>>
								<span class="optk-card-title"><?php echo esc_html( $module->label() ); ?></span>
							</label>
							<p class="optk-card-desc">
								<?php echo esc_html( $module->description() ); ?>
							</p>

							<?php if ( $module->fields() ) : ?>
								<div class="optk-fields">
									<?php foreach ( $module->fields() as $field ) {
										$value = $saved['modules'][ $id ][ $field['key'] ] ?? ( $field['default'] ?? '' );
										$this->render_field( $field, $base . '[' . $field['key'] . ']', $value );
									} ?>
								</div>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
				<?php submit_button(); ?>
			</form>

			<?php
			/** Extra sections below the module cards, e.g. settings export/import. */
			do_action( 'optk_settings_after' );
			?>

			<div class="optk-card optk-about">
				<div class="optk-about-head">
					<a class="optk-about-logo" href="<?php echo esc_url( optk_link( '' ) ); ?>" target="_blank" rel="noopener">
						<img src="<?php echo esc_url( OPTK_URL . 'assets/logo.png' ); ?>" width="96" height="56" alt="<?php esc_attr_e( 'Optimise Creative website (opens in a new tab)', 'optimise-creative-toolkit' ); ?>">
					</a>
					<div>
						<h2>
							<?php
							/* translators: %s: heart emoji. */
							printf( esc_html__( 'Made with %s by Optimise Creative', 'optimise-creative-toolkit' ), '<svg class="optk-heart" role="img" aria-label="' . esc_attr__( 'love', 'optimise-creative-toolkit' ) . '" viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M12 21s-7.5-4.6-10-9.3C.4 8.6 2 4.5 5.9 4.1 8.4 3.8 10.3 5.3 12 7.4c1.7-2.1 3.6-3.6 6.1-3.3C22 4.5 23.6 8.6 22 11.7 19.5 16.4 12 21 12 21z"/></svg>' );
							?>
						</h2>
						<a class="optk-about-domain" href="<?php echo esc_url( optk_link( '' ) ); ?>" target="_blank" rel="noopener">optimisecreative.com</a>
					</div>
				</div>
				<p><?php esc_html_e( 'Optimise Creative Toolkit is free and always will be. We are a UK studio that builds fast, search-friendly WordPress sites, and this is the toolkit we install on every one of them.', 'optimise-creative-toolkit' ); ?></p>
				<p><?php esc_html_e( 'If your site needs more than a toolkit, such as speed work, a security review, accessibility fixes or a custom plugin, we can help.', 'optimise-creative-toolkit' ); ?></p>
				<p>
					<a class="button" href="<?php echo esc_url( optk_link( 'contact/' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Talk to us about your site', 'optimise-creative-toolkit' ); ?></a>
					<a class="button" href="<?php echo esc_url( optk_link( 'services/' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'See our services', 'optimise-creative-toolkit' ); ?></a>
				</p>
			</div>

		</div>
		<?php
	}

	private function render_field( array $field, string $name, $value ): void {
		$id   = sanitize_key( str_replace( [ '[', ']' ], '-', $name ) );
		$type = $field['type'];
		echo '<div class="optk-field optk-field-' . esc_attr( $type ) . '">';

		if ( 'checkbox' === $type ) {
			printf(
				'<label><input type="checkbox" name="%s" value="1" %s> %s</label>',
				esc_attr( $name ),
				checked( ! empty( $value ), true, false ),
				esc_html( $field['label'] )
			);
		} else {
			printf( '<label for="%s">%s</label>', esc_attr( $id ), esc_html( $field['label'] ) );
			if ( 'textarea' === $type ) {
				printf( '<textarea id="%s" name="%s" rows="3" class="large-text">%s</textarea>', esc_attr( $id ), esc_attr( $name ), esc_textarea( (string) $value ) );
			} elseif ( 'select' === $type ) {
				printf( '<select id="%s" name="%s">', esc_attr( $id ), esc_attr( $name ) );
				foreach ( $field['options'] ?? [] as $opt_value => $opt_label ) {
					printf( '<option value="%s" %s>%s</option>', esc_attr( $opt_value ), selected( (string) $value, (string) $opt_value, false ), esc_html( $opt_label ) );
				}
				echo '</select>';
			} else {
				$input_type = in_array( $type, [ 'url', 'number', 'color' ], true ) ? $type : 'text';
				printf( '<input type="%s" id="%s" name="%s" value="%s" class="regular-text">', esc_attr( $input_type ), esc_attr( $id ), esc_attr( $name ), esc_attr( (string) $value ) );
			}
		}

		if ( ! empty( $field['help'] ) ) {
			echo '<p class="description">' . esc_html( $field['help'] ) . '</p>';
		}
		echo '</div>';
	}
}
