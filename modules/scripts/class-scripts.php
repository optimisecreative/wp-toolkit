<?php
/**
 * Optimise Scripts: adds Google Analytics 4, Google Tag Manager, Meta Pixel and
 * search engine verification tags from their IDs, without pasting raw code.
 *
 * Every ID is validated against its expected format and escaped on output. The
 * loaders are added through the core script APIs, so no saved value is echoed raw.
 *
 * @package OptimiseCreativeToolkit
 */

defined( 'ABSPATH' ) || exit;

final class OPTK_Scripts extends OPTK_Module {

	/** Expected format for each ID field. Values that do not match are ignored. */
	private const PATTERNS = [
		'ga4_id'              => '/^G-[A-Z0-9]{4,20}$/',
		'gtm_id'              => '/^GTM-[A-Z0-9]{4,12}$/',
		'meta_pixel_id'       => '/^[0-9]{6,20}$/',
		'google_verification' => '/^[A-Za-z0-9_-]{10,100}$/',
		'bing_verification'   => '/^[A-Za-z0-9]{10,64}$/',
	];

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
		return 'Adds Google Analytics, Tag Manager, Meta Pixel and search engine verification from their IDs, without editing the theme.';
	}

	/** {@inheritdoc} */
	public function fields(): array {
		return [
			[
				'key'     => 'ga4_id',
				'label'   => 'Google Analytics 4 measurement ID',
				'type'    => 'text',
				'default' => '',
				'help'    => 'Looks like G-XXXXXXXXXX. Leave blank if you load Analytics through Tag Manager.',
			],
			[
				'key'     => 'gtm_id',
				'label'   => 'Google Tag Manager container ID',
				'type'    => 'text',
				'default' => '',
				'help'    => 'Looks like GTM-XXXXXXX. The noscript fallback needs a theme that supports wp_body_open.',
			],
			[
				'key'     => 'meta_pixel_id',
				'label'   => 'Meta Pixel ID',
				'type'    => 'text',
				'default' => '',
				'help'    => 'The number shown in Meta Events Manager.',
			],
			[
				'key'     => 'google_verification',
				'label'   => 'Google Search Console verification code',
				'type'    => 'text',
				'default' => '',
				'help'    => 'Only the content value from the google-site-verification meta tag.',
			],
			[
				'key'     => 'bing_verification',
				'label'   => 'Bing Webmaster Tools verification code',
				'type'    => 'text',
				'default' => '',
				'help'    => 'Only the content value from the msvalidate.01 meta tag.',
			],
			[
				'key'     => 'skip_logged_in',
				'label'   => 'Do not load tracking for logged-in admins and editors',
				'type'    => 'checkbox',
				'default' => 1,
				'help'    => 'Keeps your own visits out of analytics. Verification tags are always added.',
			],
		];
	}

	/** {@inheritdoc} */
	public function boot(): void {
		add_action( 'wp_head', [ $this, 'print_verification' ], 1 );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_tracking' ] );
		add_action( 'wp_body_open', [ $this, 'print_gtm_noscript' ], 1 );
		add_action( 'wp_footer', [ $this, 'print_pixel_noscript' ], 100 );
	}

	/**
	 * Returns a saved ID only if it matches the expected format.
	 *
	 * @param string $key Field key.
	 * @return string Valid ID, or '' if empty or invalid.
	 */
	private function valid_id( string $key ): string {
		$value = trim( (string) $this->opt( $key, '' ) );

		if ( 'ga4_id' === $key || 'gtm_id' === $key ) {
			$value = strtoupper( $value );
		}

		return ( '' !== $value && preg_match( self::PATTERNS[ $key ], $value ) ) ? $value : '';
	}

	/** Whether tracking should be skipped for the current visitor. */
	private function skip_tracking(): bool {
		return is_admin() || ( $this->opt( 'skip_logged_in' ) && current_user_can( 'edit_posts' ) );
	}

	/** Prints the search engine verification meta tags. */
	public function print_verification(): void {
		$google = $this->valid_id( 'google_verification' );
		if ( '' !== $google ) {
			printf( '<meta name="google-site-verification" content="%s" />' . "\n", esc_attr( $google ) );
		}

		$bing = $this->valid_id( 'bing_verification' );
		if ( '' !== $bing ) {
			printf( '<meta name="msvalidate.01" content="%s" />' . "\n", esc_attr( $bing ) );
		}
	}

	/** Enqueues the GA4, Tag Manager and Meta Pixel loaders through the core script API. */
	public function enqueue_tracking(): void {
		if ( $this->skip_tracking() ) {
			return;
		}

		$ga4 = $this->valid_id( 'ga4_id' );
		if ( '' !== $ga4 ) {
			wp_enqueue_script(
				'optk-ga4',
				add_query_arg( 'id', rawurlencode( $ga4 ), 'https://www.googletagmanager.com/gtag/js' ),
				[],
				null, // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- Google serves this file; a version query would not change it.
				[ 'strategy' => 'async', 'in_footer' => false ]
			);
			wp_add_inline_script(
				'optk-ga4',
				'window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag("js",new Date());gtag("config",' . wp_json_encode( $ga4 ) . ');',
				'before' // Queues the config before the loader, so the loader keeps its async attribute.
			);
		}

		$gtm = $this->valid_id( 'gtm_id' );
		if ( '' !== $gtm ) {
			wp_register_script( 'optk-gtm', false, [], OPTK_VERSION, [ 'in_footer' => false ] );
			wp_enqueue_script( 'optk-gtm' );
			wp_add_inline_script(
				'optk-gtm',
				'(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({"gtm.start":new Date().getTime(),event:"gtm.js"});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!="dataLayer"?"&l="+l:"";j.async=true;j.src="https://www.googletagmanager.com/gtm.js?id="+i+dl;f.parentNode.insertBefore(j,f);})(window,document,"script","dataLayer",' . wp_json_encode( $gtm ) . ');'
			);
		}

		$pixel = $this->valid_id( 'meta_pixel_id' );
		if ( '' !== $pixel ) {
			wp_register_script( 'optk-meta-pixel', false, [], OPTK_VERSION, [ 'in_footer' => false ] );
			wp_enqueue_script( 'optk-meta-pixel' );
			wp_add_inline_script(
				'optk-meta-pixel',
				'!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version="2.0";n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,"script","https://connect.facebook.net/en_US/fbevents.js");fbq("init",' . wp_json_encode( $pixel ) . ');fbq("track","PageView");'
			);
		}
	}

	/** Prints the Tag Manager noscript fallback right after the opening body tag. */
	public function print_gtm_noscript(): void {
		$gtm = $this->valid_id( 'gtm_id' );
		if ( '' === $gtm || $this->skip_tracking() ) {
			return;
		}

		printf(
			'<noscript><iframe src="%s" height="0" width="0" style="display:none;visibility:hidden" title="%s"></iframe></noscript>' . "\n",
			esc_url( add_query_arg( 'id', rawurlencode( $gtm ), 'https://www.googletagmanager.com/ns.html' ) ),
			esc_attr__( 'Google Tag Manager', 'optimise-creative-toolkit' )
		);
	}

	/** Prints the Meta Pixel noscript fallback in the footer. */
	public function print_pixel_noscript(): void {
		$pixel = $this->valid_id( 'meta_pixel_id' );
		if ( '' === $pixel || $this->skip_tracking() ) {
			return;
		}

		printf(
			'<noscript><img height="1" width="1" style="display:none" alt="" src="%s" /></noscript>' . "\n",
			esc_url(
				add_query_arg(
					[
						'id'       => rawurlencode( $pixel ),
						'ev'       => 'PageView',
						'noscript' => '1',
					],
					'https://www.facebook.com/tr'
				)
			)
		);
	}
}
