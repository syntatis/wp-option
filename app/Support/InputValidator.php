<?php

declare(strict_types=1);

namespace Syntatis\WP\Option\Support;

use InvalidArgumentException;
use Symfony\Component\Validator\Constraint;
use Syntatis\Utils\Validator\Validator;
use Syntatis\WP\Option\Option;
use TypeError;

use function array_key_exists;
use function gettype;
use function is_array;
use function is_bool;
use function is_callable;
use function is_float;
use function is_int;
use function is_string;

/**
 * @phpstan-import-type OptionConstraints from Option
 * @phpstan-import-type OptionType from Option
 */
class InputValidator
{
	/** @phpstan-var OptionType */
	private string $type;

	/** @phpstan-var array<callable> */
	private array $constraints = [];

	/**
	 * @phpstan-param OptionType $type
	 * @phpstan-param OptionConstraints $constraints
	 */
	public function __construct(string $type, $constraints = [])
	{
		$this->type = $type;
		$this->constraints = ! is_array($constraints) ? [$constraints] : $constraints;
	}

	/** @param mixed $value */
	public function validate($value): void
	{
		$value = is_array($value) && array_key_exists('__syntatis', $value) ? $value['__syntatis'] : $value;

		if ($value === null) {
			return;
		}

		$givenType = gettype($value);
		$matchedType = $this->hasMatchedType($value);

		if ($matchedType === false) {
			throw new TypeError('Value must be of type ' . $this->type . ', ' . $givenType . ' type given.');
		}

		if ($matchedType === null) {
			throw new TypeError('Unable to validate of type ' . $this->type . '.');
		}

		$this->validateWithConstraints($value);
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

	/** @param mixed $value */
	private function validateWithConstraints($value): void
	{
		foreach ($this->constraints as $constraint) {
			if (is_callable($constraint)) {
				$result = $constraint($value);

				if ($result === false) {
					throw new InvalidArgumentException('Value does not match the given constraints.');
				}
			}

			if (! $constraint instanceof Constraint) {
				continue;
			}

			$validators = Validator::instance()->validate($value, $constraint);

			foreach ($validators as $validator) {
				throw new InvalidArgumentException((string) $validator->getMessage());
			}
		}
	}
}
