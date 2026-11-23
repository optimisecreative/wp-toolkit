<?php
/**
 * Settings export / import (JSON file), shown below the settings form.
 */

defined( 'ABSPATH' ) || exit;

final class OPTK_Transfer {

	private const PAGE     = 'optimise-creative-toolkit';
	private const MAX_SIZE = 524288; // 512 KB.

	public function init(): void {
		add_action( 'optk_settings_after', [ $this, 'render' ] );
		add_action( 'admin_post_ot_export', [ $this, 'export' ] );
		add_action( 'admin_post_ot_import', [ $this, 'import' ] );
		add_action( 'admin_notices', [ $this, 'notice' ] );
	}

	public function render(): void {
		?>
		<div class="optk-card optk-transfer">
			<h2><?php esc_html_e( 'Export / import settings', 'optimise-creative-toolkit' ); ?></h2>
			<p><?php esc_html_e( "Copy this site's toolkit setup to another site.", 'optimise-creative-toolkit' ); ?></p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="optk_export">
				<?php
				wp_nonce_field( 'optk_export' );
				submit_button( __( 'Download settings', 'optimise-creative-toolkit' ), 'secondary', 'submit', false );
				?>
			</form>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
				<input type="hidden" name="action" value="optk_import">
				<?php wp_nonce_field( 'optk_import' ); ?>
				<input type="file" name="optk_file" accept=".json,application/json" required>
				<?php submit_button( __( 'Import settings', 'optimise-creative-toolkit' ), 'secondary', 'submit', false ); ?>
				<p class="description"><small><?php esc_html_e( 'Importing replaces all current toolkit settings.', 'optimise-creative-toolkit' ); ?></small></p>
			</form>
		</div>
		<?php
	}

	public function export(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'optimise-creative-toolkit' ), '', [ 'response' => 403 ] );
		}
		check_admin_referer( 'optk_export' );

		$payload = [
			'plugin'   => 'optimise-creative-toolkit',
			'version'  => OPTK_VERSION,
			'exported' => gmdate( 'c' ),
			'settings' => get_option( OPTK_OPTION, [] ),
		];

		$host     = sanitize_title( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
		$filename = 'optimise-creative-toolkit-' . $host . '-' . gmdate( 'Y-m-d' ) . '.json';

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		echo wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON download.
		exit;
	}

	public function import(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'optimise-creative-toolkit' ), '', [ 'response' => 403 ] );
		}
		check_admin_referer( 'optk_import' );

		$file = $_FILES['optk_file'] ?? null; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce checked above; file validated below.

		if (
			! is_array( $file )
			|| ! isset( $file['error'], $file['tmp_name'], $file['size'] )
			|| UPLOAD_ERR_OK !== (int) $file['error']
			|| ! is_uploaded_file( (string) $file['tmp_name'] )
			|| (int) $file['size'] > self::MAX_SIZE
		) {
			$this->redirect( 'invalid' );
		}

		$raw  = file_get_contents( (string) $file['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local uploaded file, not a remote URL.
		$data = false === $raw ? null : json_decode( $raw, true );

		if (
			! is_array( $data )
			|| ( $data['plugin'] ?? '' ) !== 'optimise-creative-toolkit'
			|| ! isset( $data['settings'] )
			|| ! is_array( $data['settings'] )
		) {
			$this->redirect( 'invalid' );
		}

		// The registered sanitize_callback whitelists modules and fields, so untrusted input is cleaned here.
		update_option( OPTK_OPTION, $data['settings'] );

		$this->redirect( 'imported' );
	}

	public function notice(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'settings_page_' . self::PAGE !== $screen->id || ! isset( $_GET['optk_notice'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$code = sanitize_key( wp_unslash( $_GET['optk_notice'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( 'imported' === $code ) {
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				esc_html__( 'Settings imported.', 'optimise-creative-toolkit' )
			);
		} elseif ( 'invalid' === $code ) {
			printf(
				'<div class="notice notice-error is-dismissible"><p>%s</p></div>',
				esc_html__( 'That file is not a valid Optimise Creative Toolkit export.', 'optimise-creative-toolkit' )
			);
		}
	}

	private function redirect( string $code ): void {
		wp_safe_redirect( add_query_arg( 'optk_notice', $code, admin_url( 'options-general.php?page=' . self::PAGE ) ) );
		exit;
	}
}
