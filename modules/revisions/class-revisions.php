<?php
/**
 * Optimise Revisions: limits how many post revisions WordPress stores.
 *
 * @package OptimiseCreativeToolkit
 */

defined( 'ABSPATH' ) || exit;

final class OPTK_Revisions extends OPTK_Module {

	/** {@inheritdoc} */
	public function id(): string {
		return 'revisions';
	}

	/** {@inheritdoc} */
	public function label(): string {
		return 'Optimise Revisions';
	}

	/** {@inheritdoc} */
	public function description(): string {
		return 'Keeps the database lean by limiting how many revisions each post stores and how often the block editor autosaves.';
	}

	/** {@inheritdoc} */
	public function fields(): array {
		return [
			[
				'key'     => 'keep',
				'label'   => 'Revisions to keep per post',
				'type'    => 'number',
				'default' => 5,
				'help'    => 'Older revisions are removed the next time a post is saved. Set 0 to stop saving revisions.',
			],
			[
				'key'     => 'autosave',
				'label'   => 'Block editor autosave interval (seconds)',
				'type'    => 'number',
				'default' => 120,
				'help'    => 'How often the block editor autosaves drafts. WordPress uses 60 seconds by default.',
			],
		];
	}

	/** {@inheritdoc} */
	public function boot(): void {
		add_filter( 'wp_revisions_to_keep', [ $this, 'keep_revisions' ], 20, 2 );

		// Only the block editor's own setting is changed; the global AUTOSAVE_INTERVAL constant is left alone.
		add_filter( 'block_editor_settings_all', [ $this, 'set_block_editor_autosave' ], 10, 2 );
	}

	/**
	 * Limits the number of revisions stored for a post.
	 *
	 * Returns 0 rather than -1 so that a zero setting really stops saving revisions.
	 *
	 * @param int|mixed     $num  Number of revisions to keep.
	 * @param WP_Post|mixed $post Post being saved.
	 * @return int
	 */
	public function keep_revisions( $num, $post ) {
		return max( 0, (int) $this->opt( 'keep' ) );
	}

	/**
	 * Sets the block editor autosave interval.
	 *
	 * @param array|mixed $settings Block editor settings.
	 * @param WP_Post|mixed $post   Post being edited.
	 * @return array|mixed
	 */
	public function set_block_editor_autosave( $settings, $post = null ) {
		if ( is_array( $settings ) ) {
			$settings['autosaveInterval'] = max( 10, (int) $this->opt( 'autosave' ) );
		}

		return $settings;
	}
}
