<?php

declare(strict_types=1);

namespace FluentSubsForJetFormBuilder\Events;

use Jet_Form_Builder\Actions\Events\Base_Event;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AlreadySubscribedEvent extends Base_Event {

	public function get_id(): string {
		return 'FLUENTCRM.ALREADY_SUBSCRIBED';
	}

	public function get_label(): string {
		return __( 'When the FluentCRM contact is already subscribed', 'fluent-subs-for-jetformbuilder' );
	}

	public function get_help(): string {
		return __(
			'Fires when the FluentCRM Subscribe action found that the email is already a subscribed contact and "Add only" was enabled, so no update was performed. The Subscribe action then halts the submit chain via its own message — actions wired to this event run before the halt.',
			'fluent-subs-for-jetformbuilder'
		);
	}

	public function executors(): array {
		return array( new FluentCrmEventExecutor() );
	}
}
