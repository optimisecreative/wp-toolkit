<?php
/**
 * Optimise Lazy Hero: stops the first images being lazy-loaded and preloads the featured image.
 *
 * @package OptimiseCreativeToolkit
 */

defined( 'ABSPATH' ) || exit;

final class OPTK_Lazy_Hero extends OPTK_Module {

	/** {@inheritdoc} */
	public function id(): string {
		return 'lazy-hero';
	}

	/** {@inheritdoc} */
	public function label(): string {
		return 'Optimise Lazy Hero';
	}

	/** {@inheritdoc} */
	public function description(): string {
		return 'Stops the first images being lazy-loaded and preloads the featured image, for a faster Largest Contentful Paint.';
	}

	/** {@inheritdoc} */
	public function fields(): array {
		return [
			[
				'key'     => 'skip',
				'label'   => 'Images to load eagerly',
				'type'    => 'number',
				'default' => 3,
				'help'    => 'How many images at the top of each page skip lazy-loading. WordPress uses 3 by default; raise it if your hero sits below a logo or slider.',
			],
			[
				'key'     => 'preload',
				'label'   => 'Preload the featured image on posts and pages',
				'type'    => 'checkbox',
				'default' => 1,
			],
			[
				'key'     => 'preload_size',
				'label'   => 'Featured image size to preload',
				'type'    => 'select',
				'default' => 'large',
				'options' => [
					'medium_large' => 'Medium large',
					'large'        => 'Large',
					'full'         => 'Full',
				],
			],
		];
	}

	/** {@inheritdoc} */
	public function boot(): void {
		add_filter( 'wp_omit_loading_attr_threshold', [ $this, 'eager_threshold' ] );

		if ( $this->opt( 'preload' ) ) {
			add_action( 'wp_head', [ $this, 'preload_featured' ], 2 );
		}
	}

	/**
	 * Sets how many leading images skip lazy-loading (WordPress 5.9+).
	 *
	 * @return int
	 */
	public function eager_threshold(): int {
		return max( 0, (int) $this->opt( 'skip' ) );
	}

	/**
	 * Outputs a preload link for the featured image on singular views.
	 */
	public function preload_featured(): void {
		$post_id = get_queried_object_id();

		if ( ! is_singular() || ! has_post_thumbnail( $post_id ) ) {
			return;
		}

		$id   = get_post_thumbnail_id( $post_id );
		$size = $this->opt( 'preload_size' );
		$src  = wp_get_attachment_image_url( $id, $size );

		if ( empty( $src ) ) {
			return;
		}

		$srcset = wp_get_attachment_image_srcset( $id, $size );
		$sizes  = wp_get_attachment_image_sizes( $id, $size );

		printf(
			'<link rel="preload" as="image" href="%s"%s%s fetchpriority="high">' . "\n",
			esc_url( $src ),
			$srcset ? ' imagesrcset="' . esc_attr( $srcset ) . '"' : '',
			$sizes ? ' imagesizes="' . esc_attr( $sizes ) . '"' : ''
		);
	}
}
