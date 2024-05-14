<?php

declare(strict_types=1);

namespace Syntatis\WPOption\Casters;

use Syntatis\WPOption\Contracts\Castable;
use Syntatis\WPOption\Exceptions\TypeError;

use function in_array;
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

		if (in_array($this->value, ['false', '0'], true)) {
			return false;
		}

		if (in_array($this->value, ['true', '1'], true)) {
			return true;
		}

		return (bool) $this->value;
	}
}
