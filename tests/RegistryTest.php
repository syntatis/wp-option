<?php

declare(strict_types=1);

namespace Syntatis\WP\Option\Tests;

use Syntatis\WP\Hook\Hook;
use Syntatis\WP\Option\Exceptions\TypeError;
use Syntatis\WP\Option\Option;
use Syntatis\WP\Option\Registry;
use Syntatis\WP\Option\Support\InputSanitizer;

/** @group option */
class RegistryTest extends TestCase
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
	 * @param array<Option> $option
	 */
	public function testNoDefaultSet(array $option): void
	{
		$registry = new Registry($option);
		$registry->hook($this->hook);
		$registry->register();
		$this->hook->run();

		$this->assertNull(get_option($this->optionName));
	}

	public function dataNoDefaultSet(): iterable
	{
		yield [[new Option($this->optionName, 'string')]];
		yield [[new Option($this->optionName, 'boolean')]];
		yield [[new Option($this->optionName, 'integer')]];
		yield [[new Option($this->optionName, 'number')]];
		yield [[new Option($this->optionName, 'array')]];
	}

	/**
	 * @dataProvider dataDefaultSet
	 * @testdox it should return the default value when set, and coerce the value if necessary on a non-strict mode
	 *
	 * @param array<Option> $option
	 * @param mixed         $return The expected returned value from `get_option`.
	 */
	public function testDefaultSet(array $option, $return): void
	{
		$registry = new Registry($option);
		$registry->hook($this->hook);
		$registry->register();
		$this->hook->run();

		$this->assertSame($return, get_option($this->optionName));
	}

	/**
	 * Non-strict. Value may be coerced.
	 */
	public function dataDefaultSet(): iterable
	{
		yield [[(new Option($this->optionName, 'string'))->setDefault(123)], '123'];
		yield [[(new Option($this->optionName, 'boolean'))->setDefault('')], false];
		yield [[(new Option($this->optionName, 'boolean'))->setDefault(1)], true];
		yield [[(new Option($this->optionName, 'integer'))->setDefault('123')], 123];
		yield [[(new Option($this->optionName, 'array'))->setDefault('foo')], ['foo']];
		yield [[(new Option($this->optionName, 'array'))->setDefault(['foo' => 'bar'])], ['foo' => 'bar']];
		yield [[(new Option($this->optionName, 'number'))->setDefault('12.3')], 12.3];
		yield [[(new Option($this->optionName, 'number'))->setDefault('123')], 123];

		// The `null` value should be defaulted to `null`.
		yield [[(new Option($this->optionName, 'string'))->setDefault(null)], null];
		yield [[(new Option($this->optionName, 'number'))->setDefault(null)], null];
		yield [[(new Option($this->optionName, 'boolean'))->setDefault(null)], null];
		yield [[(new Option($this->optionName, 'integer'))->setDefault(null)], null];
		yield [[(new Option($this->optionName, 'array'))->setDefault(null)], null];
	}

	/**
	 * @dataProvider dataDefaultSetStrictValid
	 * @group strict-mode
	 * @testdox it should return the default value when set, on a strict mode
	 *
	 * @param array<Option> $option
	 * @param mixed         $return The expected returned value from `get_option`.
	 */
	public function testDefaultSetStrictValid(array $option, $return): void
	{
		$registry = new Registry($option, 1);
		$registry->hook($this->hook);
		$registry->register();
		$this->hook->run();

		$this->assertSame($return, get_option($this->optionName));
	}

	public function dataDefaultSetStrictValid(): iterable
	{
		yield [[(new Option($this->optionName, 'string'))->setDefault('Hello World!')], 'Hello World!'];
		yield [[(new Option($this->optionName, 'boolean'))->setDefault(true)], true];
		yield [[(new Option($this->optionName, 'boolean'))->setDefault(false)], false];
		yield [[(new Option($this->optionName, 'integer'))->setDefault(1)], 1];
		yield [[(new Option($this->optionName, 'integer'))->setDefault(-1)], -1];
		yield [[(new Option($this->optionName, 'number'))->setDefault(1)], 1];
		yield [[(new Option($this->optionName, 'number'))->setDefault(1.1)], 1.1];
		yield [[(new Option($this->optionName, 'array'))->setDefault([1])], [1]];
		yield [[(new Option($this->optionName, 'array'))->setDefault(['foo' => 'bar'])], ['foo' => 'bar']];

		// The `null` value should be defaulted to `null`.
		yield [[(new Option($this->optionName, 'string'))->setDefault(null)], null];
		yield [[(new Option($this->optionName, 'number'))->setDefault(null)], null];
		yield [[(new Option($this->optionName, 'boolean'))->setDefault(null)], null];
		yield [[(new Option($this->optionName, 'integer'))->setDefault(null)], null];
		yield [[(new Option($this->optionName, 'array'))->setDefault(null)], null];
	}

	/**
	 * @dataProvider dataDefaultSetStrictInvalid
	 * @group strict-mode
	 * @testdox it should throw an exception when the default value is invalid, on a strict mode
	 *
	 * @param array<Option> $option
	 * @param string        $message The error message to expect.
	 */
	public function testDefaultSetStrictInvalid(array $option, string $message): void
	{
		$registry = new Registry($option, 1);
		$registry->hook($this->hook);
		$registry->register();
		$this->hook->run();

		$this->expectException(TypeError::class);
		$this->expectExceptionMessage($message);

		get_option($this->optionName);
	}

	public function dataDefaultSetStrictInvalid(): iterable
	{
		yield [[(new Option($this->optionName, 'string'))->setDefault(true)], 'Value must be of type string, boolean given.'];
		yield [[(new Option($this->optionName, 'boolean'))->setDefault('true')], 'Value must be of type boolean, string given.'];
		yield [[(new Option($this->optionName, 'boolean'))->setDefault(0)], 'Value must be of type boolean, integer given.'];
		yield [[(new Option($this->optionName, 'integer'))->setDefault(1.1)], 'Value must be of type integer, number (float) given.'];
		yield [[(new Option($this->optionName, 'integer'))->setDefault('-1')], 'Value must be of type integer, string given.'];
		yield [[(new Option($this->optionName, 'number'))->setDefault([1])], 'Value must be of type number, array given.'];
		yield [[(new Option($this->optionName, 'number'))->setDefault(false)], 'Value must be of type number, boolean given.'];
		yield [[(new Option($this->optionName, 'array'))->setDefault('foo')], 'Value must be of type array, string given.'];
	}

	/**
	 * @dataProvider dataDefaultPassed
	 *
	 * @param array<Option> $option
	 * @param mixed         $defaultPassed The default value passed in the function `get_option`.
	 * @param mixed         $coerced       The default value returned or coerced by the function `get_option`.
	 */
	public function testDefaultPassed(array $option, $defaultPassed, $coerced): void
	{
		$registry = new Registry($option);
		$registry->hook($this->hook);
		$registry->register();
		$this->hook->run();

		$this->assertSame($coerced, get_option($this->optionName, $defaultPassed));
	}

	/**
	 * Non-strict. Value may be coerced.
	 */
	public function dataDefaultPassed(): iterable
	{
		yield [[(new Option($this->optionName, 'string'))->setDefault('Hello World')], 123, '123'];
		yield [[(new Option($this->optionName, 'boolean'))->setDefault(false)], 'true', true];
		yield [[(new Option($this->optionName, 'boolean'))->setDefault(true)], '', false];
		yield [[(new Option($this->optionName, 'integer'))->setDefault(1)], '2', 2];
		yield [[(new Option($this->optionName, 'number'))->setDefault(1.2)], '2.5', 2.5];
		yield [[(new Option($this->optionName, 'number'))->setDefault(1)], '2', 2];
		yield [[(new Option($this->optionName, 'array'))->setDefault(['foo'])], 'bar', ['bar']];
	}

	/**
	 * @dataProvider dataDefaultPassedStrictValid
	 * @group strict-mode
	 *
	 * @param array<Option> $option
	 * @param mixed         $defaultPassed The default value passed in the function `get_option`.
	 */
	public function testDefaultPassedStrictValid(array $option, $defaultPassed): void
	{
		$registry = new Registry($option, 1);
		$registry->hook($this->hook);
		$registry->register();
		$this->hook->run();

		$this->assertSame($defaultPassed, get_option($this->optionName, $defaultPassed));
	}

	public function dataDefaultPassedStrictValid(): iterable
	{
		yield [[(new Option($this->optionName, 'string'))->setDefault('Foo Bar')], null];
		yield [[(new Option($this->optionName, 'string'))->setDefault('Hello World')], '123'];
		yield [[(new Option($this->optionName, 'boolean'))->setDefault(true)], null];
		yield [[(new Option($this->optionName, 'boolean'))->setDefault(true)], false];
		yield [[(new Option($this->optionName, 'integer'))->setDefault(1)], null];
		yield [[(new Option($this->optionName, 'integer'))->setDefault(1)], 2];
		yield [[(new Option($this->optionName, 'number'))->setDefault(1)], null];
		yield [[(new Option($this->optionName, 'number'))->setDefault(1)], 2];
		yield [[(new Option($this->optionName, 'number'))->setDefault(1.2)], null];
		yield [[(new Option($this->optionName, 'number'))->setDefault(1.2)], 2.3];
		yield [[(new Option($this->optionName, 'array'))->setDefault(['foo'])], null];
		yield [[(new Option($this->optionName, 'array'))->setDefault(['foo'])], ['foo']];
		yield [[(new Option($this->optionName, 'array'))->setDefault(['foo' => 'bar'])], null];
		yield [[(new Option($this->optionName, 'array'))->setDefault(['foo' => 'bar'])], ['foo' => 'bar']];
	}

	/**
	 * @dataProvider dataDefaultPassedStrictInvalid
	 * @group strict-mode
	 * @testdox it should throw an exception when the default passed is invalid, on a strict mode
	 *
	 * @param array<Option> $option
	 * @param mixed         $defaultPassed The default value passed in the function `get_option`.
	 * @param string        $errorMessage  The expected error message thrown with the `TypeError`.
	 */
	public function testDefaultPassedStrictInvalid(array $option, $defaultPassed, string $errorMessage): void
	{
		$registry = new Registry($option, 1);
		$registry->hook($this->hook);
		$registry->register();
		$this->hook->run();

		$this->expectException(TypeError::class);
		$this->expectExceptionMessage($errorMessage);

		get_option($this->optionName, $defaultPassed);
	}

	public function dataDefaultPassedStrictInvalid(): iterable
	{
		yield [[(new Option($this->optionName, 'string'))->setDefault('Hello World')], 123, 'Value must be of type string, integer given.'];
		yield [[(new Option($this->optionName, 'boolean'))->setDefault(true)], '0', 'Value must be of type boolean, string given.'];
		yield [[(new Option($this->optionName, 'integer'))->setDefault(1)], '2', 'Value must be of type integer, string given.'];
		yield [[(new Option($this->optionName, 'integer'))->setDefault(1)], 1.2, 'Value must be of type integer, number (float) given.'];
		yield [[(new Option($this->optionName, 'number'))->setDefault(1)], [], 'Value must be of type number, array given.'];
		yield [[(new Option($this->optionName, 'array'))->setDefault([1])], 1, 'Value must be of type array, integer given.'];
		yield [[(new Option($this->optionName, 'array'))->setDefault(['foo' => 'bar'])], 'foo->bar', 'Value must be of type array, string given.'];
	}

	/**
	 * @dataProvider dataPrefixSet
	 *
	 * @param array<Option> $option
	 * @param mixed         $value  The value to add with `add_option` and one retrieved with `get_option`.
	 */
	public function testPrefixSet(array $option, $value): void
	{
		$registry = new Registry($option);
		$registry->hook($this->hook);
		$registry->setPrefix('syntatis_');

		$optionName = 'syntatis_' . $this->optionName;

		$this->assertFalse(has_filter('default_option_syntatis_' . $this->optionName));
		$this->assertFalse(has_filter('option_syntatis_' . $this->optionName));

		$registry->register();
		$this->hook->run();

		$this->assertTrue(has_filter('default_option_syntatis_' . $this->optionName));
		$this->assertTrue(has_filter('option_syntatis_' . $this->optionName));

		$this->assertTrue(add_option($optionName, $value));
		$this->assertSame($value, get_option($optionName));
	}

	public function dataPrefixSet(): iterable
	{
		yield [[new Option($this->optionName, 'string')], 'Hello World!'];
		yield [[new Option($this->optionName, 'boolean')], true];
		yield [[new Option($this->optionName, 'integer')], 1];
		yield [[new Option($this->optionName, 'number')], 2];
		yield [[new Option($this->optionName, 'number')], 1.2];
		yield [[new Option($this->optionName, 'array')], ['foo']];
		yield [[new Option($this->optionName, 'array')], ['foo' => 'bar']];
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
		/**
		 * Assumes that the option is already added with a value since the test only
		 * concerns about the value retrieved with the `get_option` function.
		 */
		add_option(
			$this->optionName,
			(new InputSanitizer())->sanitize($value),
		);

		$registry = new Registry([new Option($this->optionName, 'string')]);
		$registry->hook($this->hook);
		$registry->register();
		$this->hook->run();

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
	 * @param mixed $value The value to add in the option.
	 */
	public function testGetTypeStringStrictValid($value): void
	{
		/**
		 * Assumes that the option is already added with a value since the test only
		 * concerns about the value retrieved with the `get_option` function.
		 */
		add_option(
			$this->optionName,
			(new InputSanitizer())->sanitize($value),
		);

		$registry = new Registry([new Option($this->optionName, 'string')], 1);
		$registry->hook($this->hook);
		$registry->register();
		$this->hook->run();

		$this->assertSame($value, get_option($this->optionName));
	}

	/**
	 * @dataProvider dataTypeStringStrictValid
	 * @group type-string
	 * @group strict-mode
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testAddTypeStringStrictValid($value): void
	{
		$registry = new Registry([new Option($this->optionName, 'string')], 1);
		$registry->hook($this->hook);
		$registry->register();
		$this->hook->run();

		add_option($this->optionName, $value);

		$this->assertSame($value, get_option($this->optionName));
	}

	/**
	 * @dataProvider dataTypeStringStrictValid
	 * @group type-string
	 * @group strict-mode
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testUpdateTypeStringStrictValid($value): void
	{
		/**
		 * Assumes that the option is already added with a value since the test only
		 * concerns about the value updated with the `update_option` function and
		 * aone retrieved with the `get_option` function.
		 */
		add_option(
			$this->optionName,
			(new InputSanitizer())->sanitize($value),
		);

		$registry = new Registry([new Option($this->optionName, 'string')], 1);
		$registry->hook($this->hook);
		$registry->register();
		$this->hook->run();

		update_option($this->optionName, $value);

		$this->assertSame($value, get_option($this->optionName));
	}

	public function dataTypeStringStrictValid(): iterable
	{
		yield ['Hello World!'];
		yield [''];
		yield [' '];
		yield [null];
	}

	/**
	 * @dataProvider dataTypeStringStrictInvalid
	 * @group type-string
	 * @group strict-mode
	 *
	 * @param mixed  $value        The value to add in the option.
	 * @param string $errorMessage The expectd error message thrown with the `TypeError`.
	 */
	public function testGetTypeStringStrictInvalid($value, string $errorMessage): void
	{
		/**
		 * Assumes that the option is already added with a value since the test only
		 * concerns about the value retrieved with the `get_option` function.
		 */
		add_option(
			$this->optionName,
			(new InputSanitizer())->sanitize($value),
		);

		$registry = new Registry([new Option($this->optionName, 'string')], 1);
		$registry->hook($this->hook);
		$registry->register();
		$this->hook->run();

		$this->expectException(TypeError::class);
		$this->expectExceptionMessage($errorMessage);

		get_option($this->optionName);
	}

	/**
	 * @dataProvider dataTypeStringStrictInvalid
	 * @group type-string
	 * @group strict-mode
	 *
	 * @param mixed  $value        The value to add in the option.
	 * @param string $errorMessage The expectd error message thrown with the `TypeError`.
	 */
	public function testAddTypeStringStrictInvalid($value, string $errorMessage): void
	{
		$registry = new Registry([new Option($this->optionName, 'string')], 1);
		$registry->hook($this->hook);
		$registry->register();
		$this->hook->run();

		$this->expectException(TypeError::class);
		$this->expectExceptionMessage($errorMessage);

		add_option($this->optionName, $value);
	}

	/**
	 * @dataProvider dataTypeStringStrictInvalid
	 * @group type-string
	 * @group strict-mode
	 *
	 * @param mixed  $value        The value to add in the option.
	 * @param string $errorMessage The expectd error message thrown with the `TypeError`.
	 */
	public function testUpdateTypeStringStrictInvalid($value, string $errorMessage): void
	{
		/**
		 * Assumes that the option is already added with a value since the test only
		 * concerns about the value retrieved with the `get_option` function.
		 */
		add_option(
			$this->optionName,
			(new InputSanitizer())->sanitize($value),
		);

		$registry = new Registry([new Option($this->optionName, 'string')], 1);
		$registry->hook($this->hook);
		$registry->register();
		$this->hook->run();

		$this->expectException(TypeError::class);
		$this->expectExceptionMessage($errorMessage);

		update_option($this->optionName, $value);
	}

	public function dataTypeStringStrictInvalid(): iterable
	{
		yield [1, 'Value must be of type string, integer given.'];
		yield [1.2, 'Value must be of type string, number (float) given.'];
		yield [false, 'Value must be of type string, boolean given.'];
		yield [true, 'Value must be of type string, boolean given.'];
		yield [[1], 'Value must be of type string, array given.'];
		yield [['foo' => 'bar'], 'Value must be of type string, array given.'];
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
		/**
		 * Assumes that the option is already added with a value since the test only
		 * concerns about the value retrieved with the `get_option` function.
		 */
		add_option(
			$this->optionName,
			(new InputSanitizer())->sanitize($value),
		);

		$registry = new Registry([new Option($this->optionName, 'boolean')]);
		$registry->hook($this->hook);
		$registry->register();
		$this->hook->run();

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
	 * @param mixed $value The value to add in the option.
	 */
	public function testGetTypeBooleanStrictValid($value): void
	{
		/**
		 * Assumes that the option is already added with a value since the test only
		 * concerns about the value retrieved with the `get_option` function.
		 */
		add_option(
			$this->optionName,
			(new InputSanitizer())->sanitize($value),
		);

		$registry = new Registry([new Option($this->optionName, 'boolean')], 1);
		$registry->hook($this->hook);
		$registry->register();
		$this->hook->run();

		$this->assertSame($value, get_option($this->optionName));
	}

	/**
	 * @dataProvider dataTypeBooleanStrictValid
	 * @group type-boolean
	 * @group strict-mode
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testAddTypeBooleanStrictValid($value): void
	{
		$registry = new Registry([new Option($this->optionName, 'boolean')], 1);
		$registry->hook($this->hook);
		$registry->register();
		$this->hook->run();

		add_option($this->optionName, $value);

		$this->assertSame($value, get_option($this->optionName));
	}

	/**
	 * @dataProvider dataTypeBooleanStrictValid
	 * @group type-boolean
	 * @group strict-mode
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testUpdateTypeBooleanStrictValid($value): void
	{
		/**
		 * Assumes that the option is already added with a value since the test only
		 * concerns about the value retrieved with the `get_option` function.
		 */
		add_option(
			$this->optionName,
			(new InputSanitizer())->sanitize($value),
		);

		$registry = new Registry([new Option($this->optionName, 'boolean')], 1);
		$registry->hook($this->hook);
		$registry->register();
		$this->hook->run();

		update_option($this->optionName, $value);

		$this->assertSame($value, get_option($this->optionName));
	}

	public function dataTypeBooleanStrictValid(): iterable
	{
		yield [true];
		yield [false];
		yield [null];
	}

	/**
	 * @dataProvider dataTypeBooleanStrictInvalid
	 * @group type-boolean
	 * @group strict-mode
	 *
	 * @param mixed  $value        The value to add in the option.
	 * @param string $errorMessage The expected error message thrown with the `TypeError`.
	 */
	public function testGetTypeBooleanStrictInvalid($value, string $errorMessage): void
	{
		/**
		 * Assumes that the option is already added with a value since the test only
		 * concerns about the value retrieved with the `get_option` function.
		 */
		add_option(
			$this->optionName,
			(new InputSanitizer())->sanitize($value),
		);

		$registry = new Registry([new Option($this->optionName, 'boolean')], 1);
		$registry->hook($this->hook);
		$registry->register();
		$this->hook->run();

		$this->expectException(TypeError::class);
		$this->expectExceptionMessage($errorMessage);

		get_option($this->optionName);
	}

	/**
	 * @dataProvider dataTypeBooleanStrictInvalid
	 * @group type-boolean
	 * @group strict-mode
	 *
	 * @param mixed  $value        The value to add in the option.
	 * @param string $errorMessage The expected error message thrown with the `TypeError`.
	 */
	public function testAddTypeBooleanStrictInvalid($value, string $errorMessage): void
	{
		$registry = new Registry([new Option($this->optionName, 'boolean')], 1);
		$registry->hook($this->hook);
		$registry->register();
		$this->hook->run();

		$this->expectException(TypeError::class);
		$this->expectExceptionMessage($errorMessage);

		add_option($this->optionName, $value);
	}

	/**
	 * @dataProvider dataTypeBooleanStrictInvalid
	 * @group type-boolean
	 * @group strict-mode
	 *
	 * @param mixed  $value        The value to add in the option.
	 * @param string $errorMessage The expected error message thrown with the `TypeError`.
	 */
	public function testUpdateTypeBooleanStrictInvalid($value, string $errorMessage): void
	{
		/**
		 * Assumes that the option is already added with a value since the test only
		 * concerns about the value updated with the `update_option` function, and
		 * retrieved with the `get_option` function.
		 */
		add_option(
			$this->optionName,
			(new InputSanitizer())->sanitize($value),
		);

		$registry = new Registry([new Option($this->optionName, 'boolean')], 1);
		$registry->hook($this->hook);
		$registry->register();
		$this->hook->run();

		$this->expectException(TypeError::class);
		$this->expectExceptionMessage($errorMessage);

		update_option($this->optionName, $value);

		$this->assertSame($value, get_option($this->optionName));
	}

	public function dataTypeBooleanStrictInvalid(): iterable
	{
		yield ['Hello world!', 'Value must be of type boolean, string given.'];
		yield ['', 'Value must be of type boolean, string given.'];
		yield [' ', 'Value must be of type boolean, string given.'];
		yield [0, 'Value must be of type boolean, integer given.'];
		yield [1, 'Value must be of type boolean, integer given.'];
		yield [1.2, 'Value must be of type boolean, number (float) given.'];
		yield [-1, 'Value must be of type boolean, integer given.'];
		yield [[], 'Value must be of type boolean, array given.'];
		yield [['foo' => 'bar'], 'Value must be of type boolean, array given.'];
		yield ['false', 'Value must be of type boolean, string given.'];
		yield ['true', 'Value must be of type boolean, string given.'];
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
		/**
		 * Assumes that the option is already added with a value since the test only
		 * concerns about the value updated with the `update_option` function.
		 */
		add_option(
			$this->optionName,
			(new InputSanitizer())->sanitize($value),
		);

		$registry = new Registry([new Option($this->optionName, 'integer')]);
		$registry->hook($this->hook);
		$registry->register();
		$this->hook->run();

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
	 * @param mixed $value The value to add in the option.
	 */
	public function testGetTypeIntegerStrictValid($value): void
	{
		/**
		 * Assumes that the option is already added with a value since the test only
		 * concerns about the value retrieved with the `get_option` function.
		 */
		add_option(
			$this->optionName,
			(new InputSanitizer())->sanitize($value),
		);

		$registry = new Registry([new Option($this->optionName, 'integer')], 1);
		$registry->hook($this->hook);
		$registry->register();
		$this->hook->run();

		$this->assertSame($value, get_option($this->optionName));
	}

	/**
	 * @dataProvider dataTypeIntegerStrictValid
	 * @group type-integer
	 * @group strict-mode
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testAddTypeIntegerStrictValid($value): void
	{
		$registry = new Registry([new Option($this->optionName, 'integer')], 1);
		$registry->hook($this->hook);
		$registry->register();
		$this->hook->run();

		add_option($this->optionName, $value);

		$this->assertSame($value, get_option($this->optionName));
	}

	/**
	 * @dataProvider dataTypeIntegerStrictValid
	 * @group type-integer
	 * @group strict-mode
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testUpdateTypeIntegerStrictValid($value): void
	{
		/**
		 * Assumes that the option is already added with a value since the test only
		 * concerns about the value updated with the `update_option` function, and
		 * retrieved with the `get_option` function.
		 */
		add_option(
			$this->optionName,
			(new InputSanitizer())->sanitize($value),
		);

		$registry = new Registry([new Option($this->optionName, 'integer')], 1);
		$registry->hook($this->hook);
		$registry->register();
		$this->hook->run();

		update_option($this->optionName, $value);

		$this->assertSame($value, get_option($this->optionName));
	}

	public function dataTypeIntegerStrictValid(): iterable
	{
		yield [1]; // Positive
		yield [-1]; // Negative
		yield [0123]; // Octal
		yield [0x1A]; // Hexadecimal
		yield [0b11111111]; // Binary
		yield [1_234_567];
		yield [null];
	}

	/**
	 * @dataProvider dataTypeIntegerStrictInvalid
	 * @group type-integer
	 * @group strict-mode
	 *
	 * @param mixed  $value        The value to add in the option.
	 * @param string $errorMessage The expected error message thrown with the `TypeError`.
	 */
	public function testGetTypeIntegerStrictInvalid($value, string $errorMessage): void
	{
		/**
		 * Assumes that the option is already added with a value since the test only
		 * concerns about the value retrieved with the `get_option` function.
		 */
		add_option(
			$this->optionName,
			(new InputSanitizer())->sanitize($value),
		);

		$registry = new Registry([new Option($this->optionName, 'integer')], 1);
		$registry->hook($this->hook);
		$registry->register();
		$this->hook->run();

		$this->expectException(TypeError::class);
		$this->expectExceptionMessage($errorMessage);

		get_option($this->optionName);
	}

	/**
	 * @dataProvider dataTypeIntegerStrictInvalid
	 * @group type-integer
	 * @group strict-mode
	 *
	 * @param mixed  $value        The value to add in the option.
	 * @param string $errorMessage The expected error message thrown with the `TypeError`.
	 */
	public function testAddTypeIntegerStrictInvalid($value, string $errorMessage): void
	{
		$registry = new Registry([new Option($this->optionName, 'integer')], 1);
		$registry->hook($this->hook);
		$registry->register();
		$this->hook->run();

		$this->expectException(TypeError::class);
		$this->expectExceptionMessage($errorMessage);

		add_option($this->optionName, $value);
	}

	/**
	 * @dataProvider dataTypeIntegerStrictInvalid
	 * @group type-integer
	 * @group strict-mode
	 *
	 * @param mixed  $value        The value to add in the option.
	 * @param string $errorMessage The expected error message thrown with the `TypeError`.
	 */
	public function testUpdateTypeIntegerStrictInvalid($value, string $errorMessage): void
	{
		/**
		 * Assumes that the option is already added with a value since the test only
		 * concerns about the value retrieved with the `get_option` function, and
		 * updated with the `update_option` function.
		 */
		add_option(
			$this->optionName,
			(new InputSanitizer())->sanitize($value),
		);

		$registry = new Registry([new Option($this->optionName, 'integer')], 1);
		$registry->hook($this->hook);
		$registry->register();
		$this->hook->run();

		$this->expectException(TypeError::class);
		$this->expectExceptionMessage($errorMessage);

		update_option($this->optionName, $value);

		$this->assertSame($value, get_option($this->optionName));
	}

	public function dataTypeIntegerStrictInvalid(): iterable
	{
		yield ['Hello world!', 'Value must be of type integer, string given.'];
		yield ['', 'Value must be of type integer, string given.'];
		yield [1.2, 'Value must be of type integer, number (float) given.'];
		yield [false, 'Value must be of type integer, boolean given.'];
		yield [true, 'Value must be of type integer, boolean given.'];
		yield [['foo'], 'Value must be of type integer, array given.'];
		yield [['foo' => 'bar'], 'Value must be of type integer, array given.'];
	}

	/**
	 * @dataProvider dataTypeNumber
	 * @group type-number
	 *
	 * @param mixed $value  The value to add in the option.
	 * @param mixed $expect The value to be returned.
	 */
	public function testGetTypeNumber($value, $expect): void
	{
		/**
		 * Assumes that the option is already added with a value since the test only
		 * concerns about the value retrieved with the `get_option` function.
		 */
		add_option(
			$this->optionName,
			(new InputSanitizer())->sanitize($value),
		);

		$registry = new Registry([new Option($this->optionName, 'number')]);
		$registry->hook($this->hook);
		$registry->register();
		$this->hook->run();

		$this->assertSame($expect, get_option($this->optionName));
	}

	/**
	 * Non-strict. Value may be coerced.
	 */
	public function dataTypeNumber(): iterable
	{
		yield [0, 0];
		yield [1, 1];
		yield [1.2, 1.2];
		yield ['1', 1];
		yield ['1.2', 1.2];
		yield [-1, -1];
		yield [false, 0];
		yield [true, 1];

		/**
		 * As certain types have undefined behavior when converting to number.
		 */
		yield [[], null];
		yield [['foo'], null];
		yield [['foo' => 'bar'], null];
		yield ['Hello world!', null];
		yield ['', null];

		yield [null, null];
	}

	/**
	 * @dataProvider dataTypeNumberStrictValid
	 * @group type-number
	 * @group strict-mode
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testGetTypeNumberStrictValid($value): void
	{
		/**
		 * Assumes that the option is already added with a value since the test only
		 * concerns about the value retrieved with the `get_option` function, and
		 * updated with the `update_option` function.
		 */
		add_option(
			$this->optionName,
			(new InputSanitizer())->sanitize($value),
		);

		$registry = new Registry([new Option($this->optionName, 'number')], 1);
		$registry->hook($this->hook);
		$registry->register();
		$this->hook->run();

		$this->assertSame($value, get_option($this->optionName));
	}

	/**
	 * @dataProvider dataTypeNumberStrictValid
	 * @group type-number
	 * @group strict-mode
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testAddTypeNumberStrictValid($value): void
	{
		$registry = new Registry([new Option($this->optionName, 'number')], 1);
		$registry->hook($this->hook);
		$registry->register();
		$this->hook->run();

		add_option($this->optionName, $value);

		$this->assertSame($value, get_option($this->optionName));
	}

	/**
	 * @dataProvider dataTypeNumberStrictValid
	 * @group type-number
	 * @group strict-mode
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testUpdateTypeNumberStrictValid($value): void
	{
		/**
		 * Assumes that the option is already added with a value since the test only
		 * concerns about the value retrieved with the `get_option` function, and
		 * updated with the `update_option` function.
		 */
		add_option(
			$this->optionName,
			(new InputSanitizer())->sanitize($value),
		);

		$registry = new Registry([new Option($this->optionName, 'number')], 1);
		$registry->hook($this->hook);
		$registry->register();
		$this->hook->run();

		update_option($this->optionName, $value);

		$this->assertSame($value, get_option($this->optionName));
	}

	public function dataTypeNumberStrictValid(): iterable
	{
		yield [1.2]; // Positive
		yield [-1.2]; // Negative
		yield [1.2e3]; // Scientific notation
		yield [7E-10]; // Scientific notation
		yield [1_234_567.89];

		// Integers
		yield [1];
		yield [-1];
		yield [0];

		yield [null];
	}

	/**
	 * @dataProvider dataTypeNumberStrictInvalid
	 * @group type-number
	 * @group strict-mode
	 *
	 * @param mixed  $value        The value to add in the option.
	 * @param string $errorMessage The expected error message thrown with the `TypeError`.
	 */
	public function testGetTypeNumberStrictInvalid($value, string $errorMessage): void
	{
		/**
		 * Assumes that the option is already added with a value since the test only
		 * concerns about the value retrieved with the `get_option` function.
		 */
		add_option(
			$this->optionName,
			(new InputSanitizer())->sanitize($value),
		);

		$registry = new Registry([new Option($this->optionName, 'number')], 1);
		$registry->hook($this->hook);
		$registry->register();
		$this->hook->run();

		$this->expectException(TypeError::class);

		get_option($this->optionName);
	}

	/**
	 * @dataProvider dataTypeNumberStrictInvalid
	 * @group type-number
	 * @group strict-mode
	 *
	 * @param mixed  $value        The value to add in the option.
	 * @param string $errorMessage The expected error message thrown with the `TypeError`.
	 */
	public function testAddTypeNumberStrictInvalid($value, string $errorMessage): void
	{
		$registry = new Registry([new Option($this->optionName, 'number')], 1);
		$registry->hook($this->hook);
		$registry->register();
		$this->hook->run();

		$this->expectException(TypeError::class);
		$this->expectExceptionMessage($errorMessage);

		add_option($this->optionName, $value);
	}

	/**
	 * @dataProvider dataTypeNumberStrictInvalid
	 * @group type-number
	 * @group strict-mode
	 *
	 * @param mixed  $value        The value to add in the option.
	 * @param string $errorMessage The expected error message thrown with the `TypeError`.
	 */
	public function testUpdateTypeNumberStrictInvalid($value, string $errorMessage): void
	{
		/**
		 * Assumes that the option is already added with a value since the test only
		 * concerns about the value retrieved with the `get_option` function, and
		 * updated with the `update_option` function.
		 */
		add_option(
			$this->optionName,
			(new InputSanitizer())->sanitize($value),
		);

		$registry = new Registry([new Option($this->optionName, 'number')], 1);
		$registry->hook($this->hook);
		$registry->register();
		$this->hook->run();

		$this->expectException(TypeError::class);
		$this->expectExceptionMessage($errorMessage);

		update_option($this->optionName, $value);
	}

	public function dataTypeNumberStrictInvalid(): iterable
	{
		yield ['Hello world!', 'Value must be of type number, string given.'];
		yield ['', 'Value must be of type number, string given.'];
		yield [false, 'Value must be of type number, boolean given.'];
		yield [true, 'Value must be of type number, boolean given.'];
		yield [[], 'Value must be of type number, array given.'];
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
		/**
		 * Assumes that the option is already added with a value since the test only
		 * concerns about the value retrieved with the `get_option` function, and
		 * updated with the `update_option` function.
		 */
		add_option(
			$this->optionName,
			(new InputSanitizer())->sanitize($value),
		);

		$registry = new Registry([new Option($this->optionName, 'array')]);
		$registry->hook($this->hook);
		$registry->register();
		$this->hook->run();

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
		/**
		 * Assumes that the option is already added with a value since the test only
		 * concerns about the value retrieved with the `get_option` function.
		 */
		add_option(
			$this->optionName,
			(new InputSanitizer())->sanitize($value),
		);

		$registry = new Registry([new Option($this->optionName, 'array')], 1);
		$registry->hook($this->hook);
		$registry->register();
		$this->hook->run();

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
		$registry = new Registry([new Option($this->optionName, 'array')], 1);
		$registry->hook($this->hook);
		$registry->register();
		$this->hook->run();

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
		/**
		 * Assumes that the option is already added with a value since the test only
		 * concerns about the value retrieved with the `get_option` function, and
		 * updated with the `update_option` function.
		 */
		add_option(
			$this->optionName,
			(new InputSanitizer())->sanitize($value),
		);

		$registry = new Registry([new Option($this->optionName, 'array')], 1);
		$registry->hook($this->hook);
		$registry->register();
		$this->hook->run();

		update_option($this->optionName, $value);

		$this->assertSame($value, get_option($this->optionName));
	}

	public function dataTypeArrayStrictValid(): iterable
	{
		yield [[]];
		yield [['foo']];
		yield [['foo' => 'bar']];
		yield [null];
	}

	/**
	 * @dataProvider dataTypeArrayStrictInvalid
	 * @group type-array
	 * @group strict-mode
	 *
	 * @param mixed  $value        The value to add in the option.
	 * @param string $errorMessage The expected error message thrown with the `TypeError`.
	 */
	public function testGetTypeArrayStrictInvalid($value, string $errorMessage): void
	{
		/**
		 * Assumes that the option is already added with a value since the test only
		 * concerns about the value retrieved with the `get_option` function.
		 */
		add_option(
			$this->optionName,
			(new InputSanitizer())->sanitize($value),
		);

		$registry = new Registry([new Option($this->optionName, 'array')], 1);
		$registry->hook($this->hook);
		$registry->register();
		$this->hook->run();

		$this->expectException(TypeError::class);
		$this->expectExceptionMessage($errorMessage);

		get_option($this->optionName);
	}

	/**
	 * @dataProvider dataTypeArrayStrictInvalid
	 * @group type-array
	 * @group strict-mode
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testAddTypeArrayStrictInvalid($value, string $errorMessage): void
	{
		$registry = new Registry([new Option($this->optionName, 'array')], 1);
		$registry->hook($this->hook);
		$registry->register();
		$this->hook->run();

		$this->expectException(TypeError::class);
		$this->expectExceptionMessage($errorMessage);

		add_option($this->optionName, $value);
	}

	/**
	 * @dataProvider dataTypeArrayStrictInvalid
	 * @group type-array
	 * @group strict-mode
	 *
	 * @param mixed  $value        The value to add in the option.
	 * @param string $errorMessage The expected error message thrown with the `TypeError`.
	 */
	public function testUpdateTypeArrayStrictInvalid($value, string $errorMessage): void
	{
		/**
		 * Assumes that the option is already added with a value since the test only
		 * concerns about the value retrieved with the `get_option` function, and
		 * updated with the `update_option` function.
		 */
		add_option(
			$this->optionName,
			(new InputSanitizer())->sanitize($value),
		);

		$registry = new Registry([new Option($this->optionName, 'array')], 1);
		$registry->hook($this->hook);
		$registry->register();
		$this->hook->run();

		$this->expectException(TypeError::class);
		$this->expectExceptionMessage($errorMessage);

		update_option($this->optionName, $value);
	}

	public function dataTypeArrayStrictInvalid(): iterable
	{
		yield ['Hello world!', 'Value must be of type array, string given.'];
		yield ['', 'Value must be of type array, string given.'];
		yield [0, 'Value must be of type array, integer given.'];
		yield [1.2, 'Value must be of type array, number (float) given.'];
		yield [-1, 'Value must be of type array, integer given.'];
		yield [false, 'Value must be of type array, boolean given.'];
		yield [true, 'Value must be of type array, boolean given.'];
	}

	// /**
	//  * @dataProvider dataConstraints
	//  * @group strict-mode
	//  *
	//  * @param mixed $constraints  The constraints to be passed in the schema.
	//  * @param mixed $value        The value to add in the option.
	//  * @param mixed $errorMessage The expected error message.
	//  */
	// public function testAddConstraints($constraints, $value, $errorMessage): void
	// {
	// 	$option = new Option($this->hook, null, 1);
	// 	$option->setSchema([
	// 		$this->optionName => [
	// 			'type' => 'string',
	// 			'constraints' => $constraints,
	// 		],
	// 	]);
	// 	$option->register();

	// 	$this->expectException(InvalidArgumentException::class);
	// 	$this->expectExceptionMessage($errorMessage);

	// 	add_option($this->optionName, $value);
	// }

	// /**
	//  * @dataProvider dataConstraints
	//  *
	//  * @param mixed $constraints The constraints to be passed in the schema.
	//  * @param mixed $value       The value to add in the option.
	//  */
	// public function testAddConstraintsNonStrict($constraints, $value): void
	// {
	// 	$option = new Option($this->hook);
	// 	$option->setSchema([
	// 		$this->optionName => [
	// 			'type' => 'string',
	// 			'constraints' => $constraints,
	// 		],
	// 	]);
	// 	$option->register();

	// 	add_option($this->optionName, $value);

	// 	$this->assertSame($value, get_option($this->optionName));
	// }

	// /**
	//  * @dataProvider dataConstraints
	//  * @group strict-mode
	//  *
	//  * @param mixed $constraints  The constraints to be passed in the schema.
	//  * @param mixed $value        The value to add in the option.
	//  * @param mixed $errorMessage The expected error message.
	//  */
	// public function testUpdateConstraints($constraints, $value, $errorMessage): void
	// {
	// 	add_option($this->optionName, ['__syntatis' => 'email@example.org']);

	// 	$option = new Option($this->hook, null, 1);
	// 	$option->setSchema([
	// 		$this->optionName => [
	// 			'type' => 'string',
	// 			'constraints' => $constraints,
	// 		],
	// 	]);
	// 	$option->register();

	// 	$this->expectException(InvalidArgumentException::class);
	// 	$this->expectExceptionMessage($errorMessage);

	// 	update_option($this->optionName, $value);
	// }

	// /**
	//  * @dataProvider dataConstraints
	//  *
	//  * @param mixed $constraints The constraints to be passed in the schema.
	//  * @param mixed $value       The value to add in the option.
	//  */
	// public function testUpdateConstraintsNonStrict($constraints, $value): void
	// {
	// 	add_option($this->optionName, ['__syntatis' => 'email@example.org']);

	// 	$option = new Option($this->hook);
	// 	$option->setSchema([
	// 		$this->optionName => [
	// 			'type' => 'string',
	// 			'constraints' => $constraints,
	// 		],
	// 	]);
	// 	$option->register();

	// 	$this->assertSame('email@example.org', get_option($this->optionName));

	// 	update_option($this->optionName, $value);

	// 	$this->assertSame($value, get_option($this->optionName));
	// }

	// public function dataConstraints(): iterable
	// {
	// 	yield ['\Syntatis\Utils\is_email', 'Maybe Email', 'Value does not match the given constraints.'];
	// 	yield [new Assert\Email(null, 'The email {{ value }} is not a valid email.'), 'Hello Email', 'The email "Hello Email" is not a valid email.'];

	// 	// With arrays.
	// 	yield [['\Syntatis\Utils\is_email'], 'Maybe Email', 'Value does not match the given constraints.'];
	// 	yield [[new Assert\Email(null, 'The email {{ value }} is not a valid email.')], 'Hello Email', 'The email "Hello Email" is not a valid email.'];
	// }
}
