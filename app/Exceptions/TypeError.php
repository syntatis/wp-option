<?php

declare(strict_types=1);

namespace Syntatis\WP\Option\Exceptions;

use Error;

use function array_is_list;
use function get_class;
use function gettype;
use function is_object;
use function strtolower;

class TypeError extends Error
{
	/**
	 * @param string $expected The expected type of the value e.g. 'string', 'integer', 'array', etc.
	 * @param mixed  $value    The value given.
	 */
	public function __construct(string $expected, $value)
	{
		parent::__construct('Value must be of type ' . $expected . ', ' . $this->inferType($value) . ' given.');
	}

	/**
	 * Infer the type of value given.
	 *
	 * @param mixed $value
	 */
	private function inferType($value): string
	{
		if (is_object($value)) {
			return get_class($value);
		}

		$inferredType = strtolower(gettype($value));

		switch ($inferredType) {
			case 'double':
				$inferredType = 'number (float)';
				break;
			case 'array':
				// @phpstan-ignore-next-line -- `$value` type is inferred with `gettype`.
				$inferredType = array_is_list($value) ? 'array (sequential)' : 'array (associative)';
				break;
		}

		return $inferredType;
	}
}
