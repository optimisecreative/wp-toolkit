<?php
/**
 * Optimise Comments Off: switches comments and trackbacks off across the site.
 *
 * @package OptimiseCreativeToolkit
 */

defined( 'ABSPATH' ) || exit;

final class OPTK_Comments_Off extends OPTK_Module {

	/** {@inheritdoc} */
	public function id(): string {
		return 'comments-off';
	}

	/** {@inheritdoc} */
	public function label(): string {
		return 'Optimise Comments Off';
	}

	/** {@inheritdoc} */
	public function description(): string {
		return 'Switches comments off across the whole site, front end and admin.';
	}

	/** {@inheritdoc} */
	public function boot(): void {
		add_filter( 'comments_open', '__return_false', 20, 2 );
		add_filter( 'pings_open', '__return_false', 20, 2 );
		add_filter( 'comments_array', '__return_empty_array', 10, 2 );
		add_action( 'init', [ $this, 'remove_comment_post_type_support' ], 100 );
		add_action( 'admin_menu', [ $this, 'remove_comment_menus' ] );
		add_action( 'admin_init', [ $this, 'block_comment_admin_pages' ] );
		add_action( 'admin_bar_menu', [ $this, 'remove_admin_bar_comments' ], 999 );
		add_action( 'template_redirect', [ $this, 'redirect_comment_feed' ] );
		add_filter( 'feed_links_show_comments_feed', '__return_false' );
		add_action( 'wp_enqueue_scripts', [ $this, 'dequeue_comment_reply' ], 100 );
		add_filter( 'rest_endpoints', [ $this, 'remove_comment_rest_endpoints' ] );
		add_filter( 'xmlrpc_methods', [ $this, 'remove_xmlrpc_new_comment' ] );
	}

	/** Removes comment and trackback support from every registered post type. */
	public function remove_comment_post_type_support(): void {
		foreach ( get_post_types() as $post_type ) {
			if ( post_type_supports( $post_type, 'comments' ) ) {
				remove_post_type_support( $post_type, 'comments' );
				remove_post_type_support( $post_type, 'trackbacks' );
			}
		}
	}

	/** Removes the Comments and Discussion admin menu entries. */
	public function remove_comment_menus(): void {
		remove_menu_page( 'edit-comments.php' );
		remove_submenu_page( 'options-general.php', 'options-discussion.php' );
	}

	/** Redirects away from the comment and discussion screens, and drops the recent comments widget. */
	public function block_comment_admin_pages(): void {
		global $pagenow;

		if ( in_array( $pagenow, [ 'edit-comments.php', 'options-discussion.php' ], true ) ) {
			wp_safe_redirect( admin_url() );
			exit;
		}

		remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
	}

	/**
	 * Removes the Comments item from the admin bar.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar Admin bar instance.
	 */
	public function remove_admin_bar_comments( $wp_admin_bar ): void {
		$wp_admin_bar->remove_node( 'comments' );
	}

	/** Redirects comment feed requests to the home page. */
	public function redirect_comment_feed(): void {
		if ( is_comment_feed() ) {
			wp_safe_redirect( home_url( '/' ), 301 );
			exit;
		}
	}

	/** Dequeues the comment-reply script on the front end. */
	public function dequeue_comment_reply(): void {
		wp_dequeue_script( 'comment-reply' );
	}

	/**
	 * Removes the comment REST API endpoints.
	 *
	 * @param array|mixed $endpoints REST endpoints keyed by route.
	 * @return array|mixed
	 */
	public function remove_comment_rest_endpoints( $endpoints ) {
		if ( is_array( $endpoints ) ) {
			unset( $endpoints['/wp/v2/comments'], $endpoints['/wp/v2/comments/(?P<id>[\d]+)'] );
		}

		return $endpoints;
	}

	/**
	 * Removes the wp.newComment XML-RPC method.
	 *
	 * @param array|mixed $methods XML-RPC methods keyed by name.
	 * @return array|mixed
	 */
	public function remove_xmlrpc_new_comment( $methods ) {
		if ( is_array( $methods ) ) {
			unset( $methods['wp.newComment'] );
		}

		return $methods;
	}
}
