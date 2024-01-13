<?php

declare(strict_types=1);

namespace Syntatis\WP\Option\Support;

use function array_key_exists;
use function is_array;

class InputSanitizer
{
	/**
	 * @param mixed $value
	 * @return array<string, mixed>
	 *
	 * @psalm-return array{__syntatis: mixed}
	 */
	public function sanitize($value): array
	{
		/** @psalm-var array{__syntatis: mixed} */
		$value = is_array($value) && array_key_exists('__syntatis', $value) ? $value : ['__syntatis' => $value];

		return $value;
	}
}
