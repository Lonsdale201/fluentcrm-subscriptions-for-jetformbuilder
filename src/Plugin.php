<?php

declare(strict_types=1);

namespace FluentSubsForJetFormBuilder;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use FluentSubsForJetFormBuilder\Actions\FluentCrmAddListTagsAction;
use FluentSubsForJetFormBuilder\Actions\FluentCrmSubscribeAction;
use FluentSubsForJetFormBuilder\Events\AlreadySubscribedEvent;
use FluentSubsForJetFormBuilder\Events\ContactCreatedEvent;
use FluentSubsForJetFormBuilder\Events\ContactUpdatedEvent;
use FluentSubsForJetFormBuilder\Services\FluentCrmData;
use Jet_Form_Builder\Actions\Manager;
use Jet_Form_Builder\Form_Messages\Manager as Messages_Manager;
use function determine_locale;
use function load_plugin_textdomain;
use function load_textdomain;
use function plugin_basename;
use YahnisElsts\PluginUpdateChecker\v5p0\PucFactory;

final class Plugin {

	public const MINIMUM_FLUENTCRM_VERSION = '2.8.0';

	private static ?self $instance = null;

	private string $plugin_file;

	private string $version;

	private FluentCrmData $data_service;

	private function __construct( string $plugin_file ) {
		$this->plugin_file  = $plugin_file;
		$this->version      = get_file_data( $plugin_file, array( 'Version' => 'Version' ) )['Version'] ?? '0.0';
		$this->data_service = new FluentCrmData();

		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'plugins_loaded', array( $this, 'on_plugins_loaded' ), 12 );
	}

	public static function instance( ?string $plugin_file = null ): self {
		if ( null === self::$instance ) {
			if ( null === $plugin_file ) {
				throw new \RuntimeException( 'Plugin file path is required on first initialization.' );
			}

			self::$instance = new self( $plugin_file );
		}

		return self::$instance;
	}

	public function on_plugins_loaded(): void {
		$this->init_updater();

		add_action( 'jet-form-builder/actions/register', array( $this, 'register_action' ) );
		add_filter( 'jet-form-builder/event-types', array( $this, 'register_event_types' ) );
		add_action( 'jet-form-builder/editor-assets/before', array( $this, 'enqueue_editor_assets' ) );
		add_action( 'jet-form-builder/form-handler/after-send', array( $this, 'maybe_adjust_response_message' ), 10, 2 );
	}

	public function load_textdomain(): void {
		$domain = 'fluent-subs-for-jetformbuilder';
		$locale = determine_locale();
		$mofile = WP_LANG_DIR . '/plugins/' . $domain . '-' . $locale . '.mo';

		if ( file_exists( $mofile ) ) {
			load_textdomain( $domain, $mofile );
		}

		load_plugin_textdomain(
			$domain,
			false,
			dirname( plugin_basename( $this->plugin_file ) ) . '/languages'
		);
	}

	public function register_action( Manager $manager ): void {
		$manager->register_action_type(
			new FluentCrmSubscribeAction( $this->data_service )
		);
		$manager->register_action_type(
			new FluentCrmAddListTagsAction( $this->data_service )
		);
	}

	/**
	 * Register custom JFB action events fired by the Subscribe action.
	 *
	 * Other actions on the form (typically FluentCrmAddListTagsAction)
	 * can be wired to these events via the action editor's events
	 * picker, so they only run on the matching outcome.
	 *
	 * @param array<int, object> $events
	 *
	 * @return array<int, object>
	 */
	public function register_event_types( array $events ): array {
		$events[] = new ContactCreatedEvent();
		$events[] = new ContactUpdatedEvent();
		$events[] = new AlreadySubscribedEvent();

		return $events;
	}

	public function enqueue_editor_assets(): void {
		$asset_rel_path = 'assets/js/editor-action.js';
		$asset_path     = $this->asset_path( $asset_rel_path );
		$version        = file_exists( $asset_path ) ? (string) filemtime( $asset_path ) : $this->version;
		$style_rel_path = 'assets/js/editor-action.css';
		$style_path     = $this->asset_path( $style_rel_path );
		$style_version  = file_exists( $style_path ) ? (string) filemtime( $style_path ) : $this->version;
		$dependencies   = array( 'jet-fb-components', 'wp-element', 'wp-components', 'wp-i18n' );

		foreach ( array( 'jet-fb-actions-v2', 'jet-fb-blocks-v2-to-actions-v2' ) as $handle ) {
			if ( wp_script_is( $handle, 'registered' ) ) {
				$dependencies[] = $handle;
			}
		}

		wp_register_script(
			'fluent-subs-jetformbuilder-action',
			$this->asset_url( $asset_rel_path ),
			$dependencies,
			$version,
			true
		);

		wp_register_style(
			'fluent-subs-jetformbuilder-action',
			$this->asset_url( $style_rel_path ),
			array(),
			$style_version
		);

		wp_enqueue_script( 'fluent-subs-jetformbuilder-action' );
		wp_enqueue_style( 'fluent-subs-jetformbuilder-action' );
	}

	public function init_updater(): void {
		PucFactory::buildUpdateChecker(
			'https://pluginupdater.hellodevs.dev/plugins/fluent-subs-for-jetformbuilder.json',
			$this->plugin_file,
			'fluent-subs-for-jetformbuilder'
		);
	}

	public function asset_url( string $relative = '' ): string {
		return plugin_dir_url( $this->plugin_file ) . ltrim( $relative, '/' );
	}

	public function asset_path( string $relative = '' ): string {
		return plugin_dir_path( $this->plugin_file ) . ltrim( $relative, '/' );
	}

	public function version(): string {
		return $this->version;
	}

	public function maybe_adjust_response_message( $form_handler, bool $is_success ): void {
		if ( ! $is_success || ! isset( $form_handler->action_handler ) ) {
			return;
		}

		$action_handler = $form_handler->action_handler;
		$message        = $action_handler->get_context( 'fluentcrm_subscribe', 'fluentcrm_success_message' );

		if ( ! $message ) {
			return;
		}

		$form_handler->set_response_args(
			array(
				'status'  => Messages_Manager::dynamic_success( $message ),
				'message' => $message,
			)
		);
	}
}
