<?php

declare(strict_types=1);

namespace FluentSubsForJetFormBuilder\Events;

use Jet_Form_Builder\Actions\Events\Base_Event;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ContactCreatedEvent extends Base_Event {

	public function get_id(): string {
		return 'FLUENTCRM.CONTACT_CREATED';
	}

	public function get_label(): string {
		return __( 'When a new FluentCRM contact is created', 'fluent-subs-for-jetformbuilder' );
	}

	public function get_help(): string {
		return __(
			'Fires after the FluentCRM Subscribe action successfully created a brand new contact (the email did not exist in FluentCRM before this submission).',
			'fluent-subs-for-jetformbuilder'
		);
	}

	public function executors(): array {
		return array( new FluentCrmEventExecutor() );
	}
}
