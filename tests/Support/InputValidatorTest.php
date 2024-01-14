<?php

declare(strict_types=1);

namespace Syntatis\WP\Option\Tests\Support;

use stdClass;
use Syntatis\WP\Hook\Hook;
use Syntatis\WP\Option\Support\InputValidator;
use Syntatis\WP\Option\Tests\TestCase;
use TypeError;

class InputValidatorTest extends TestCase
{
	private Hook $hook;

	// phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
	public function set_up(): void
	{
		parent::set_up();

		$this->hook = new Hook();
	}

	/**
	 * @dataProvider dataValidateInvalidValueType
	 *
	 * @param mixed $value     The value to validate.
	 * @param mixed $givenType The type of the value to validate.
	 */
	public function testValidateInvalidValueType(string $type, $value, $givenType): void
	{
		$validator = new InputValidator($type);

		$this->expectException(TypeError::class);
		$this->expectExceptionMessage('Value must be of type ' . $type . ', ' . $givenType . ' type given.');

		$validator->validate($value);
	}

	/**
	 * @dataProvider dataValidateInvalidType
	 *
	 * @param mixed $value The value to validate.
	 */
	public function testValidateInvalidType(string $type): void
	{
		$validator = new InputValidator($type);

		$this->expectException(TypeError::class);
		$this->expectExceptionMessage('Unable to validate of type ' . $type . '.');

		$validator->validate($type, 'foo');
	}

	public function dataValidateInvalidValueType(): iterable
	{
		yield ['string', true, 'boolean'];
		yield ['string', 1, 'integer'];
		yield ['string', 1.0, 'double'];
		yield ['string', [], 'array'];
		yield ['string', new stdClass(), 'object'];
		yield ['boolean', 'foo', 'string'];
		yield ['boolean', 1, 'integer'];
		yield ['boolean', 1.0, 'double'];
		yield ['boolean', [], 'array'];
		yield ['boolean', new stdClass(), 'object'];
		yield ['integer', 'foo', 'string'];
		yield ['integer', true, 'boolean'];
		yield ['integer', 1.0, 'double'];
		yield ['integer', [], 'array'];
		yield ['integer', new stdClass(), 'object'];
		yield ['float', 'foo', 'string'];
		yield ['float', true, 'boolean'];
		yield ['float', [], 'array'];
		yield ['float', new stdClass(), 'object'];
		yield ['array', 'foo', 'string'];
		yield ['array', true, 'boolean'];
		yield ['array', 1, 'integer'];
		yield ['array', 1.0, 'double'];
		yield ['array', new stdClass(), 'object'];
	}

	public function dataValidateInvalidType(): iterable
	{
		yield ['foo'];
	}
}