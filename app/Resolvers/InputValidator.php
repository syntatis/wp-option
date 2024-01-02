<?php

declare(strict_types=1);

namespace Syntatis\WP\Option\Resolvers;

use Syntatis\WP\Option\Option;
use TypeError;

use function gettype;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;

/** @phpstan-import-type OptionType from Option */
class InputValidator
{
	/** @phpstan-var OptionType */
	private string $type;

	/** @phpstan-param OptionType $type */
	public function __construct(string $type)
	{
		$this->type = $type;
	}

	/** @param mixed $value */
	public function validate($value): void
	{
		$givenType = gettype($value);
		$matchedType = $this->hasMatchedType($value);

		if ($matchedType === false) {
			throw new TypeError('Value must be of type ' . $this->type . ', ' . $givenType . ' type given.');
		}

		if ($matchedType === null) {
			throw new TypeError('Unable to validate of type ' . $this->type . '.');
		}
	}

	/** @param mixed $value */
	private function hasMatchedType($value): ?bool
	{
		switch ($this->type) {
			case 'string':
				return is_string($value);

			case 'boolean':
				return is_bool($value);

			case 'integer':
				return is_int($value);

			case 'float':
				return is_float($value) || is_int($value);

			case 'array':
				return is_array($value);

			default:
				return null;
		}
	}
}
