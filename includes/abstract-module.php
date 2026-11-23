<?php
/**
 * Base class every module extends.
 *
 * A module declares its id, label and description, registers its hooks in
 * boot(), and may declare settings fields. Fields are rendered and sanitised
 * by OPTK_Settings; read saved values with $this->opt().
 */

defined( 'ABSPATH' ) || exit;

abstract class OPTK_Module {

	/** Unique slug, matches the folder name, e.g. 'clean'. */
	abstract public function id(): string;

	/** Display name, e.g. 'Optimise Clean'. */
	abstract public function label(): string;

	/** One-line description shown on the settings card. */
	abstract public function description(): string;

	/** Register add_action / add_filter calls. Only called when enabled. */
	abstract public function boot(): void;

	/**
	 * Settings fields for this module.
	 *
	 * Each field: [
	 *   'key'     => 'logo_url',                        // stored under this key
	 *   'label'   => 'Logo URL',
	 *   'type'    => 'checkbox'|'text'|'url'|'number'|'color'|'textarea'|'code'|'select',
	 *                                                    // 'code' keeps raw HTML/JS; only unfiltered_html users can change it
	 *   'default' => mixed,
	 *   'options' => [ 'value' => 'Label' ],            // select only
	 *   'help'    => 'Optional help text',
	 * ]
	 */
	public function fields(): array {
		return [];
	}

	/** Read a saved setting for this module, falling back to the field default. */
	protected function opt( string $key, $default = null ) {
		$all = get_option( OPTK_OPTION, [] );
		if ( isset( $all['modules'][ $this->id() ][ $key ] ) ) {
			return $all['modules'][ $this->id() ][ $key ];
		}
		if ( null === $default ) {
			foreach ( $this->fields() as $field ) {
				if ( $field['key'] === $key ) {
					return $field['default'] ?? null;
				}
			}
		}
		return $default;
	}

	/** URL to a file inside this module's folder. */
	protected function url( string $file ): string {
		return OPTK_URL . 'modules/' . $this->id() . '/' . ltrim( $file, '/' );
	}
}
