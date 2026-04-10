<?php

declare(strict_types=1);

namespace FluentSubsForJetFormBuilder\Actions;

use FluentSubsForJetFormBuilder\Services\FluentCrmData;
use Jet_Form_Builder\Actions\Action_Handler;
use Jet_Form_Builder\Actions\Types\Base;
use Jet_Form_Builder\Classes\Tools;
use Jet_Form_Builder\Exceptions\Action_Exception;
use function __;
use function is_email;
use function sanitize_email;
use function sanitize_key;
use function sanitize_text_field;
use function wp_get_current_user;
use function wp_strip_all_tags;

final class FluentCrmAddListTagsAction extends Base {

	private FluentCrmData $data_service;

	public function __construct( FluentCrmData $data_service ) {
		$this->data_service = $data_service;
	}

	public function dependence() {
		return $this->data_service->is_available();
	}

	public function get_id() {
		return 'fluentcrm_add_list_tags';
	}

	public function get_name() {
		return __( 'FluentCRM Add List & Tags', 'fluent-subs-for-jetformbuilder' );
	}

	public function self_script_name() {
		return 'JetFluentCrmAddListTags';
	}

	public function editor_labels() {
		return array(
			'list_id'            => __( 'FluentCRM Lists', 'fluent-subs-for-jetformbuilder' ),
			'tag_id'             => __( 'Tags', 'fluent-subs-for-jetformbuilder' ),
			'use_current_user'   => __( 'Use current user', 'fluent-subs-for-jetformbuilder' ),
			'skip_non_existing'  => __( 'Skip if not in CRM', 'fluent-subs-for-jetformbuilder' ),
			'new_contact_status' => __( 'New contact status', 'fluent-subs-for-jetformbuilder' ),
			'fields_map'              => __( 'Fields map', 'fluent-subs-for-jetformbuilder' ),
			'fields_map_email'        => __( 'Email field (ID)', 'fluent-subs-for-jetformbuilder' ),
			'fields_map_first_name'   => __( 'First name field (ID)', 'fluent-subs-for-jetformbuilder' ),
			'fields_map_last_name'    => __( 'Last name field (ID)', 'fluent-subs-for-jetformbuilder' ),
			'fields_map_phone'        => __( 'Phone field (ID)', 'fluent-subs-for-jetformbuilder' ),
		);
	}

	public function editor_labels_help() {
		return array(
			'use_current_user'   => __( 'Identify the contact by the logged-in user\'s email instead of a form field. Enable when the form is restricted to authenticated users.', 'fluent-subs-for-jetformbuilder' ),
			'skip_non_existing'  => __( 'When enabled, contacts not yet in FluentCRM are silently skipped — no lists or tags are assigned and no new contact is created.', 'fluent-subs-for-jetformbuilder' ),
			'new_contact_status' => __( 'Status assigned to contacts that are automatically created because they were not found in FluentCRM.', 'fluent-subs-for-jetformbuilder' ),
			'fields_map'         => __( 'These fields are only used when a new contact is created in FluentCRM. Existing contacts are not updated — only lists and tags are assigned.', 'fluent-subs-for-jetformbuilder' ),
			'fields_map_email'   => __( 'JetFormBuilder field that stores the email address.', 'fluent-subs-for-jetformbuilder' ),
			'fields_map_first_name' => __( 'Optional JetFormBuilder field storing the contact first name.', 'fluent-subs-for-jetformbuilder' ),
			'fields_map_last_name'  => __( 'Optional JetFormBuilder field storing the contact last name.', 'fluent-subs-for-jetformbuilder' ),
			'fields_map_phone'      => __( 'Optional JetFormBuilder field storing the contact phone number.', 'fluent-subs-for-jetformbuilder' ),
		);
	}

	public function visible_attributes_for_gateway_editor() {
		return array( 'list_id', 'tag_id', 'use_current_user', 'fields_map' );
	}

	public function action_attributes() {
		return array(
			'list_id' => array(
				'default' => array(),
			),
			'tag_id' => array(
				'default' => array(),
			),
			'use_current_user' => array(
				'default' => false,
			),
			'skip_non_existing' => array(
				'default' => false,
			),
			'new_contact_status' => array(
				'default' => 'subscribed',
			),
			'fields_map' => array(
				'default' => array(
					'email'      => '',
					'first_name' => '',
					'last_name'  => '',
					'phone'      => '',
				),
			),
		);
	}

	public function action_data() {
		return array(
			'lists'    => Tools::with_placeholder( $this->data_service->get_lists() ),
			'tags'     => Tools::with_placeholder( $this->data_service->get_tags() ),
			'statuses' => $this->data_service->get_statuses(),
			'field_map' => array(
				array(
					'key'      => 'email',
					'label'    => __( 'Email field', 'fluent-subs-for-jetformbuilder' ),
					'help'     => __( 'JetFormBuilder field that stores the email address.', 'fluent-subs-for-jetformbuilder' ),
					'required' => true,
				),
				array(
					'key'      => 'first_name',
					'label'    => __( 'First name field', 'fluent-subs-for-jetformbuilder' ),
					'help'     => __( 'JetFormBuilder field that stores the contact first name (optional).', 'fluent-subs-for-jetformbuilder' ),
					'required' => false,
				),
				array(
					'key'      => 'last_name',
					'label'    => __( 'Last name field', 'fluent-subs-for-jetformbuilder' ),
					'help'     => __( 'JetFormBuilder field that stores the contact last name (optional).', 'fluent-subs-for-jetformbuilder' ),
					'required' => false,
				),
				array(
					'key'      => 'phone',
					'label'    => __( 'Phone field', 'fluent-subs-for-jetformbuilder' ),
					'help'     => __( 'JetFormBuilder field that stores the contact phone number (optional).', 'fluent-subs-for-jetformbuilder' ),
					'required' => false,
				),
			),
		);
	}

	/**
	 * @param array          $request
	 * @param Action_Handler $handler
	 *
	 * @throws Action_Exception
	 */
	public function do_action( array $request, Action_Handler $handler ) {
		$use_current_user = ! empty( $this->settings['use_current_user'] );

		if ( $use_current_user ) {
			$user = wp_get_current_user();

			if ( ! $user || 0 === $user->ID ) {
				throw new Action_Exception(
					__( 'FluentCRM Add List & Tags requires a logged-in user when "Use current user" is enabled.', 'fluent-subs-for-jetformbuilder' )
				);
			}

			$email = $user->user_email;
		} else {
			$email = $this->get_mapped_value( 'email', $request );
		}

		if ( '' === $email ) {
			throw new Action_Exception(
				__( 'FluentCRM Add List & Tags requires a valid email address.', 'fluent-subs-for-jetformbuilder' )
			);
		}

		$email = sanitize_email( $email );

		if ( ! is_email( $email ) ) {
			throw new Action_Exception(
				__( 'FluentCRM Add List & Tags received an invalid email address.', 'fluent-subs-for-jetformbuilder' )
			);
		}

		try {
			$contacts_api = \FluentCrmApi( 'contacts' );
			$contact      = $contacts_api->getContact( $email );

			if ( ! $contact ) {
				if ( ! empty( $this->settings['skip_non_existing'] ) ) {
					return;
				}

				$status  = sanitize_key( $this->settings['new_contact_status'] ?? 'subscribed' );
				$payload = array(
					'email'  => $email,
					'status' => $status,
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

				$contact = $contacts_api->createOrUpdate( $payload, true );

				if ( ! $contact ) {
					throw new Action_Exception(
						__( 'Unable to create the FluentCRM contact.', 'fluent-subs-for-jetformbuilder' )
					);
				}
			}

			$list_ids = $this->normalize_ids( $this->settings['list_id'] ?? array() );
			$tag_ids  = $this->normalize_ids( $this->settings['tag_id'] ?? array() );

			if ( $list_ids ) {
				$contact->attachLists( $list_ids );
			}

			if ( $tag_ids ) {
				$contact->attachTags( $tag_ids );
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
}
