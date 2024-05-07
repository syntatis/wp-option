<?php

declare(strict_types=1);

namespace Syntatis\WP\Option\Support;

use Syntatis\WP\Option\Option;

/** @phpstan-import-type ValueType from Option */
class InputSanitizer
{
	/**
	 * @param mixed $value
	 * @return mixed
	 */
	public function sanitize($value)
	{
		/**
		 * The `null` value needs to be stored as an array with a key `__syntatis`.
		 * This workaround is to prevent WordPress from storing the value as an
		 * empty string.
		 */
		if ($value === null) {
			$value = ['__syntatis' => $value];
		}

		return $value;
	}
}
