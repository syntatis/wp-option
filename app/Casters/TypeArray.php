<?php

declare(strict_types=1);

namespace Syntatis\WP\Option\Casters;

use Syntatis\WP\Option\Contracts\Castable;

/**
 * Cast a value to an array.
 *
 * It carries different levels of strictness:
 *
 * - 0: Casts the value to an array, if possible. Otherwise, it returns a `null`.
 * - 1: Return the value as is, which may throw an exception if the value is not an array.
 */
class TypeArray implements Castable
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

	/** @return array<mixed>|null */
	public function cast(int $strict = 0): ?array
	{
		if ($strict === 1) {
			return $this->value;
		}

		return (array) $this->value;
	}
}
