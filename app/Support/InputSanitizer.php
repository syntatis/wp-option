<?php

declare(strict_types=1);

namespace Syntatis\WP\Option\Support;

use Syntatis\WP\Option\Option;

use function is_float;

/** @phpstan-import-type ValueType from Option */
class InputSanitizer
{
	/**
	 * @param mixed $value
	 * @return mixed
	 */
	public function sanitize($value)
	{
		if (
			/**
			 * The `null` and `false` value needs to be stored as an array with a key `__syntatis`.
			 * This workaround is to prevent WordPress from storing the value as an empty string.
			 */
			$value === null ||
			$value === false ||
			/**
			 * When updating a value, WordPress will compare the value with the default or
			 * existing value. This can be a problem when comparing a float value with
			 * an integer value such as when comparing 0.0 and 0 which may evaluate
			 * to `true`.
			 */
			is_float($value)
		) {
			$value = ['__syntatis' => $value];
		}

		return $value;
	}
}
