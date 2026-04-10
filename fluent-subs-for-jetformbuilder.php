<?php
/**
 * Plugin Name: FluentCRM Subscriptions for JetFormBuilder
 * Description: Adds a FluentCRM subscription action to JetFormBuilder forms.
 * Version: 1.2
 * Author: Soczó Kristóf
 * Author URI: https://github.com/Lonsdale201?tab=repositories
 * Plugin URI: https://github.com/Lonsdale201/fluentcrm-subscriptions-for-jetformbuilder
 * Text Domain: fluent-subs-for-jetformbuilder
 * Requires PHP: 8.0
 * Requires Plugins: fluent-crm, jetformbuilder
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

declare(strict_types=1);

use FluentSubsForJetFormBuilder\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$autoload = __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/plugin-update-checker/plugin-update-checker.php';

if ( file_exists( $autoload ) ) {
	require $autoload;
}

if ( ! defined( 'FLUENT_SFJB_FILE' ) ) {
	define( 'FLUENT_SFJB_FILE', __FILE__ );
}

$update_checker_bootstrap = __DIR__ . '/plugin-update-checker/plugin-update-checker.php';

if ( file_exists( $update_checker_bootstrap ) ) {
	require_once $update_checker_bootstrap;
}

if ( ! class_exists( Plugin::class ) ) {
	spl_autoload_register(
		static function ( string $class ): void {
			$prefix = 'FluentSubsForJetFormBuilder\\';

			if ( 0 !== strpos( $class, $prefix ) ) {
				return;
			}

			$relative_class = substr( $class, strlen( $prefix ) );
			$relative_path  = str_replace( '\\', DIRECTORY_SEPARATOR, $relative_class );
			$file           = __DIR__ . '/src/' . $relative_path . '.php';

			if ( file_exists( $file ) ) {
				require $file;
			}
		}
	);
}

const FLUENT_SFJB_MIN_PHP_VERSION = '8.0';
const FLUENT_SFJB_MIN_WP_VERSION  = '6.0';

register_activation_hook(
	__FILE__,
	static function (): void {
		$errors = fluent_sfjfb_requirement_errors();

		if ( empty( $errors ) ) {
			return;
		}

		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		deactivate_plugins( plugin_basename( __FILE__ ) );

		unset( $_GET['activate'] );

		$GLOBALS['fluent_sfjfb_activation_errors'] = $errors;

		add_action( 'admin_notices', 'fluent_sfjfb_activation_admin_notice' );
	}
);

if ( ! function_exists( 'fluent_sfjfb_requirement_errors' ) ) {
	/**
	 * Gather unmet requirement messages.
	 *
	 * @param bool $include_plugin_checks Whether to validate plugin dependencies.
	 *
	 * @return string[]
	 */
	function fluent_sfjfb_requirement_errors( bool $include_plugin_checks = true ): array {
		$errors = array();

		if ( version_compare( PHP_VERSION, FLUENT_SFJB_MIN_PHP_VERSION, '<' ) ) {
			$errors[] = sprintf(
				/* translators: 1: required PHP version, 2: current PHP version */
				__( 'Requires PHP version %1$s or higher. Current version: %2$s.', 'fluent-subs-for-jetformbuilder' ),
				FLUENT_SFJB_MIN_PHP_VERSION,
				PHP_VERSION
			);
		}

		global $wp_version;

		if ( version_compare( $wp_version, FLUENT_SFJB_MIN_WP_VERSION, '<' ) ) {
			$errors[] = sprintf(
				/* translators: 1: required WordPress version, 2: current WordPress version */
				__( 'Requires WordPress version %1$s or higher. Current version: %2$s.', 'fluent-subs-for-jetformbuilder' ),
				FLUENT_SFJB_MIN_WP_VERSION,
				$wp_version
			);
		}

		if ( ! $include_plugin_checks ) {
			return $errors;
		}

		if ( ! defined( 'JET_FORM_BUILDER_VERSION' ) && ! class_exists( '\Jet_Form_Builder\Plugin' ) ) {
			$errors[] = __( 'Requires the JetFormBuilder plugin to be installed and active.', 'fluent-subs-for-jetformbuilder' );
		}

		if ( ! defined( 'FLUENTCRM_PLUGIN_VERSION' ) ) {
			$errors[] = sprintf(
				/* translators: %s: minimum FluentCRM version */
				__( 'Requires FluentCRM version %s or higher to be installed and active.', 'fluent-subs-for-jetformbuilder' ),
				Plugin::MINIMUM_FLUENTCRM_VERSION
			);
		} elseif ( version_compare( FLUENTCRM_PLUGIN_VERSION, Plugin::MINIMUM_FLUENTCRM_VERSION, '<' ) ) {
			$errors[] = sprintf(
				/* translators: 1: required FluentCRM version, 2: current FluentCRM version */
				__( 'Requires FluentCRM version %1$s or higher. Current version: %2$s.', 'fluent-subs-for-jetformbuilder' ),
				Plugin::MINIMUM_FLUENTCRM_VERSION,
				FLUENTCRM_PLUGIN_VERSION
			);
		}

		return $errors;
	}
}

if ( ! function_exists( 'fluent_sfjfb_activation_admin_notice' ) ) {
	function fluent_sfjfb_activation_admin_notice(): void {
		if ( empty( $GLOBALS['fluent_sfjfb_activation_errors'] ) || ! is_array( $GLOBALS['fluent_sfjfb_activation_errors'] ) ) {
			return;
		}

		$errors = $GLOBALS['fluent_sfjfb_activation_errors'];

		printf(
			'<div class="notice notice-error is-dismissible"><p><strong>%s</strong></p><ul><li>%s</li></ul></div>',
			esc_html__( 'Fluent Subscriptions for JetFormBuilder could not be activated.', 'fluent-subs-for-jetformbuilder' ),
			implode( '</li><li>', array_map( 'esc_html', $errors ) )
		);

		unset( $GLOBALS['fluent_sfjfb_activation_errors'] );
	}
}

if ( ! function_exists( 'fluent_sfjfb_admin_notice' ) ) {
	function fluent_sfjfb_admin_notice(): void {
		$errors = $GLOBALS['fluent_sfjfb_runtime_errors'] ?? fluent_sfjfb_requirement_errors();

		if ( empty( $errors ) ) {
			return;
		}

		printf(
			'<div class="notice notice-error"><p><strong>%s</strong></p><ul><li>%s</li></ul></div>',
			esc_html__( 'Fluent Subscriptions for JetFormBuilder cannot run:', 'fluent-subs-for-jetformbuilder' ),
			implode( '</li><li>', array_map( 'esc_html', $errors ) )
		);
	}
}

$initial_environment_errors = fluent_sfjfb_requirement_errors( false );

if ( ! empty( $initial_environment_errors ) ) {
	$GLOBALS['fluent_sfjfb_runtime_errors'] = $initial_environment_errors;

	if ( is_admin() ) {
		add_action( 'admin_notices', 'fluent_sfjfb_admin_notice' );
	}
	return;
}

add_action(
	'plugins_loaded',
	static function () {
		$errors = fluent_sfjfb_requirement_errors();

		if ( ! empty( $errors ) ) {
			$GLOBALS['fluent_sfjfb_runtime_errors'] = $errors;

			if ( is_admin() ) {
				add_action( 'admin_notices', 'fluent_sfjfb_admin_notice' );
			}

			return;
		}

		if ( class_exists( Plugin::class ) ) {
			Plugin::instance( __FILE__ );
		}
	}
);
