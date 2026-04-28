<?php

declare(strict_types=1);

namespace FluentSubsForJetFormBuilder\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use function __;

final class FluentCrmData {

	public function is_available(): bool {
		return function_exists( 'FluentCrmApi' );
	}

	/**
	 * @return array<int, array<string, string>>
	 */
	public function get_lists(): array {
		if ( ! $this->is_available() ) {
			return array();
		}

		try {
			$lists = \FluentCrmApi( 'lists' )->all();
		} catch ( \Throwable $throwable ) {
			return array();
		}

		return $this->map_to_options( $lists, 'list' );
	}

	/**
	 * @return array<int, array<string, string>>
	 */
	public function get_tags(): array {
		if ( ! $this->is_available() ) {
			return array();
		}

		try {
			$tags = \FluentCrmApi( 'tags' )->all();
		} catch ( \Throwable $throwable ) {
			return array();
		}

		return $this->map_to_options( $tags, 'tag' );
	}

	/**
	 * @return array<int, array<string, string>>
	 */
	public function get_statuses(): array {
		if ( ! $this->is_available() || ! function_exists( 'fluentcrm_subscriber_editable_statuses' ) ) {
			return array();
		}

		try {
			$statuses = fluentcrm_subscriber_editable_statuses( true );
		} catch ( \Throwable $throwable ) {
			return array();
		}

		$options = array();

		foreach ( $statuses as $status ) {
			$id    = (string) ( $status['id'] ?? $status['slug'] ?? '' );
			$title = (string) ( $status['title'] ?? '' );

			if ( '' === $id ) {
				continue;
			}

			if ( '' === $title ) {
				$title = ucfirst( $id );
			}

			$options[] = array(
				'value' => $id,
				'label' => $title,
			);
		}

		return $options;
	}

	/**
	 * @param iterable<int, object|array> $items
	 * @param string                      $fallback_key
	 *
	 * @return array<int, array<string, string>>
	 */
	private function map_to_options( iterable $items, string $fallback_key ): array {
		$options = array();

		foreach ( $items as $item ) {
			$id    = '';
			$title = '';

			if ( is_object( $item ) ) {
				$id    = (string) ( $item->id ?? '' );
				$title = (string) ( $item->title ?? $item->name ?? '' );
			} elseif ( is_array( $item ) ) {
				$id    = (string) ( $item['id'] ?? '' );
				$title = (string) ( $item['title'] ?? $item['name'] ?? '' );
			}

			if ( '' === $id ) {
				continue;
			}

				if ( '' === $title ) {
					$title = sprintf(
						/* translators: %1$s: entity type name; %2$s: numeric identifier. */
						__( '%1$s #%2$s', 'fluent-subs-for-jetformbuilder' ),
						ucfirst( $fallback_key ),
						$id
					);
			}

			$options[] = array(
				'value' => $id,
				'label' => $title,
			);
		}

		return $options;
	}
}
