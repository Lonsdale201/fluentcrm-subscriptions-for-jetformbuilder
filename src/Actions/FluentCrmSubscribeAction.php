<?php

declare(strict_types=1);

namespace FluentSubsForJetFormBuilder\Actions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use FluentSubsForJetFormBuilder\Events\AlreadySubscribedEvent;
use FluentSubsForJetFormBuilder\Events\ContactCreatedEvent;
use FluentSubsForJetFormBuilder\Events\ContactUpdatedEvent;
use FluentSubsForJetFormBuilder\Services\FluentCrmData;
use Jet_Form_Builder\Actions\Action_Handler;
use Jet_Form_Builder\Actions\Types\Base;
use Jet_Form_Builder\Classes\Tools;
use Jet_Form_Builder\Exceptions\Action_Exception;
use function __;
use function is_email;
use function sanitize_email;
use function sanitize_text_field;
use function wp_strip_all_tags;

final class FluentCrmSubscribeAction extends Base {

	private FluentCrmData $data_service;

	public function __construct( FluentCrmData $data_service ) {
		$this->data_service = $data_service;
	}

	public function dependence() {
		return $this->data_service->is_available();
	}

	public function get_id() {
		return 'fluentcrm_subscribe';
	}

	public function get_name() {
		return __( 'FluentCRM Subscribe', 'fluent-subs-for-jetformbuilder' );
	}

	public function self_script_name() {
		return 'JetFluentCrmSubscribe';
	}

	/**
	 * Prevent the Subscribe action from being wired to the events it
	 * dispatches itself — that would create infinite recursion if an
	 * admin accidentally selected one of the FLUENTCRM.* events.
	 */
	public function unsupported_events(): array {
		return array(
			ContactCreatedEvent::class,
			ContactUpdatedEvent::class,
			AlreadySubscribedEvent::class,
			'FLUENTCRM.CONTACT_CREATED',
			'FLUENTCRM.CONTACT_UPDATED',
			'FLUENTCRM.ALREADY_SUBSCRIBED',
		);
	}

	public function editor_labels() {
		return array(
			'list_id'                   => __( 'FluentCRM Lists', 'fluent-subs-for-jetformbuilder' ),
			'tag_id'                    => __( 'Tags', 'fluent-subs-for-jetformbuilder' ),
			'bypass_double_optin'       => __( 'Bypass double opt-in', 'fluent-subs-for-jetformbuilder' ),
			'add_only'                  => __( 'Add only', 'fluent-subs-for-jetformbuilder' ),
			'already_subscribed_message'=> __( 'Message if already subscribed', 'fluent-subs-for-jetformbuilder' ),
			'existing_contact_message'  => __( 'Message if contact data updated', 'fluent-subs-for-jetformbuilder' ),
			'fields_map'                => __( 'Fields map', 'fluent-subs-for-jetformbuilder' ),
			'fields_map_email'          => __( 'Email field (ID)', 'fluent-subs-for-jetformbuilder' ),
			'fields_map_first_name'     => __( 'First name field (ID)', 'fluent-subs-for-jetformbuilder' ),
			'fields_map_last_name'      => __( 'Last name field (ID)', 'fluent-subs-for-jetformbuilder' ),
		);
	}

	public function editor_labels_help() {
		return array(
			'bypass_double_optin'        => __( 'Skip FluentCRM double opt-in confirmation email.', 'fluent-subs-for-jetformbuilder' ),
			'fields_map'                 => __( 'Select which JetFormBuilder fields supply the subscriber contact information.', 'fluent-subs-for-jetformbuilder' ),
			'fields_map_email'           => __( 'Provide the JetFormBuilder field ID that contains the subscriber email address.', 'fluent-subs-for-jetformbuilder' ),
			'fields_map_first_name'      => __( 'Optional JetFormBuilder field ID storing the subscriber first name.', 'fluent-subs-for-jetformbuilder' ),
			'fields_map_last_name'       => __( 'Optional JetFormBuilder field ID storing the subscriber last name.', 'fluent-subs-for-jetformbuilder' ),
			'fields_map_phone'           => __( 'Optional JetFormBuilder field ID storing the subscriber phone number.', 'fluent-subs-for-jetformbuilder' ),
			'already_subscribed_message' => __( 'Shown when the email address already belongs to a confirmed FluentCRM contact.', 'fluent-subs-for-jetformbuilder' ),
			'existing_contact_message'   => __( 'Shown when an existing FluentCRM contact is updated because Add only is disabled.', 'fluent-subs-for-jetformbuilder' ),
			'add_only'                  => __( 'When enabled only new subscriptions are processed; existing contacts remain unchanged.', 'fluent-subs-for-jetformbuilder' ),
		);
	}

	public function visible_attributes_for_gateway_editor() {
		return array( 'list_id', 'tag_id', 'bypass_double_optin', 'fields_map' );
	}

	public function action_attributes() {
		return array(
			'list_id'             => array(
				'default' => array(),
			),
			'tag_id'              => array(
				'default' => array(),
			),
			'bypass_double_optin' => array(
				'default' => false,
			),
			'add_only'            => array(
				'default' => false,
			),
			'fields_map'          => array(
				'default' => array(
					'email'      => '',
					'first_name' => '',
					'last_name'  => '',
					'phone'      => '',
				),
			),
			'already_subscribed_message' => array(
				'default' => '',
			),
			'existing_contact_message'   => array(
				'default' => '',
			),
		);
	}

	public function action_data() {
		return array(
			'lists'     => Tools::with_placeholder( $this->data_service->get_lists() ),
			'tags'      => Tools::with_placeholder( $this->data_service->get_tags() ),
			'field_map' => array(
				array(
					'key'      => 'email',
					'label'    => __( 'Email field', 'fluent-subs-for-jetformbuilder' ),
					'help'     => __( 'JetFormBuilder field that stores the subscriber email address.', 'fluent-subs-for-jetformbuilder' ),
					'required' => true,
				),
				array(
					'key'      => 'first_name',
					'label'    => __( 'First name field', 'fluent-subs-for-jetformbuilder' ),
					'help'     => __( 'JetFormBuilder field that stores the subscriber first name (optional).', 'fluent-subs-for-jetformbuilder' ),
					'required' => false,
				),
				array(
					'key'      => 'last_name',
					'label'    => __( 'Last name field', 'fluent-subs-for-jetformbuilder' ),
					'help'     => __( 'JetFormBuilder field that stores the subscriber last name (optional).', 'fluent-subs-for-jetformbuilder' ),
					'required' => false,
				),
				array(
					'key'      => 'phone',
					'label'    => __( 'Phone field', 'fluent-subs-for-jetformbuilder' ),
					'help'     => __( 'JetFormBuilder field that stores the subscriber phone number (optional).', 'fluent-subs-for-jetformbuilder' ),
					'required' => false,
				),
			),
		);
	}

	/**
	 * @param array $request
	 * @param Action_Handler $handler
	 *
	 * @throws Action_Exception
	 */
	public function do_action( array $request, Action_Handler $handler ) {
		$email = $this->get_mapped_value( 'email', $request );

		if ( '' === $email ) {
			throw new Action_Exception(
				__( 'FluentCRM subscription requires a valid email value.', 'fluent-subs-for-jetformbuilder' )
			);
		}

		$email = sanitize_email( $email );

		if ( ! is_email( $email ) ) {
			throw new Action_Exception(
				__( 'FluentCRM subscription received an invalid email address.', 'fluent-subs-for-jetformbuilder' )
			);
		}

		$bypass   = $this->is_bypass_enabled();
		$add_only = $this->is_add_only_enabled();

		$payload = array(
			'email' => $email,
		);

		$first_name = $this->get_mapped_value( 'first_name', $request );
		if ( '' !== $first_name ) {
			$payload['first_name'] = sanitize_text_field( $first_name );
		}

		$last_name = $this->get_mapped_value( 'last_name', $request );
		if ( '' !== $last_name ) {
			$payload['last_name'] = sanitize_text_field( $last_name );
		}

		$phone = $this->get_mapped_value( 'phone', $request );
		if ( '' !== $phone ) {
			$payload['phone'] = sanitize_text_field( $phone );
		}

		$list_ids = $this->normalize_ids( $this->settings['list_id'] ?? array() );
		if ( $list_ids ) {
			$payload['lists'] = $list_ids;
		}

		$tag_ids = $this->normalize_ids( $this->settings['tag_id'] ?? array() );
		if ( $tag_ids ) {
			$payload['tags'] = $tag_ids;
		}

		try {
			$contacts_api = \FluentCrmApi( 'contacts' );
			$existing     = $contacts_api->getContact( $email );

			if ( $existing && isset( $existing->status ) && 'subscribed' === $existing->status ) {
				if ( $add_only ) {
					$this->dispatch_outcome_event(
						$handler,
						AlreadySubscribedEvent::class,
						'already_subscribed',
						$email,
						$existing
					);

					$message = trim( (string) ( $this->settings['already_subscribed_message'] ?? '' ) );

					if ( '' === $message ) {
						$message = __( 'This email address is already subscribed.', 'fluent-subs-for-jetformbuilder' );
					}

					throw new Action_Exception( $message );
				}
			}

			$payload['status'] = $bypass ? 'subscribed' : 'pending';

			$contact = $contacts_api->createOrUpdate(
				$payload,
				$bypass
			);

			if ( ! $contact ) {
				throw new Action_Exception(
					__( 'Unable to create or update the FluentCRM contact.', 'fluent-subs-for-jetformbuilder' )
				);
			}

			if ( $existing ) {
				$this->dispatch_outcome_event(
					$handler,
					ContactUpdatedEvent::class,
					'contact_updated',
					$email,
					$contact
				);
			} else {
				$this->dispatch_outcome_event(
					$handler,
					ContactCreatedEvent::class,
					'contact_created',
					$email,
					$contact
				);
			}

			if ( $existing && ! $add_only ) {
				$update_message = trim( (string) ( $this->settings['existing_contact_message'] ?? '' ) );

				if ( '' === $update_message ) {
					$update_message = __( 'CRM Data updated', 'fluent-subs-for-jetformbuilder' );
				}

				$this->store_success_message( $handler, $update_message );
			}

			$should_send_double_optin = ! $bypass
				&& isset( $contact->status )
				&& 'pending' === $contact->status
				&& method_exists( $contact, 'sendDoubleOptinEmail' );

			if ( $should_send_double_optin ) {
				$contact->sendDoubleOptinEmail();
			}
		} catch ( Action_Exception $exception ) {
			throw $exception;
		} catch ( \Throwable $throwable ) {
			throw new Action_Exception(
				wp_strip_all_tags(
					sprintf(
						/* translators: %s: FluentCRM error message. */
						__( 'FluentCRM error: %s', 'fluent-subs-for-jetformbuilder' ),
						$throwable->getMessage()
					)
				)
			);
		}
	}

	/**
	 * @param string $key
	 * @param array  $request
	 *
	 * @return string
	 */
	private function get_mapped_value( string $key, array $request ): string {
		$fields_map = is_array( $this->settings['fields_map'] ?? null ) ? $this->settings['fields_map'] : array();
		$field_id   = isset( $fields_map[ $key ] ) ? (string) $fields_map[ $key ] : '';

		if ( '' === $field_id ) {
			return '';
		}

		$value = '';

		if ( function_exists( 'jet_fb_context' ) ) {
			$context = jet_fb_context();

			if ( $context ) {
				$context_value = $context->get_value( $field_id );

				if ( null !== $context_value ) {
					$value = $context_value;
				}
			}
		}

		if ( '' === $value && isset( $request[ $field_id ] ) ) {
			$value = $request[ $field_id ];
		}

		if ( is_array( $value ) ) {
			$value = array_filter(
				$value,
				static fn( $item ) => is_scalar( $item ) && '' !== $item
			);
			$value = reset( $value ) ?: '';
		}

		return is_scalar( $value ) ? (string) $value : '';
	}

	/**
	 * @param mixed $raw
	 *
	 * @return array<int, int>
	 */
	private function normalize_ids( $raw ): array {
		if ( empty( $raw ) ) {
			return array();
		}

		if ( ! is_array( $raw ) ) {
			$raw = array( $raw );
		}

		$ids = array();

		foreach ( $raw as $maybe_id ) {
			if ( is_array( $maybe_id ) ) {
				$maybe_id = reset( $maybe_id );
			}

			if ( ! is_scalar( $maybe_id ) ) {
				continue;
			}

			$maybe_id = (int) $maybe_id;

			if ( $maybe_id > 0 ) {
				$ids[] = $maybe_id;
			}
		}

		return array_values( array_unique( $ids ) );
	}

	private function is_bypass_enabled(): bool {
		return ! empty( $this->settings['bypass_double_optin'] );
	}

	private function is_add_only_enabled(): bool {
		return ! empty( $this->settings['add_only'] );
	}

	private function store_success_message( Action_Handler $handler, string $message ): void {
		$handler->add_context_once(
			$this->get_id(),
			array(
				'fluentcrm_success_message' => $message,
			)
		);
	}

	/**
	 * Stash outcome data into the action context (so listening actions
	 * can read it via $handler->get_context( 'fluentcrm_subscribe', ... )),
	 * then synchronously dispatch the matching JFB event.
	 *
	 * The dispatch is wrapped in try/catch because event executors may
	 * fire user-defined actions that throw; we don't want those to mask
	 * the Subscribe action's own outcome (the user's CRM record IS
	 * created — a downstream tag failure shouldn't reverse that).
	 *
	 * @param Action_Handler                $handler
	 * @param class-string                  $event_class
	 * @param string                        $outcome   one of 'contact_created', 'contact_updated', 'already_subscribed'
	 * @param string                        $email
	 * @param object|null                   $contact   FluentCRM contact object, when available
	 */
	private function dispatch_outcome_event(
		Action_Handler $handler,
		string $event_class,
		string $outcome,
		string $email,
		$contact = null
	): void {
		$handler->add_context_once(
			$this->get_id(),
			array(
				'fluentcrm_outcome'    => $outcome,
				'fluentcrm_email'      => $email,
				'fluentcrm_contact_id' => is_object( $contact ) && isset( $contact->id ) ? (int) $contact->id : 0,
			)
		);

		if ( ! function_exists( 'jet_fb_events' ) ) {
			return;
		}

		try {
			jet_fb_events()->execute( $event_class );
		} catch ( \Throwable $throwable ) {
			// Listener actions misbehaving must not roll back the CRM write.
			// Surface to PHP error log only.
			if ( function_exists( 'error_log' ) ) {
				error_log(
					sprintf(
						'[fluent-subs-for-jetformbuilder] Event %s listener threw: %s',
						$event_class,
						$throwable->getMessage()
					)
				);
			}
		}
	}
}
