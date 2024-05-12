<?php

declare(strict_types=1);

namespace Syntatis\WPOption\Casters;

use Syntatis\WPOption\Contracts\Castable;
use Syntatis\WPOption\Exceptions\TypeError;
use Throwable;

use function is_string;

/**
 * Cast a value to a string.
 *
 * It carries different levels of strictness:
 * - 0: Casts the value to string, if possible. Otherwise, it returns a `null`.
 * - 1: Return the value as is, which may throw an exception if the value is not a string.
 */
class TypeString implements Castable
{
	/**
	 * The value to cast to string.
	 *
	 * @var mixed
	 */
	private $value;

	/** @param mixed $value */
	public function __construct($value)
	{
		$this->value = $value;
	}

	/** @throws TypeError If the value is not a string. */
	public function cast(int $strict = 0): ?string
	{
		if ($this->value === null) {
			return $this->value;
		}

		if ($strict === 1) {
			if (! is_string($this->value)) {
				throw new TypeError('string', $this->value);
			}
		}

		try {
			return (string) $this->value;
		} catch (Throwable $th) {
			return null;
		}
	}
}
