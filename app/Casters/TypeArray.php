<?php

declare(strict_types=1);

namespace Syntatis\WP\Option\Casters;

use Syntatis\WP\Option\Contracts\Castable;
use Syntatis\WP\Option\Exceptions\TypeError;

use function is_array;

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
			if (! is_array($this->value)) {
				throw new TypeError('array', $this->value);
			}

			return $this->value;
		}

		return (array) $this->value;
	}
}
