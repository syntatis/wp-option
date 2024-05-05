<?php

declare(strict_types=1);

namespace Syntatis\WP\Option\Casters;

use Syntatis\WP\Option\Contracts\Castable;
use Syntatis\WP\Option\Exceptions\TypeError;

use function is_bool;

/**
 * Cast a value to a boolean.
 *
 * It carries different levels of strictness:
 *
 * - 0: Casts the value to boolean, if possible. Otherwise, it returns a `null`.
 * - 1: Return the value as is, which may throw an exception if the value is not a boolean.
 */
class TypeBoolean implements Castable
{
	/**
	 * The value to cast to a boolean.
	 *
	 * @var mixed
	 */
	private $value;

	/** @param mixed $value */
	public function __construct($value)
	{
		$this->value = $value;
	}

	public function cast(int $strict = 0): ?bool
	{
		if ($strict === 1) {
			if (! is_bool($this->value)) {
				throw new TypeError('boolean', $this->value);
			}

			return $this->value;
		}

		return (bool) $this->value;
	}
}
