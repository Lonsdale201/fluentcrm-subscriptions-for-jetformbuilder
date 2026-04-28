<?php

declare(strict_types=1);

namespace FluentSubsForJetFormBuilder\Events;

use Jet_Form_Builder\Actions\Events\Base_Event;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ContactUpdatedEvent extends Base_Event {

	public function get_id(): string {
		return 'FLUENTCRM.CONTACT_UPDATED';
	}

	public function get_label(): string {
		return __( 'When an existing FluentCRM contact is updated', 'fluent-subs-for-jetformbuilder' );
	}

	public function get_help(): string {
		return __(
			'Fires after the FluentCRM Subscribe action updated an already existing contact — for example new lists or tags were added, or the contact was reactivated from pending status.',
			'fluent-subs-for-jetformbuilder'
		);
	}

	public function executors(): array {
		return array( new FluentCrmEventExecutor() );
	}
}
