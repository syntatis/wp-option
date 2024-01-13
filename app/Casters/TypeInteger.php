<?php

declare(strict_types=1);

namespace Syntatis\WP\Option\Casters;

use Syntatis\WP\Option\Contracts\Castable;

use function is_bool;
use function is_numeric;
use function is_string;

/**
 * Cast a value to an integer.
 *
 * It carries different levels of strictness:
 *
 * - 0: Casts for boolean, numeric, string to integer. Otherwise, it returns a `null`.
 * - 1: Return the value as is, which may throw an exception if it is not an integer.
 */
class TypeInteger implements Castable
{
	/**
	 * The value to cast to an integer.
	 *
	 * @var mixed
	 */
	private $value;

	/** @param mixed $value */
	public function __construct($value)
	{
		$this->value = $value;
	}

	public function cast(int $strict = 0): ?int
	{
		if ($strict === 1) {
			return $this->value;
		}

		/**
		 * The behaviour of converting to int is undefined for other types.
		 * Do not rely on any observed behaviour, as it can change without
		 * notice.
		 *
		 * @see https://www.php.net/manual/en/language.types.integer.php
		 */
		if (
			is_bool($this->value) ||
			is_numeric($this->value) ||
			is_string($this->value) ||
			$this->value === null
		) {
			return (int) $this->value;
		}

		return null;
	}
}
