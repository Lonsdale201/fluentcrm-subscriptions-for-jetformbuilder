<?php

declare(strict_types=1);

namespace FluentSubsForJetFormBuilder\Events;

use Jet_Form_Builder\Actions\Events\Base_Executor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class FluentCrmEventExecutor extends Base_Executor {

	public function is_supported(): bool {
		return true;
	}
}
