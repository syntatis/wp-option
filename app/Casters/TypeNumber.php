<?php

declare(strict_types=1);

namespace Syntatis\WPOption\Casters;

use Syntatis\WPOption\Contracts\Castable;
use Syntatis\WPOption\Exceptions\TypeError;

use function is_bool;
use function is_float;
use function is_int;
use function is_numeric;

/**
 * Cast a value to an number either integer or float.
 */
class TypeNumber implements Castable
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

	/** @return float|int|null */
	public function cast(int $strict = 0)
	{
		if ($strict === 1) {
			if (! is_int($this->value) && ! is_float($this->value)) {
				throw new TypeError('number', $this->value);
			}

			return $this->value;
		}

		if (is_bool($this->value)) {
			return (int) $this->value;
		}

		if (is_int($this->value) || is_float($this->value)) {
			return $this->value;
		}

		/**
		 * As certain types have undefined behavior when converting to int,
		 * this is also the case when converting to float.
		 *
		 * @see https://www.php.net/manual/en/language.types.float.php
		 */
		if (is_numeric($this->value)) {
			return $this->value * 1;
		}

		return null;
	}
}
