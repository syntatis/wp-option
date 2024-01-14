<?php

declare(strict_types=1);

namespace Syntatis\WP\Option\Tests;

use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;
use Syntatis\WP\Hook\Hook;
use Syntatis\WP\Option\Option;
use TypeError;

use function gettype;

/** @group option */
class OptionTest extends TestCase
{
	private Hook $hook;

	private string $optionName = 'foo_bar';

	// phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
	public function set_up(): void
	{
		parent::set_up();

		$this->hook = new Hook();
	}

	// phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
	public function tear_down(): void
	{
		delete_option($this->optionName);

		parent::tear_down();
	}

	/**
	 * @dataProvider dataNoDefaultSet
	 * @testdox it should return `null` when no default is set
	 *
	 * @param mixed $default The default value to return
	 */
	public function testNoDefaultSet(string $type): void
	{
		$option = new Option($this->hook);
		$option->setSchema([$this->optionName => ['type' => $type]]);
		$option->register();

		$this->assertNull(get_option($this->optionName));
	}

	public function dataNoDefaultSet(): iterable
	{
		yield ['string'];
		yield ['boolean'];
		yield ['integer'];
		yield ['float'];
		yield ['array'];
	}

	/**
	 * @dataProvider dataDefaultSet
	 * @testdox it should return the default value when set, and coerce the value if necessary on a non-strict mode
	 *
	 * @param mixed $default The default value to return
	 * @param mixed $return  The default value returned or coerced by the function `get_site_option`.
	 */
	public function testDefaultSet(string $type, $default, $return): void
	{
		$option = new Option($this->hook);
		$option->setSchema([
			$this->optionName => [
				'type' => $type,
				'default' => $default,
			],
		]);
		$option->register();

		$this->assertSame($return, get_option($this->optionName));
	}

	/**
	 * Non-strict. Value may be coerced.
	 */
	public function dataDefaultSet(): iterable
	{
		yield ['string', 123, '123'];
		yield ['boolean', 1, true];
		yield ['boolean', '', false];
		yield ['integer', '123', 123];
		yield ['float', '1.23', 1.23];
		yield ['array', 'foo', ['foo']];
	}

	/**
	 * @dataProvider dataDefaultSetStrictValid
	 * @group strict-mode
	 * @testdox it should return the default value when set, on a strict mode
	 *
	 * @param mixed $default The default value to return
	 */
	public function testDefaultSetStrictValid(string $type, $default): void
	{
		$option = new Option($this->hook, null, 1);
		$option->setSchema([
			$this->optionName => [
				'type' => $type,
				'default' => $default,
			],
		]);
		$option->register();

		$this->assertSame($default, get_option($this->optionName));
	}

	public function dataDefaultSetStrictValid(): iterable
	{
		yield ['string', 'Hello World!'];
		yield ['boolean', true];
		yield ['boolean', false];
		yield ['integer', 123];
		yield ['float', 1.23];
		yield ['array', ['foo', 'bar']];
	}

	/**
	 * @dataProvider dataDefaultSetStrictInvalid
	 * @group strict-mode
	 * @testdox it should throw an exception when the default value is invalid, on a strict mode
	 *
	 * @param mixed $default The default value to return
	 */
	public function testDefaultSetStrictInvalid(string $type, $default): void
	{
		$option = new Option($this->hook, null, 1);
		$option->setSchema([
			$this->optionName => [
				'type' => $type,
				'default' => $default,
			],
		]);
		$option->register();

		$this->expectException(TypeError::class);

		get_option($this->optionName);
	}

	public function dataDefaultSetStrictInvalid(): iterable
	{
		yield ['string', true];
		yield ['boolean', 'true'];
		yield ['integer', ['foo']];
		yield ['float', '1.23'];
		yield ['array', false];
	}

	/**
	 * @dataProvider dataDefaultPassed
	 *
	 * @param mixed $default               The default value passed in the schema.
	 * @param mixed $defaultPassed         The default value passed in the function `get_site_option`.
	 * @param mixed $defaultPassedReturned The default value returned or coerced by the function `get_site_option`.
	 */
	public function testDefaultPassed(string $type, $default, $defaultPassed, $defaultPassedReturned): void
	{
		$option = new Option($this->hook);
		$option->setSchema([
			$this->optionName => [
				'type' => $type,
				'default' => $default,
			],
		]);
		$option->register();

		$this->assertSame($defaultPassedReturned, get_option($this->optionName, $defaultPassed));
	}

	/**
	 * Non-strict. Value may be coerced.
	 */
	public function dataDefaultPassed(): iterable
	{
		yield ['string', 'Hello World', 123, '123'];
		yield ['boolean', false, 'true', true];
		yield ['boolean', true, '', false];
		yield ['integer', 1, '2', 2];
		yield ['float', 1.2, '2.5', 2.5];
		yield ['array', ['foo'], 'bar', ['bar']];
	}

	/**
	 * @dataProvider dataDefaultPassedStrictValid
	 * @group strict-mode
	 *
	 * @param mixed $default               The default value passed in the schema.
	 * @param mixed $defaultPassed         The default value passed in the function `get_site_option`.
	 * @param mixed $defaultPassedReturned The default value returned or coerced by the function `get_site_option`.
	 */
	public function testDefaultPassedStrictValid(string $type, $default, $defaultPassed): void
	{
		$option = new Option($this->hook, null, 1);
		$option->setSchema([
			$this->optionName => [
				'type' => $type,
				'default' => $default,
			],
		]);
		$option->register();

		$this->assertSame($defaultPassed, get_option($this->optionName, $defaultPassed));
	}

	public function dataDefaultPassedStrictValid(): iterable
	{
		yield ['string', 'Hello World', '123'];
		yield ['boolean', true, null];
		yield ['integer', 1, 2];
		yield ['float', 1.2, 2.5];
		yield ['array', ['foo'], ['bar']];
	}

	/**
	 * @dataProvider dataDefaultPassedStrictInvalid
	 * @group strict-mode
	 * @testdox it should throw an exception when the default value is invalid, on a strict mode
	 *
	 * @param mixed $default               The default value passed in the schema.
	 * @param mixed $defaultPassed         The default value passed in the function `get_site_option`.
	 * @param mixed $defaultPassedReturned The default value returned or coerced by the function `get_site_option`.
	 */
	public function testDefaultPassedStrictInvalid(string $type, $default, $defaultPassed): void
	{
		$option = new Option($this->hook, null, 1);
		$option->setSchema([
			$this->optionName => [
				'type' => $type,
				'default' => $default,
			],
		]);
		$option->register();

		$this->assertSame($default, get_option($this->optionName));

		$this->expectException(TypeError::class);

		get_option($this->optionName, $defaultPassed);
	}

	public function dataDefaultPassedStrictInvalid(): iterable
	{
		yield ['string', 'Hello World', 123];
		yield ['boolean', true, '0'];
		yield ['integer', 1, '2'];
		yield ['float', 1.2, '2.5'];
		yield ['array', ['foo'], 'bar'];
	}

	/**
	 * @dataProvider dataPrefixSet
	 *
	 * @param string $type  The default value passed in the schema.
	 * @param mixed  $value The value to add with `add_site_option`.
	 */
	public function testPrefixSet(string $type, $value): void
	{
		$option = new Option($this->hook, 'syntatis_');
		$option->setSchema([$this->optionName => ['type' => $type]]);

		$this->assertFalse(has_filter('default_option_syntatis_' . $this->optionName));
		$this->assertFalse(has_filter('option_syntatis_' . $this->optionName));

		$option->register();

		$this->assertTrue(has_filter('default_option_syntatis_' . $this->optionName));
		$this->assertTrue(has_filter('option_syntatis_' . $this->optionName));

		$this->assertTrue(add_option('syntatis_' . $this->optionName, $value));
		$this->assertSame($value, get_option('syntatis_' . $this->optionName));
	}

	public function dataPrefixSet(): iterable
	{
		yield ['string', 'Hello World!'];
		yield ['boolean', true];
		yield ['integer', 1];
		yield ['float', 1.2];
		yield ['array', ['foo']];
	}

	/**
	 * @dataProvider dataGetTypeString
	 * @group type-string
	 *
	 * @param mixed $value  The value to add in the option.
	 * @param mixed $expect The expected value to be returned.
	 */
	public function testGetTypeString($value, $expect): void
	{
		add_option($this->optionName, ['__syntatis' => $value]);

		$option = new Option($this->hook);
		$option->setSchema([$this->optionName => ['type' => 'string']]);
		$option->register();

		$this->assertSame($expect, get_option($this->optionName));
	}

	/**
	 * Non-strict. Value may be coerced.
	 */
	public function dataGetTypeString(): iterable
	{
		yield ['Hello World!', 'Hello World!'];
		yield [1, '1'];
		yield [1.2, '1.2'];
		yield [true, '1'];
		yield [false, ''];

		/**
		 * For consistency with how other type handles `null` values, and how it handles default
		 * when no value is passed on the `get_option` function, a `null` value would return
		 * as a `null`.
		 */
		yield [null, null];

		/**
		 * PHP can't convert an array to a string.
		 *
		 * When converting an array to a string, it will throw an exception
		 * and value returned will fallback to a `null`.
		 */
		yield [[], null];
		yield [['foo'], null];
		yield [['foo' => 'bar'], null];
	}

	/**
	 * @dataProvider dataTypeStringStrictValid
	 * @group type-string
	 * @group strict-mode
	 *
	 * @param mixed $value  The value to add in the option.
	 * @param mixed $expect The expected value to be returned.
	 */
	public function testGetTypeStringStrictValid($value, $expect): void
	{
		add_option($this->optionName, ['__syntatis' => $value]);

		$option = new Option($this->hook, null, 1);
		$option->setSchema([$this->optionName => ['type' => 'string']]);
		$option->register();

		$this->assertSame($expect, get_option($this->optionName));
	}

	/**
	 * @dataProvider dataTypeStringStrictValid
	 * @group type-string
	 * @group strict-mode
	 *
	 * @param mixed $value  The value to add in the option.
	 * @param mixed $expect The expected value to be returned.
	 */
	public function testAddTypeStringStrictValid($value, $expect): void
	{
		$option = new Option($this->hook, null, 1);
		$option->setSchema([$this->optionName => ['type' => 'string']]);
		$option->register();

		add_option($this->optionName, $value);

		$this->assertSame($expect, get_option($this->optionName));
	}

	/**
	 * @dataProvider dataTypeStringStrictValid
	 * @group type-string
	 * @group strict-mode
	 *
	 * @param mixed $value  The value to add in the option.
	 * @param mixed $expect The expected value to be returned.
	 */
	public function testUpdateTypeStringStrictValid($value, $expect): void
	{
		add_option($this->optionName, ['__syntatis' => 'Initial value!']);

		$option = new Option($this->hook, null, 1);
		$option->setSchema([$this->optionName => ['type' => 'string']]);
		$option->register();

		update_option($this->optionName, $value);

		$this->assertSame($value, get_option($this->optionName));
	}

	public function dataTypeStringStrictValid(): iterable
	{
		yield ['Hello World!', 'Hello World!'];
		yield ['', ''];
		yield [' ', ' '];
		yield [null, null];
	}

	/**
	 * @dataProvider dataTypeStringStrictInvalid
	 * @group type-string
	 * @group strict-mode
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testGetTypeStringStrictInvalid($value): void
	{
		add_option($this->optionName, ['__syntatis' => $value]);

		$option = new Option($this->hook, null, 1);
		$option->setSchema([$this->optionName => ['type' => 'string']]);
		$option->register();

		$this->expectException(TypeError::class);

		get_option($this->optionName);
	}

	/**
	 * @dataProvider dataTypeStringStrictInvalid
	 * @group type-string
	 * @group strict-mode
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testAddTypeStringStrictInvalid($value): void
	{
		$option = new Option($this->hook, null, 1);
		$option->setSchema([$this->optionName => ['type' => 'string']]);
		$option->register();

		$this->expectException(TypeError::class);
		$this->expectExceptionMessage('Value must be of type string, ' . gettype($value) . ' type given.');

		add_option($this->optionName, $value);
	}

	/**
	 * @dataProvider dataTypeStringStrictInvalid
	 * @group type-string
	 * @group strict-mode
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testUpdateTypeStringStrictInvalid($value): void
	{
		add_option($this->optionName, ['__syntatis' => 'Initial value!']);

		$option = new Option($this->hook, null, 1);
		$option->setSchema([$this->optionName => ['type' => 'string']]);
		$option->register();

		$this->expectException(TypeError::class);
		$this->expectExceptionMessage('Value must be of type string, ' . gettype($value) . ' type given.');

		update_option($this->optionName, $value);
	}

	public function dataTypeStringStrictInvalid(): iterable
	{
		yield [1];
		yield [1.2];
		yield [false];
		yield [true];
		yield [[]];
	}

	/**
	 * @dataProvider dataTypeBoolean
	 * @group type-boolean
	 *
	 * @param mixed $value  The value to add in the option.
	 * @param mixed $expect The expected value to be returned.
	 */
	public function testGetTypeBoolean($value, $expect): void
	{
		add_option($this->optionName, ['__syntatis' => $value]);

		$option = new Option($this->hook);
		$option->setSchema([$this->optionName => ['type' => 'boolean']]);
		$option->register();

		$this->assertSame($expect, get_option($this->optionName));
	}

	/**
	 * Non-strict. Value may be coerced.
	 */
	public function dataTypeBoolean(): iterable
	{
		yield ['Hello world!', true];
		yield ['', false];
		yield [0, false];
		yield [1, true];
		yield [1.2, true];
		yield [false, false];
		yield [true, true];
		yield [[], false];

		/**
		 * -1 is considered true, like any other non-zero (whether negative or positive) number!
		 *
		 * @see https://www.php.net/manual/en/language.types.boolean.php
		 */
		yield [-1, true];

		/**
		 * A `null` value would return as a `null`.
		 */
		yield [null, null];
	}

	/**
	 * @dataProvider dataTypeBooleanStrictValid
	 * @group type-boolean
	 * @group strict-mode
	 *
	 * @param mixed $value  The value to add in the option.
	 * @param mixed $expect The expected value to be returned.
	 */
	public function testGetTypeBooleanStrictValid($value, $expect): void
	{
		add_option($this->optionName, ['__syntatis' => $value]);

		$option = new Option($this->hook, null, 1);
		$option->setSchema([$this->optionName => ['type' => 'boolean']]);
		$option->register();

		$this->assertSame($expect, get_option($this->optionName));
	}

	/**
	 * @dataProvider dataTypeBooleanStrictValid
	 * @group type-boolean
	 * @group strict-mode
	 *
	 * @param mixed $value  The value to add in the option.
	 * @param mixed $expect The expected value to be returned.
	 */
	public function testAddTypeBooleanStrictValid($value, $expect): void
	{
		$option = new Option($this->hook, null, 1);
		$option->setSchema([$this->optionName => ['type' => 'boolean']]);
		$option->register();

		add_option($this->optionName, $value);

		$this->assertSame($expect, get_option($this->optionName));
	}

	/**
	 * @dataProvider dataTypeBooleanStrictValid
	 * @group type-boolean
	 * @group strict-mode
	 *
	 * @param mixed $value  The value to add in the option.
	 * @param mixed $expect The expected value to be returned.
	 */
	public function testUpdateTypeBooleanStrictValid($value, $expect): void
	{
		add_option($this->optionName, ['__syntatis' => true]);

		$option = new Option($this->hook, null, 1);
		$option->setSchema([$this->optionName => ['type' => 'boolean']]);
		$option->register();

		update_option($this->optionName, $value);

		$this->assertSame($expect, get_option($this->optionName));
	}

	public function dataTypeBooleanStrictValid(): iterable
	{
		yield [true, true];
		yield [false, false];
		yield [null, null];
	}

	/**
	 * @dataProvider dataTypeBooleanStrictInvalid
	 * @group type-boolean
	 * @group strict-mode
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testGetTypeBooleanStrictInvalid($value): void
	{
		add_option($this->optionName, ['__syntatis' => $value]);

		$option = new Option($this->hook, null, 1);
		$option->setSchema([$this->optionName => ['type' => 'boolean']]);
		$option->register();

		$this->expectException(TypeError::class);

		get_option($this->optionName);
	}

	/**
	 * @dataProvider dataTypeBooleanStrictInvalid
	 * @group type-boolean
	 * @group strict-mode
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testAddTypeBooleanStrictInvalid($value): void
	{
		$option = new Option($this->hook, null, 1);
		$option->setSchema([$this->optionName => ['type' => 'boolean']]);
		$option->register();

		$this->expectException(TypeError::class);
		$this->expectExceptionMessage('Value must be of type boolean, ' . gettype($value) . ' type given.');

		add_option($this->optionName, $value);
	}

	/**
	 * @dataProvider dataTypeBooleanStrictInvalid
	 * @group type-boolean
	 * @group strict-mode
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testUpdateTypeBooleanStrictInvalid($value): void
	{
		add_option($this->optionName, ['__syntatis' => true]);

		$option = new Option($this->hook, null, 1);
		$option->setSchema([$this->optionName => ['type' => 'boolean']]);
		$option->register();

		$this->expectException(TypeError::class);
		$this->expectExceptionMessage('Value must be of type boolean, ' . gettype($value) . ' type given.');

		update_option($this->optionName, $value);
	}

	public function dataTypeBooleanStrictInvalid(): iterable
	{
		yield ['Hello world!'];
		yield [''];
		yield [' '];
		yield [0];
		yield [1];
		yield [1.2];
		yield [-1];
		yield [[]];
		yield [['foo']];
		yield ['false'];
		yield ['true'];
	}

	/**
	 * @dataProvider dataTypeInteger
	 * @group type-integer
	 *
	 * @param mixed $value  The value to add in the option.
	 * @param mixed $expect The expected value to be returned.
	 */
	public function testGetTypeInteger($value, $expect): void
	{
		add_option($this->optionName, ['__syntatis' => $value]);

		$option = new Option($this->hook);
		$option->setSchema([$this->optionName => ['type' => 'integer']]);
		$option->register();

		$this->assertSame($expect, get_option($this->optionName));
	}

	/**
	 * Non-strict. Value may be coerced.
	 */
	public function dataTypeInteger(): iterable
	{
		yield ['Hello world!', 0];
		yield ['', 0];
		yield [0, 0];
		yield [1, 1];
		yield [1.2, 1];
		yield [1.23, 1];
		yield [-1, -1];
		yield [false, 0];
		yield [true, 1];

		/**
		 * The behaviour of converting to int is undefined for other types.
		 * Do not rely on any observed behaviour, as it can change without
		 * notice. Similar to how it handles the string type, an array
		 * would return as a `null`.
		 *
		 * @see https://www.php.net/manual/en/language.types.integer.php
		 */
		yield [[], null];
		yield [['foo'], null];
		yield [['foo' => 'bar'], null];

		/**
		 * PHP internally would cast a `null` to `0`, but for consistency
		 * with the other types, and how it handles default when no value
		 * is passed on the `get_option` function, a `null` value would
		 * return as a `null`.
		 */
		yield [null, null];
	}

	/**
	 * @dataProvider dataTypeIntegerStrictValid
	 * @group type-integer
	 * @group strict-mode
	 *
	 * @param mixed $value  The value to add in the option.
	 * @param mixed $expect The expected value to be returned.
	 */
	public function testGetTypeIntegerStrictValid($value, $expect): void
	{
		add_option($this->optionName, ['__syntatis' => $value]);

		$option = new Option($this->hook, null, 1);
		$option->setSchema([$this->optionName => ['type' => 'integer']]);
		$option->register();

		$this->assertSame($expect, get_option($this->optionName));
	}

	/**
	 * @dataProvider dataTypeIntegerStrictValid
	 * @group type-integer
	 * @group strict-mode
	 *
	 * @param mixed $value  The value to add in the option.
	 * @param mixed $expect The expected value to be returned.
	 */
	public function testAddTypeIntegerStrictValid($value, $expect): void
	{
		$option = new Option($this->hook, null, 1);
		$option->setSchema([$this->optionName => ['type' => 'integer']]);
		$option->register();

		add_option($this->optionName, $value);

		$this->assertSame($value, get_option($this->optionName));
	}

	/**
	 * @dataProvider dataTypeIntegerStrictValid
	 * @group type-integer
	 * @group strict-mode
	 *
	 * @param mixed $value  The value to add in the option.
	 * @param mixed $expect The expected value to be returned.
	 */
	public function testUpdateTypeIntegerStrictValid($value, $expect): void
	{
		add_option($this->optionName, ['__syntatis' => 1]);

		$option = new Option($this->hook, null, 1);
		$option->setSchema([$this->optionName => ['type' => 'integer']]);
		$option->register();

		update_option($this->optionName, $value);

		$this->assertSame($value, get_option($this->optionName));
	}

	public function dataTypeIntegerStrictValid(): iterable
	{
		yield [1, 1]; // Positive
		yield [-1, -1]; // Negative
		yield [0123, 0123]; // Octal
		yield [0x1A, 0x1A]; // Hexadecimal
		yield [0b11111111, 0b11111111]; // Binary
		yield [1_234_567, 1_234_567];
		yield [null, null];
	}

	/**
	 * @dataProvider dataTypeIntegerStrictInvalid
	 * @group type-integer
	 * @group strict-mode
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testGetTypeIntegerStrictInvalid($value): void
	{
		add_option($this->optionName, ['__syntatis' => $value]);

		$option = new Option($this->hook, null, 1);
		$option->setSchema([$this->optionName => ['type' => 'integer']]);
		$option->register();

		$this->expectException(TypeError::class);

		get_option($this->optionName);
	}

	/**
	 * @dataProvider dataTypeIntegerStrictInvalid
	 * @group type-integer
	 * @group strict-mode
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testAddTypeIntegerStrictInvalid($value): void
	{
		$option = new Option($this->hook, null, 1);
		$option->setSchema([$this->optionName => ['type' => 'integer']]);
		$option->register();

		$this->expectException(TypeError::class);
		$this->expectExceptionMessage('Value must be of type integer, ' . gettype($value) . ' type given.');

		add_option($this->optionName, $value);
	}

	/**
	 * @dataProvider dataTypeIntegerStrictInvalid
	 * @group type-integer
	 * @group strict-mode
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testUpdateTypeIntegerStrictInvalid($value): void
	{
		add_option($this->optionName, ['__syntatis' => 1]);

		$option = new Option($this->hook, null, 1);
		$option->setSchema([$this->optionName => ['type' => 'integer']]);
		$option->register();

		$this->expectException(TypeError::class);
		$this->expectExceptionMessage('Value must be of type integer, ' . gettype($value) . ' type given.');

		update_option($this->optionName, $value);
	}

	public function dataTypeIntegerStrictInvalid(): iterable
	{
		yield ['Hello world!'];
		yield [''];
		yield [1.2];
		yield [false];
		yield [true];
		yield [[]];
	}

	/**
	 * @dataProvider dataTypeFloat
	 * @group type-float
	 *
	 * @param mixed $value  The value to add in the option.
	 * @param mixed $expect The value to be returned.
	 */
	public function testGetTypeFloat($value, $expect): void
	{
		add_option($this->optionName, ['__syntatis' => $value]);

		$option = new Option($this->hook);
		$option->setSchema([$this->optionName => ['type' => 'float']]);
		$option->register();

		$this->assertSame($expect, get_option($this->optionName));
	}

	/**
	 * Non-strict. Value may be coerced.
	 */
	public function dataTypeFloat(): iterable
	{
		yield ['Hello world!', 0.0];
		yield ['', 0.0];
		yield [0, 0.0];
		yield [1, 1.0];
		yield [1.2, 1.2];
		yield [-1, -1.0];
		yield [false, 0.0];
		yield [true, 1.0];

		/**
		 * As certain types have undefined behavior when converting to `int`,
		 * this is also the case when converting to float.
		 */
		yield [[], null];
		yield [['foo'], null];
		yield [['foo' => 'bar'], null];

		yield [null, null];
	}

	/**
	 * @dataProvider dataTypeFloatStrictValid
	 * @group type-float
	 * @group strict-mode
	 *
	 * @param mixed $value  The value to add in the option.
	 * @param mixed $expect The value to be returned.
	 */
	public function testGetTypeFloatStrictValid($value, $expect): void
	{
		add_option($this->optionName, ['__syntatis' => $value]);

		$option = new Option($this->hook, null, 1);
		$option->setSchema([$this->optionName => ['type' => 'float']]);
		$option->register();

		$this->assertSame($expect, get_option($this->optionName));
	}

	/**
	 * @dataProvider dataTypeFloatStrictValid
	 * @group type-float
	 * @group strict-mode
	 *
	 * @param mixed $value  The value to add in the option.
	 * @param mixed $expect The value to be returned.
	 */
	public function testAddTypeFloatStrictValid($value, $expect): void
	{
		$option = new Option($this->hook, null, 1);
		$option->setSchema([$this->optionName => ['type' => 'float']]);
		$option->register();

		add_option($this->optionName, $value);

		$this->assertSame($expect, get_option($this->optionName));
	}

	/**
	 * @dataProvider dataTypeFloatStrictValid
	 * @group type-float
	 * @group strict-mode
	 *
	 * @param mixed $value  The value to add in the option.
	 * @param mixed $expect The value to be returned.
	 */
	public function testUpdateTypeFloatStrictValid($value, $expect): void
	{
		add_option($this->optionName, ['__syntatis' => 100.12]);

		$option = new Option($this->hook, null, 1);
		$option->setSchema([$this->optionName => ['type' => 'float']]);
		$option->register();

		update_option($this->optionName, $value);

		$this->assertSame($expect, get_option($this->optionName));
	}

	public function dataTypeFloatStrictValid(): iterable
	{
		yield [1.2, 1.2]; // Positive
		yield [-1.2, -1.2]; // Negative
		yield [1.2e3, 1.2e3]; // Scientific notation
		yield [7E-10, 7E-10]; // Scientific notation
		yield [1_234_567.89, 1_234_567.89];

		/**
		 * This exception occurs even in the `strict_mode`, where an integer is coerced into a float.
		 * This behavior is based on the assumption that integers can be safely converted to floats
		 * without any loss of precision or functionality.
		 */
		yield [1, 1.0];
		yield [-1, -1.0];
		yield [0, 0.0];

		yield [null, null];
	}

	/**
	 * @dataProvider dataTypeFloatStrictInvalid
	 * @group type-float
	 * @group strict-mode
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testGetTypeFloatStrictInvalid($value): void
	{
		add_option($this->optionName, ['__syntatis' => $value]);

		$option = new Option($this->hook, null, 1);
		$option->setSchema([$this->optionName => ['type' => 'float']]);
		$option->register();

		$this->expectException(TypeError::class);

		get_option($this->optionName);
	}

	/**
	 * @dataProvider dataTypeFloatStrictInvalid
	 * @group type-float
	 * @group strict-mode
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testAddTypeFloatStrictInvalid($value): void
	{
		$option = new Option($this->hook, null, 1);
		$option->setSchema([$this->optionName => ['type' => 'float']]);
		$option->register();

		$this->expectException(TypeError::class);
		$this->expectExceptionMessage('Value must be of type float, ' . gettype($value) . ' type given.');

		add_option($this->optionName, $value);
	}

	/**
	 * @dataProvider dataTypeFloatStrictInvalid
	 * @group type-float
	 * @group strict-mode
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testUpdateTypeFloatStrictInvalid($value): void
	{
		add_option($this->optionName, ['__syntatis' => 1.23]);

		$option = new Option($this->hook, null, 1);
		$option->setSchema([$this->optionName => ['type' => 'float']]);
		$option->register();

		$this->expectException(TypeError::class);
		$this->expectExceptionMessage('Value must be of type float, ' . gettype($value) . ' type given.');

		update_option($this->optionName, $value);
	}

	public function dataTypeFloatStrictInvalid(): iterable
	{
		yield ['Hello world!'];
		yield [''];
		yield [false];
		yield [true];
		yield [[]];
	}

	/**
	 * @dataProvider dataTypeArray
	 * @group type-array
	 *
	 * @param mixed $value  The value to add in the option.
	 * @param mixed $expect The expected value to be returned.
	 */
	public function testGetTypeArray($value, $expect): void
	{
		add_option($this->optionName, ['__syntatis' => $value]);

		$option = new Option($this->hook);
		$option->setSchema([$this->optionName => ['type' => 'array']]);
		$option->register();

		$this->assertSame($expect, get_option($this->optionName));
	}

	/**
	 * Non-strict. Value may be coerced.
	 */
	public function dataTypeArray(): iterable
	{
		yield ['Hello world!', ['Hello world!']];
		yield ['', ['']];
		yield [0, [0]];
		yield [1, [1]];
		yield [1.2, [1.2]];
		yield [-1, [-1]];
		yield [false, [false]];
		yield [true, [true]];
		yield [[], []];
		yield [['foo', 'bar'], ['foo', 'bar']];
		yield [['foo' => 'bar'], ['foo' => 'bar']];

		yield [null, null];
	}

	/**
	 * @dataProvider dataTypeArrayStrictValid
	 * @group type-array
	 * @group strict-mode
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testGetTypeArrayStrictValid($value): void
	{
		add_option($this->optionName, ['__syntatis' => $value]);

		$option = new Option($this->hook, null, 1);
		$option->setSchema([$this->optionName => ['type' => 'array']]);
		$option->register();

		$this->assertSame($value, get_option($this->optionName));
	}

	/**
	 * @dataProvider dataTypeArrayStrictValid
	 * @group type-array
	 * @group strict-mode
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testAddTypeArrayStrictValid($value): void
	{
		$option = new Option($this->hook, null, 1);
		$option->setSchema([$this->optionName => ['type' => 'array']]);
		$option->register();

		add_option($this->optionName, $value);

		$this->assertSame($value, get_option($this->optionName));
	}

	/**
	 * @dataProvider dataTypeArrayStrictValid
	 * @group type-array
	 * @group strict-mode
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testUpdateTypeArrayStrictValid($value): void
	{
		add_option($this->optionName, ['__syntatis' => ['foo']]);

		$option = new Option($this->hook, null, 1);
		$option->setSchema([$this->optionName => ['type' => 'array']]);
		$option->register();

		update_option($this->optionName, $value);

		$this->assertSame($value, get_option($this->optionName));
	}

	public function dataTypeArrayStrictValid(): iterable
	{
		yield [[], []];
		yield [['foo'], ['foo']];
		yield [['foo' => 'bar'], ['foo' => 'bar']];
		yield [null, null];
	}

	/**
	 * @dataProvider dataTypeArrayStrictInvalid
	 * @group type-array
	 * @group strict-mode
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testGetTypeArrayStrictInvalid($value): void
	{
		add_option($this->optionName, ['__syntatis' => $value]);

		$option = new Option($this->hook, null, 1);
		$option->setSchema([$this->optionName => ['type' => 'array']]);
		$option->register();

		$this->expectException(TypeError::class);

		get_option($this->optionName);
	}

	/**
	 * @dataProvider dataTypeArrayStrictInvalid
	 * @group type-array
	 * @group strict-mode
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testAddTypeArrayStrictInvalid($value): void
	{
		$option = new Option($this->hook, null, 1);
		$option->setSchema([$this->optionName => ['type' => 'array']]);
		$option->register();

		$this->expectException(TypeError::class);
		$this->expectExceptionMessage('Value must be of type array, ' . gettype($value) . ' type given.');

		add_option($this->optionName, $value);
	}

	/**
	 * @dataProvider dataTypeArrayStrictInvalid
	 * @group type-array
	 * @group strict-mode
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testUpdateTypeArrayStrictInvalid($value): void
	{
		add_option($this->optionName, ['__syntatis' => ['foo']]);

		$option = new Option($this->hook, null, 1);
		$option->setSchema([$this->optionName => ['type' => 'array']]);
		$option->register();

		$this->expectException(TypeError::class);
		$this->expectExceptionMessage('Value must be of type array, ' . gettype($value) . ' type given.');

		update_option($this->optionName, $value);
	}

	public function dataTypeArrayStrictInvalid(): iterable
	{
		yield ['Hello world!'];
		yield [''];
		yield [0];
		yield [1];
		yield [1.2];
		yield [-1];
		yield [false];
		yield [true];
	}

	/**
	 * @dataProvider dataConstraintsCallback
	 * @group strict-mode
	 * @group test-here-1
	 *
	 * @param mixed $constraints  The constraints to be passed in the schema.
	 * @param mixed $value        The value to add in the option.
	 * @param mixed $errorMessage The expected error message.
	 */
	public function testConstraintsCallback($constraints, $value, $errorMessage): void
	{
		$option = new Option($this->hook, null, 1);
		$option->setSchema([
			$this->optionName => [
				'type' => 'string',
				'constraints' => $constraints,
			],
		]);
		$option->register();

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage($errorMessage);

		add_option($this->optionName, $value);
	}

	public function dataConstraintsCallback(): iterable
	{
		yield ['\Syntatis\Utils\is_email', 'Hello', 'Value does not match the given constraints.'];
		yield [new Assert\Email(null, 'The email "Hello" is not a valid email.'), 'Hello', 'The email "Hello" is not a valid email.'];
	}
}
