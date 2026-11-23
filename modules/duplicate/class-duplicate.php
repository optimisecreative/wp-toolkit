<?php
/**
 * Optimise Duplicate: adds a Duplicate link to posts and pages that copies them into a new draft.
 *
 * @package OptimiseCreativeToolkit
 */

defined( 'ABSPATH' ) || exit;

final class OPTK_Duplicate extends OPTK_Module {

	/** {@inheritdoc} */
	public function id(): string {
		return 'duplicate';
	}

	/** {@inheritdoc} */
	public function label(): string {
		return 'Optimise Duplicate';
	}

	/** {@inheritdoc} */
	public function description(): string {
		return 'Adds a Duplicate link to posts and pages that copies them into a new draft.';
	}

	/** {@inheritdoc} */
	public function boot(): void {
		add_filter( 'post_row_actions', [ $this, 'add_row_action' ], 10, 2 );
		add_filter( 'page_row_actions', [ $this, 'add_row_action' ], 10, 2 );
		add_action( 'admin_action_ot_duplicate', [ $this, 'handle' ] );
	}

	/**
	 * Adds the Duplicate link to a row's action links.
	 *
	 * @param array|mixed $actions Row action links.
	 * @param mixed       $post    Post object for the row.
	 * @return array|mixed
	 */
	public function add_row_action( $actions, $post ) {
		if ( ! is_array( $actions ) || ! $post instanceof WP_Post || ! post_type_exists( $post->post_type ) ) {
			return $actions;
		}

		$type = get_post_type_object( $post->post_type );
		if ( ! $type || empty( $type->show_ui ) || ! current_user_can( 'edit_post', $post->ID ) ) {
			return $actions;
		}

		$url = wp_nonce_url(
			admin_url( 'admin.php?action=optk_duplicate&post=' . $post->ID ),
			'optk_duplicate_' . $post->ID
		);

		$actions['optk_duplicate'] = sprintf(
			'<a href="%1$s" aria-label="%2$s">%3$s</a>',
			esc_url( $url ),
			/* translators: %s: post title. */
			esc_attr( sprintf( __( 'Duplicate “%s”', 'optimise-creative-toolkit' ), $post->post_title ) ),
			esc_html__( 'Duplicate', 'optimise-creative-toolkit' )
		);

		return $actions;
	}

	/**
	 * Copies the requested post into a new draft and opens it in the editor.
	 */
	public function handle(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- verified by check_admin_referer() below.
		$id = absint( wp_unslash( $_GET['post'] ?? 0 ) );
		check_admin_referer( 'optk_duplicate_' . $id );

		$post = get_post( $id );
		if ( ! $post || ! current_user_can( 'edit_post', $id ) ) {
			wp_die(
				esc_html__( 'You are not allowed to duplicate this item.', 'optimise-creative-toolkit' ),
				esc_html__( 'Forbidden', 'optimise-creative-toolkit' ),
				[ 'response' => 403 ]
			);
		}

		$type = get_post_type_object( $post->post_type );
		if ( ! $type || ! current_user_can( $type->cap->create_posts ) ) {
			wp_die(
				esc_html__( 'You are not allowed to create this type of item.', 'optimise-creative-toolkit' ),
				esc_html__( 'Forbidden', 'optimise-creative-toolkit' ),
				[ 'response' => 403 ]
			);
		}

		$args = [
			'post_title'     => $post->post_title . ' (copy)',
			'post_content'   => $post->post_content,
			'post_excerpt'   => $post->post_excerpt,
			'post_type'      => $post->post_type,
			'post_parent'    => $post->post_parent,
			'menu_order'     => $post->menu_order,
			'comment_status' => $post->comment_status,
			'ping_status'    => $post->ping_status,
			'post_password'  => $post->post_password,
			'post_author'    => get_current_user_id(),
			'post_status'    => 'draft',
		];

		// wp_insert_post() unslashes its input, so the data must be slashed first.
		$new_id = wp_insert_post( wp_slash( $args ), true );
		if ( is_wp_error( $new_id ) ) {
			wp_die( esc_html( $new_id->get_error_message() ) );
		}

		foreach ( get_object_taxonomies( $post->post_type ) as $tax ) {
			$terms = wp_get_object_terms( $id, $tax, [ 'fields' => 'ids' ] );
			if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
				wp_set_object_terms( $new_id, $terms, $tax );
			}
		}

		$skip_meta = [ '_edit_lock', '_edit_last', '_wp_old_slug', '_wp_old_date' ];
		foreach ( get_post_meta( $id ) as $key => $values ) {
			if ( in_array( $key, $skip_meta, true ) ) {
				continue;
			}
			foreach ( $values as $value ) {
				add_post_meta( $new_id, $key, wp_slash( maybe_unserialize( $value ) ) );
			}
		}

		wp_safe_redirect( get_edit_post_link( $new_id, 'raw' ) );
		exit;
	}
}
