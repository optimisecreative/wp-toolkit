<?php
/**
 * Optimise Alt: finds images with no alt text and lets you fix them in one place.
 *
 * @package OptimiseCreativeToolkit
 */

defined( 'ABSPATH' ) || exit;

final class OPTK_Alt extends OPTK_Module {

	/** {@inheritdoc} */
	public function id(): string {
		return 'alt';
	}

	/** {@inheritdoc} */
	public function label(): string {
		return 'Optimise Alt';
	}

	/** {@inheritdoc} */
	public function description(): string {
		return 'Finds images with no alt text and lets you fix them in one place, for accessibility and SEO.';
	}

	/** {@inheritdoc} */
	public function boot(): void {
		add_action( 'admin_menu', [ $this, 'add_page' ] );
		add_action( 'wp_ajax_ot_alt_save', [ $this, 'save' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
	}

	/** Adds the Alt Text Audit page under Media. */
	public function add_page(): void {
		add_media_page(
			__( 'Alt Text Audit', 'optimise-creative-toolkit' ),
			__( 'Alt Text Audit', 'optimise-creative-toolkit' ),
			'upload_files',
			'optk-alt',
			[ $this, 'render' ]
		);
	}

	/**
	 * Loads the audit script and styles on the audit page only.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue( $hook ): void {
		if ( 'media_page_ot-alt' !== $hook ) {
			return;
		}

		wp_enqueue_style( 'optk-admin', OPTK_URL . 'assets/admin.css', [], OPTK_VERSION );
		wp_enqueue_script( 'optk-alt', $this->url( 'alt.js' ), [], OPTK_VERSION, true );
		wp_localize_script(
			'optk-alt',
			'optkAlt',
			[
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'optk_alt_save' ),
				'saved'   => __( 'Saved', 'optimise-creative-toolkit' ),
				'error'   => __( 'Could not save', 'optimise-creative-toolkit' ),
			]
		);
	}

	/**
	 * Runs an image attachment query and returns the WP_Query object.
	 *
	 * @param array $args Extra query arguments.
	 * @return WP_Query
	 */
	private function query( array $args = [] ): WP_Query {
		return new WP_Query(
			array_merge(
				[
					'post_type'      => 'attachment',
					'post_status'    => 'inherit',
					'post_mime_type' => 'image',
					'fields'         => 'ids',
					'no_found_rows'  => false,
				],
				$args
			)
		);
	}

	/** Renders the audit page. */
	public function render(): void {
		if ( ! current_user_can( 'upload_files' ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'optimise-creative-toolkit' ) );
		}

		$missing_meta = [
			'relation' => 'OR',
			[
				'key'     => '_wp_attachment_image_alt',
				'compare' => 'NOT EXISTS',
			],
			[
				'key'     => '_wp_attachment_image_alt',
				'value'   => '',
				'compare' => '=',
			],
		];

		$total   = (int) $this->query( [ 'posts_per_page' => 1 ] )->found_posts;
		$missing = (int) $this->query(
			[
				'posts_per_page' => 1,
				'meta_query'     => $missing_meta, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			]
		)->found_posts;
		$with    = max( 0, $total - $missing );
		$percent = $total > 0 ? (int) round( ( $with / $total ) * 100 ) : 100;

		echo '<div class="wrap optk-wrap">';
		echo '<h1>' . esc_html__( 'Alt Text Audit', 'optimise-creative-toolkit' ) . '</h1>';

		printf(
			'<p class="optk-intro">%s</p>',
			esc_html(
				sprintf(
					/* translators: 1: images with alt text, 2: total images, 3: percentage. */
					__( '%1$d of %2$d images have alt text (%3$d%%)', 'optimise-creative-toolkit' ),
					$with,
					$total,
					$percent
				)
			)
		);
		printf(
			'<div style="background:#dcdcde;border-radius:4px;height:10px;max-width:400px;overflow:hidden"><div style="background:#6C4860;height:100%%;width:%d%%"></div></div>',
			(int) $percent
		);

		if ( $missing > 0 && $percent < 80 ) {
			printf(
				'<p class="optk-cta">%s <a href="%s" target="_blank" rel="noopener">%s</a></p>',
				esc_html__( 'Missing alt text is often a sign of wider accessibility issues.', 'optimise-creative-toolkit' ),
				esc_url( optk_link( 'website-accessibility-fixes/', 'alt-audit' ) ),
				esc_html__( 'Get an accessibility review from Optimise Creative', 'optimise-creative-toolkit' )
			);
		}

		if ( 0 === $missing ) {
			echo '<div class="notice notice-success inline"><p>' . esc_html__( 'Every image has alt text. Nice work.', 'optimise-creative-toolkit' ) . '</p></div>';
			echo '</div>';
			return;
		}

		$per_page = 30;
		$paged    = isset( $_GET['paged'] ) ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$list     = $this->query(
			[
				'posts_per_page' => $per_page,
				'paged'          => $paged,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'meta_query'     => $missing_meta, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			]
		);

		echo '<table class="widefat striped" style="margin-top:20px"><thead><tr>';
		echo '<th>' . esc_html__( 'Image', 'optimise-creative-toolkit' ) . '</th>';
		echo '<th>' . esc_html__( 'File', 'optimise-creative-toolkit' ) . '</th>';
		echo '<th>' . esc_html__( 'Used on', 'optimise-creative-toolkit' ) . '</th>';
		echo '<th>' . esc_html__( 'Alt text', 'optimise-creative-toolkit' ) . '</th>';
		echo '</tr></thead><tbody>';

		foreach ( $list->posts as $id ) {
			$id       = (int) $id;
			$file     = get_attached_file( $id );
			$name     = $file ? basename( $file ) : get_the_title( $id );
			$edit     = get_edit_post_link( $id );
			$parent   = (int) wp_get_post_parent_id( $id );
			$title    = get_the_title( $id );
			$used_on  = '&mdash;';
			$thumb    = wp_get_attachment_image( $id, [ 60, 60 ] );

			if ( $parent ) {
				$used_on = sprintf(
					'<a href="%1$s">%2$s</a>',
					esc_url( (string) get_edit_post_link( $parent ) ),
					esc_html( get_the_title( $parent ) )
				);
			}

			echo '<tr>';
			echo '<td>' . wp_kses_post( $thumb ) . '</td>';
			echo '<td>';
			if ( $edit ) {
				echo '<a href="' . esc_url( $edit ) . '">' . esc_html( $name ) . '</a>';
			} else {
				echo esc_html( $name );
			}
			echo '</td>';
			echo '<td>' . wp_kses_post( $used_on ) . '</td>';
			echo '<td>';
			printf(
				'<input type="text" class="optk-alt-input regular-text" data-id="%1$s" placeholder="%2$s"> <button type="button" class="button optk-alt-save" data-id="%1$s">%3$s</button> <span class="optk-alt-status"></span>',
				esc_attr( (string) $id ),
				esc_attr( $title ),
				esc_html__( 'Save', 'optimise-creative-toolkit' )
			);
			echo '</td>';
			echo '</tr>';
		}

		echo '</tbody></table>';

		$links = paginate_links(
			[
				'base'    => add_query_arg( 'paged', '%#%' ),
				'format'  => '',
				'current' => $paged,
				'total'   => max( 1, (int) $list->max_num_pages ),
			]
		);
		if ( $links ) {
			echo '<div class="tablenav"><div class="tablenav-pages">' . wp_kses_post( $links ) . '</div></div>';
		}

		echo '</div>';
	}

	/** Saves alt text for one attachment (AJAX). */
	public function save(): void {
		check_ajax_referer( 'optk_alt_save', 'nonce' );

		$id = isset( $_POST['id'] ) ? absint( wp_unslash( $_POST['id'] ) ) : 0;
		if ( ! $id || 'attachment' !== get_post_type( $id ) || ! current_user_can( 'edit_post', $id ) ) {
			wp_send_json_error();
		}

		$alt = sanitize_text_field( wp_unslash( $_POST['alt'] ?? '' ) );
		if ( '' === $alt ) {
			wp_send_json_error();
		}

		update_post_meta( $id, '_wp_attachment_image_alt', $alt );
		wp_send_json_success();
	}
}
