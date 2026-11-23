<?php
/**
 * Loader: boots enabled modules and the settings page.
 */

defined( 'ABSPATH' ) || exit;

final class OPTK_Plugin {

	/** Module registry. Add new modules here. */
	public const MODULES = [
		'clean'        => 'OPTK_Clean',
		'security'     => 'OPTK_Security',
		'environment'  => 'OPTK_Environment',
		'duplicate'    => 'OPTK_Duplicate',
		'lazy-hero'    => 'OPTK_Lazy_Hero',
		'upload-guard' => 'OPTK_Upload_Guard',
		'alt'          => 'OPTK_Alt',
		'noindex'      => 'OPTK_Noindex',
		'revisions'    => 'OPTK_Revisions',
		'comments-off' => 'OPTK_Comments_Off',
		'scripts'      => 'OPTK_Scripts',
		'mail-from'    => 'OPTK_Mail_From',
		'client-mode'  => 'OPTK_Client_Mode',
		'login'        => 'OPTK_Login',
	];

	public function init(): void {
		$modules = [];
		foreach ( self::MODULES as $id => $class ) {
			if ( class_exists( $class ) ) {
				$modules[ $id ] = new $class();
			}
		}

		$enabled = (array) ( get_option( OPTK_OPTION, [] )['enabled'] ?? [] );
		foreach ( $modules as $id => $module ) {
			if ( in_array( $id, $enabled, true ) ) {
				$module->boot();
			}
		}

		if ( is_admin() ) {
			( new OPTK_Settings( $modules ) )->init();
		}
	}
}
