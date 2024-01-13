<?php

declare(strict_types=1);

namespace Syntatis\WP\Option\Casters;

use Syntatis\WP\Option\Contracts\Castable;
use Throwable;
use TypeError;

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
		if ($strict === 1) {
			return $this->value;
		}

		try {
			return (string) $this->value;
		} catch (Throwable $th) {
			return null;
		}
	}
}
