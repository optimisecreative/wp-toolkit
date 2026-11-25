<?php
/**
 * Optimise Upload Guard: stops oversized images slowing the site.
 *
 * @package OptimiseCreativeToolkit
 */

defined( 'ABSPATH' ) || exit;

final class OPTK_Upload_Guard extends OPTK_Module {

	/** {@inheritdoc} */
	public function id(): string {
		return 'upload-guard';
	}

	/** {@inheritdoc} */
	public function label(): string {
		return __( 'Optimise Upload Guard', 'optimise-creative-toolkit' );
	}

	/** {@inheritdoc} */
	public function description(): string {
		return __( 'Stops oversized images slowing the site: shrinks big photos on upload and can block huge files.', 'optimise-creative-toolkit' );
	}

	/** {@inheritdoc} */
	public function fields(): array {
		return [
			[
				'key'     => 'max_px',
				'label'   => __( 'Maximum image dimension (px)', 'optimise-creative-toolkit' ),
				'type'    => 'number',
				'default' => 2000,
				'help'    => __( 'Larger images are scaled down on upload. The original is kept by WordPress.', 'optimise-creative-toolkit' ),
			],
			[
				'key'     => 'block',
				'label'   => __( 'Block image uploads over the size limit', 'optimise-creative-toolkit' ),
				'type'    => 'checkbox',
				'default' => 1,
			],
			[
				'key'     => 'max_mb',
				'label'   => __( 'Maximum image file size (MB)', 'optimise-creative-toolkit' ),
				'type'    => 'number',
				'default' => 5,
			],
		];
	}

	/** {@inheritdoc} */
	public function boot(): void {
		add_filter( 'big_image_size_threshold', [ $this, 'set_big_image_threshold' ] );

		if ( $this->opt( 'block' ) ) {
			add_filter( 'wp_handle_upload_prefilter', [ $this, 'check_size' ] );
		}
	}

	/**
	 * Sets the dimension above which WordPress scales uploaded images down.
	 *
	 * @param int|mixed $threshold Threshold proposed by WordPress.
	 * @return int|mixed
	 */
	public function set_big_image_threshold( $threshold ) {
		$max_px = (int) $this->opt( 'max_px' );

		return $max_px > 0 ? $max_px : $threshold;
	}

	/**
	 * Rejects image uploads larger than the configured file size.
	 *
	 * @param array $file Upload data: name, type, tmp_name, error, size.
	 * @return array
	 */
	public function check_size( $file ) {
		if ( ! isset( $file['type'], $file['size'] ) ) {
			return $file;
		}

		if ( ! str_starts_with( $file['type'], 'image/' ) || 'image/svg+xml' === $file['type'] ) {
			return $file;
		}

		$limit = max( 1, (int) $this->opt( 'max_mb' ) ) * MB_IN_BYTES;

		if ( $file['size'] > $limit ) {
			$file['error'] = sprintf(
				/* translators: 1: uploaded file size, 2: size limit. */
				__( 'This image is %1$s, over the %2$s limit. Please resize it before uploading — try squoosh.app.', 'optimise-creative-toolkit' ),
				size_format( $file['size'] ),
				size_format( $limit )
			);
		}

		return $file;
	}
}
