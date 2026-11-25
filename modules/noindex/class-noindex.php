<?php
/**
 * Optimise Noindex: keeps thin archive pages out of search results.
 *
 * @package OptimiseCreativeToolkit
 */

defined( 'ABSPATH' ) || exit;

final class OPTK_Noindex extends OPTK_Module {

	/** {@inheritdoc} */
	public function id(): string {
		return 'noindex';
	}

	/** {@inheritdoc} */
	public function label(): string {
		return __( 'Optimise Noindex', 'optimise-creative-toolkit' );
	}

	/** {@inheritdoc} */
	public function description(): string {
		return __( 'Keeps thin archive pages out of search results so Google focuses on the pages that matter.', 'optimise-creative-toolkit' );
	}

	/** {@inheritdoc} */
	public function fields(): array {
		return [
			[
				'key'     => 'tags',
				'label'   => __( 'Tag archives', 'optimise-creative-toolkit' ),
				'type'    => 'checkbox',
				'default' => 1,
			],
			[
				'key'     => 'categories',
				'label'   => __( 'Category archives', 'optimise-creative-toolkit' ),
				'type'    => 'checkbox',
				'default' => 0,
			],
			[
				'key'     => 'authors',
				'label'   => __( 'Author archives', 'optimise-creative-toolkit' ),
				'type'    => 'checkbox',
				'default' => 1,
			],
			[
				'key'     => 'dates',
				'label'   => __( 'Date archives', 'optimise-creative-toolkit' ),
				'type'    => 'checkbox',
				'default' => 1,
			],
			[
				'key'     => 'attachments',
				'label'   => __( 'Attachment pages', 'optimise-creative-toolkit' ),
				'type'    => 'checkbox',
				'default' => 1,
			],
			[
				'key'     => 'paged',
				'label'   => __( 'Page 2 onwards of any archive', 'optimise-creative-toolkit' ),
				'type'    => 'checkbox',
				'default' => 0,
			],
			[
				'key'     => 'sitemap',
				'label'   => __( 'Also remove these from the WordPress sitemap', 'optimise-creative-toolkit' ),
				'type'    => 'checkbox',
				'default' => 1,
				'help'    => __( 'Only affects the built-in sitemap at /wp-sitemap.xml. If an SEO plugin such as Yoast or Rank Math is active, use its settings instead.', 'optimise-creative-toolkit' ),
			],
		];
	}

	/** {@inheritdoc} */
	public function boot(): void {
		add_filter( 'wp_robots', [ $this, 'filter_robots' ] );

		if ( $this->opt( 'sitemap' ) ) {
			add_filter( 'wp_sitemaps_taxonomies', [ $this, 'drop_sitemap_taxonomies' ] );
			add_filter( 'wp_sitemaps_add_provider', [ $this, 'drop_users_sitemap_provider' ], 10, 2 );
		}
	}

	/**
	 * Adds noindex and follow to the robots meta tag on matching archive pages.
	 *
	 * Uses the wp_robots core filter (WordPress 5.7+).
	 *
	 * @param array|mixed $robots Robots directives.
	 * @return array|mixed
	 */
	public function filter_robots( $robots ) {
		if ( is_array( $robots ) && $this->should_noindex() ) {
			$robots['noindex'] = true;
			$robots['follow']  = true;
			unset( $robots['index'], $robots['max-image-preview'] );
		}

		return $robots;
	}

	/**
	 * Whether the current request is a thin page that should be kept out of search.
	 *
	 * Search results are already noindexed by WordPress core, so there is no search option here.
	 *
	 * @return bool
	 */
	private function should_noindex(): bool {
		return ( $this->opt( 'tags' ) && is_tag() )
			|| ( $this->opt( 'categories' ) && is_category() )
			|| ( $this->opt( 'authors' ) && is_author() )
			|| ( $this->opt( 'dates' ) && is_date() )
			|| ( $this->opt( 'attachments' ) && is_attachment() )
			|| ( $this->opt( 'paged' ) && is_paged() && ( is_archive() || is_home() ) );
	}

	/**
	 * Removes tag and category taxonomies from the built-in sitemap.
	 *
	 * @param array|mixed $taxonomies Taxonomy objects keyed by name.
	 * @return array|mixed
	 */
	public function drop_sitemap_taxonomies( $taxonomies ) {
		if ( ! is_array( $taxonomies ) ) {
			return $taxonomies;
		}

		if ( $this->opt( 'tags' ) ) {
			unset( $taxonomies['post_tag'] );
		}

		if ( $this->opt( 'categories' ) ) {
			unset( $taxonomies['category'] );
		}

		return $taxonomies;
	}

	/**
	 * Removes the users provider from the built-in sitemap when author archives are off.
	 *
	 * @param mixed  $provider Sitemap provider instance.
	 * @param string $name     Provider name, e.g. 'users'.
	 * @return mixed|false
	 */
	public function drop_users_sitemap_provider( $provider, $name ) {
		if ( 'users' === $name && $this->opt( 'authors' ) ) {
			return false;
		}

		return $provider;
	}
}
