<?php

declare(strict_types=1);

namespace Syntatis\WP\Option\Casters;

use Syntatis\WP\Option\Contracts\Castable;

use function is_bool;
use function is_numeric;
use function is_string;

/**
 * Cast a value to an integer.
 */
class TypeFloat implements Castable
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

	public function cast(int $strict = 0): ?float
	{
		if ($strict === 1) {
			return $this->value;
		}

		/**
		 * As certain types have undefined behavior when converting to int,
		 * this is also the case when converting to float.
		 *
		 * @see https://www.php.net/manual/en/language.types.float.php
		 */
		if (
			is_bool($this->value) ||
			is_numeric($this->value) ||
			is_string($this->value) ||
			$this->value === null
		) {
			return (float) $this->value;
		}

		return null;
	}
}
