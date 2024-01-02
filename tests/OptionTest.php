<?php

declare(strict_types=1);

namespace Syntatis\WP\Option\Tests;

use Syntatis\WP\Hook\Hook;
use Syntatis\WP\Option\Option;
use TypeError;

use function gettype;

class OptionTest extends TestCase
{
	private Hook $hook;

	public function setUp(): void
	{
		parent::setUp();

		$this->hook = new Hook();
	}

	/**
	 * @dataProvider dataHasDefaultSet
	 *
	 * @param mixed $default The default value to return
	 */
	public function testHasDefaultSet(string $type, $default): void
	{
		$optionName = 'foo_bar_default';
		$option = new Option($this->hook);
		$option->setSchema([
			$optionName => [
				'type' => $type,
				'default' => $default,
			],
		]);
		$option->register();

		$this->assertSame($default, get_option($optionName));

		delete_option($optionName);
	}

	public function testHasNoDefaultSet(): void
	{
		$optionName = 'foo_bar_no_default';
		$option = new Option($this->hook);
		$option->setSchema([$optionName => ['type' => 'string']]);
		$option->register();

		$this->assertNull(get_option($optionName));

		delete_option($optionName);
	}

	public function testHasDefaultPassed(): void
	{
		$optionName = 'foo_bar_default';
		$option = new Option($this->hook);
		$option->setSchema([
			$optionName => [
				'type' => 'integer',
				'default' => 1,
			],
		]);
		$option->register();

		$this->assertSame(1, get_option($optionName));
		$this->assertSame(2, get_option($optionName, '2'));
	}

	public function testHasDefaultPassedStrictValid(): void
	{
		$optionName = 'foo_bar_default';
		$option = new Option($this->hook, null, 1);
		$option->setSchema([
			$optionName => [
				'type' => 'integer',
				'default' => 1,
			],
		]);
		$option->register();

		$this->assertSame(1, get_option($optionName));
		$this->assertSame(2, get_option($optionName, 2));
	}

	public function testHasDefaultPassedStrictInvalid(): void
	{
		$optionName = 'foo_bar_default';
		$option = new Option($this->hook, null, 1);
		$option->setSchema([
			$optionName => [
				'type' => 'integer',
				'default' => 1,
			],
		]);
		$option->register();

		$this->assertSame(1, get_option($optionName));

		$this->expectException(TypeError::class);

		get_option($optionName, '2'); // Default should be set to an integer, not a string.
	}

	public function testHasPrefix(): void
	{
		$optionName = 'foo_bar_prefix';
		$option = new Option($this->hook, 'syntatis_');
		$option->setSchema([$optionName => ['type' => 'string']]);

		$this->assertFalse(has_filter('default_option_syntatis_' . $optionName));
		$this->assertFalse(has_filter('option_syntatis_' . $optionName));

		$option->register();

		$this->assertTrue(has_filter('default_option_syntatis_' . $optionName));
		$this->assertTrue(has_filter('option_syntatis_' . $optionName));

		add_option('syntatis_' . $optionName, 'Hello world!');

		$this->assertSame('Hello world!', get_option('syntatis_' . $optionName));
	}

	/**
	 * @dataProvider dataTypeString
	 *
	 * @param mixed $value  The value to add in the option.
	 * @param mixed $expect The expected value to be returned.
	 */
	public function testGetTypeString($value, $expect): void
	{
		$optionName = 'foo_bar_string';

		add_option($optionName, $value);

		$option = new Option($this->hook);
		$option->setSchema([$optionName => ['type' => 'string']]);
		$option->register();

		$this->assertSame($expect, get_option($optionName));

		delete_option($optionName);
	}

	/**
	 * @dataProvider dataTypeStringStrictValid
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testGetTypeStringStrictValid($value): void
	{
		$optionName = 'foo_bar_string';

		add_option($optionName, $value);

		$option = new Option($this->hook, null, 1);
		$option->setSchema([$optionName => ['type' => 'string']]);
		$option->register();

		$this->assertSame($value, get_option($optionName));
	}

	/**
	 * @dataProvider dataTypeStringStrictValid
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testAddTypeStringStrictValid($value): void
	{
		$optionName = 'foo_bar_string';

		$option = new Option($this->hook, null, 1);
		$option->setSchema([$optionName => ['type' => 'string']]);
		$option->register();

		add_option($optionName, $value);

		$this->assertSame($value, get_option($optionName));
	}

	/**
	 * @dataProvider dataTypeStringStrictValid
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testUpdateTypeStringStrictValid($value): void
	{
		$optionName = 'foo_bar_string';

		add_option($optionName, 'initial-value');

		$option = new Option($this->hook, null, 1);
		$option->setSchema([$optionName => ['type' => 'string']]);
		$option->register();

		update_option($optionName, $value);

		$this->assertSame($value, get_option($optionName));
	}

	/**
	 * @dataProvider dataTypeStringStrictInvalid
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testGetTypeStringStrictInvalid($value): void
	{
		$optionName = 'foo_bar_string';

		add_option($optionName, $value);

		$option = new Option($this->hook, null, 1);
		$option->setSchema([$optionName => ['type' => 'string']]);
		$option->register();

		$this->expectException(TypeError::class);

		get_option($optionName);
	}

	/**
	 * @dataProvider dataTypeStringStrictInvalid
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testAddTypeStringStrictInvalid($value): void
	{
		$optionName = 'foo_bar_string';
		$option = new Option($this->hook, null, 1);
		$option->setSchema([$optionName => ['type' => 'string']]);
		$option->register();

		$this->expectException(TypeError::class);
		$this->expectExceptionMessage('Value must be of type string, ' . gettype($value) . ' type given.');

		add_option($optionName, $value);
	}

	/**
	 * @dataProvider dataTypeStringStrictInvalid
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testUpdateTypeStringStrictInvalid($value): void
	{
		$optionName = 'foo_bar_string';

		add_option($optionName, 'initial-value');

		$option = new Option($this->hook, null, 1);
		$option->setSchema([$optionName => ['type' => 'string']]);
		$option->register();

		$this->expectException(TypeError::class);
		$this->expectExceptionMessage('Value must be of type string, ' . gettype($value) . ' type given.');

		update_option($optionName, $value);
	}

	/**
	 * @dataProvider dataTypeBoolean
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testGetTypeBoolean($value, bool $expect): void
	{
		$optionName = 'foo_bar_boolean';

		add_option($optionName, $value);

		$option = new Option($this->hook);
		$option->setSchema([$optionName => ['type' => 'boolean']]);
		$option->register();

		$this->assertSame($expect, get_option($optionName));

		delete_option($optionName);
	}

	/**
	 * @dataProvider dataTypeBooleanStrictValid
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testGetTypeBooleanStrictValid($value): void
	{
		$optionName = 'foo_bar_boolean';

		add_option($optionName, $value);

		$option = new Option($this->hook, null, 1);
		$option->setSchema([$optionName => ['type' => 'boolean']]);
		$option->register();

		$this->assertSame($value, get_option($optionName));
	}

	/**
	 * @dataProvider dataTypeBooleanStrictValid
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testAddTypeBooleanStrictValid($value): void
	{
		$optionName = 'foo_bar_boolean';
		$option = new Option($this->hook, null, 1);
		$option->setSchema([$optionName => ['type' => 'boolean']]);
		$option->register();

		add_option($optionName, $value);

		$this->assertSame($value, get_option($optionName));
	}

	/**
	 * @dataProvider dataTypeBooleanStrictValid
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testUpdateTypeBooleanStrictValid($value): void
	{
		$optionName = 'foo_bar_boolean';

		add_option($optionName, true);

		$option = new Option($this->hook, null, 1);
		$option->setSchema([$optionName => ['type' => 'boolean']]);
		$option->register();

		update_option($optionName, $value);

		$this->assertSame($value, get_option($optionName));
	}

	/**
	 * @dataProvider dataTypeBooleanStrictInvalid
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testGetTypeBooleanStrictInvalid($value): void
	{
		$optionName = 'foo_bar_boolean';

		add_option($optionName, $value);

		$option = new Option($this->hook, null, 1);
		$option->setSchema([$optionName => ['type' => 'boolean']]);
		$option->register();

		$this->expectException(TypeError::class);

		get_option($optionName);
	}

	/**
	 * @dataProvider dataTypeBooleanStrictInvalid
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testAddTypeBooleanStrictInvalid($value): void
	{
		$optionName = 'foo_bar_boolean';
		$option = new Option($this->hook, null, 1);
		$option->setSchema([$optionName => ['type' => 'boolean']]);
		$option->register();

		$this->expectException(TypeError::class);
		$this->expectExceptionMessage('Value must be of type boolean, ' . gettype($value) . ' type given.');

		add_option($optionName, $value);
	}

	/**
	 * @dataProvider dataTypeBooleanStrictInvalid
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testUpdateTypeBooleanStrictInvalid($value): void
	{
		$optionName = 'foo_bar_boolean';

		add_option($optionName, false);

		$option = new Option($this->hook, null, 1);
		$option->setSchema([$optionName => ['type' => 'boolean']]);
		$option->register();

		$this->expectException(TypeError::class);
		$this->expectExceptionMessage('Value must be of type boolean, ' . gettype($value) . ' type given.');

		update_option($optionName, $value);
	}

	/**
	 * @dataProvider dataTypeInteger
	 *
	 * @param mixed $value  The value to add in the option.
	 * @param mixed $expect The expected value to be returned.
	 */
	public function testGetTypeInteger($value, $expect): void
	{
		$optionName = 'foo_bar_integer';

		add_option($optionName, $value);

		$option = new Option($this->hook);
		$option->setSchema([$optionName => ['type' => 'integer']]);
		$option->register();

		$this->assertSame($expect, get_option($optionName));

		delete_option($optionName);
	}

	/**
	 * @dataProvider dataTypeIntegerValid
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testGetTypeIntegerValid($value): void
	{
		$optionName = 'foo_bar_integer';

		add_option($optionName, $value);

		$option = new Option($this->hook, null, 1);
		$option->setSchema([$optionName => ['type' => 'integer']]);
		$option->register();

		$this->assertSame($value, get_option($optionName));
	}

	/**
	 * @dataProvider dataTypeIntegerValid
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testAddTypeIntegerValid($value): void
	{
		$optionName = 'foo_bar_integer';

		$option = new Option($this->hook, null, 1);
		$option->setSchema([$optionName => ['type' => 'integer']]);
		$option->register();

		add_option($optionName, $value);

		$this->assertSame($value, get_option($optionName));
	}

	/**
	 * @dataProvider dataTypeIntegerValid
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testUpdateTypeIntegerValid($value): void
	{
		$optionName = 'foo_bar_integer';

		add_option($optionName, $value);

		$option = new Option($this->hook, null, 1);
		$option->setSchema([$optionName => ['type' => 'integer']]);
		$option->register();

		update_option($optionName, $value);

		$this->assertSame($value, get_option($optionName));
	}

	/**
	 * @dataProvider dataTypeIntegerInvalid
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testGetTypeIntegerInvalid($value): void
	{
		$optionName = 'foo_bar_integer';

		add_option($optionName, $value);

		$option = new Option($this->hook, null, 1);
		$option->setSchema([$optionName => ['type' => 'integer']]);
		$option->register();

		$this->expectException(TypeError::class);

		get_option($optionName);
	}

	/**
	 * @dataProvider dataTypeIntegerInvalid
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testAddTypeIntegerInvalid($value): void
	{
		$optionName = 'foo_bar_integer';
		$option = new Option($this->hook, null, 1);
		$option->setSchema([$optionName => ['type' => 'integer']]);
		$option->register();

		$this->expectException(TypeError::class);

		add_option($optionName, $value);
	}

	/**
	 * @dataProvider dataTypeIntegerInvalid
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testUpdateTypeIntegerInvalid($value): void
	{
		$optionName = 'foo_bar_integer';

		add_option($optionName, 1);

		$option = new Option($this->hook, null, 1);
		$option->setSchema([$optionName => ['type' => 'integer']]);
		$option->register();

		$this->expectException(TypeError::class);

		update_option($optionName, $value);
	}

	/**
	 * @dataProvider dataTypeFloat
	 *
	 * @param mixed $value  The value to add in the option.
	 * @param mixed $expect The value to be returned.
	 */
	public function testGetTypeFloat($value, $expect): void
	{
		$optionName = 'foo_bar_float';

		add_option($optionName, $value);

		$option = new Option($this->hook);
		$option->setSchema([$optionName => ['type' => 'float']]);
		$option->register();

		$this->assertSame($expect, get_option($optionName));

		delete_option($optionName);
	}

	/**
	 * @dataProvider dataTypeFloatValid
	 *
	 * @param mixed $value  The value to add in the option.
	 * @param mixed $expect The value to be returned.
	 */
	public function testGetTypeFloatValid($value, $expect): void
	{
		$optionName = 'foo_bar_float';

		add_option($optionName, $value);

		$option = new Option($this->hook, null, 1);
		$option->setSchema([$optionName => ['type' => 'float']]);
		$option->register();

		$this->assertSame($expect, get_option($optionName));

		delete_option($optionName);
	}

	/**
	 * @dataProvider dataTypeFloatValid
	 *
	 * @param mixed $value  The value to add in the option.
	 * @param mixed $expect The value to be returned.
	 */
	public function testAddTypeFloatValid($value, $expect): void
	{
		$optionName = 'foo_bar_float';
		$option = new Option($this->hook, null, 1);
		$option->setSchema([$optionName => ['type' => 'float']]);
		$option->register();

		add_option($optionName, $value);

		$this->assertSame($expect, get_option($optionName));

		delete_option($optionName);
	}

	/**
	 * @dataProvider dataTypeFloatValid
	 *
	 * @param mixed $value  The value to add in the option.
	 * @param mixed $expect The value to be returned.
	 */
	public function testUpdateTypeFloatValid($value, $expect): void
	{
		$optionName = 'foo_bar_float';

		add_option($optionName, 100.0);

		$option = new Option($this->hook, null, 1);
		$option->setSchema([$optionName => ['type' => 'float']]);
		$option->register();

		update_option($optionName, $value);

		$this->assertSame($expect, get_option($optionName));

		delete_option($optionName);
	}

	/**
	 * @dataProvider dataTypeFloatInvalid
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testGetTypeFloatInvalid($value): void
	{
		$optionName = 'foo_bar_float';

		add_option($optionName, $value);

		$option = new Option($this->hook, null, 1);
		$option->setSchema([$optionName => ['type' => 'float']]);
		$option->register();

		$this->expectException(TypeError::class);

		get_option($optionName);
	}

	/**
	 * @dataProvider dataTypeFloatInvalid
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testAddTypeFloatInvalid($value): void
	{
		$optionName = 'foo_bar_float';

		$option = new Option($this->hook, null, 1);
		$option->setSchema([$optionName => ['type' => 'float']]);
		$option->register();

		$this->expectException(TypeError::class);

		add_option($optionName, $value);
	}

	/**
	 * @dataProvider dataTypeFloatInvalid
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testUpdateTypeFloatInvalid($value): void
	{
		$optionName = 'foo_bar_float';

		add_option($optionName, 12.0);

		$option = new Option($this->hook, null, 1);
		$option->setSchema([$optionName => ['type' => 'float']]);
		$option->register();

		$this->expectException(TypeError::class);

		update_option($optionName, $value);
	}

	/**
	 * @dataProvider dataTypeArray
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testGetTypeArray($value, array $expect): void
	{
		$optionName = 'foo_bar_array';

		add_option($optionName, $value);

		$option = new Option($this->hook);
		$option->setSchema([$optionName => ['type' => 'array']]);
		$option->register();

		$this->assertSame($expect, get_option($optionName));

		delete_option($optionName);
	}

	/**
	 * @dataProvider dataTypeArrayValid
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testGetTypeArrayValid($value): void
	{
		$optionName = 'foo_bar_array';

		add_option($optionName, $value);

		$option = new Option($this->hook, null, 1);
		$option->setSchema([$optionName => ['type' => 'array']]);
		$option->register();

		$this->assertSame($value, get_option($optionName));

		delete_option($optionName);
	}

	/**
	 * @dataProvider dataTypeArrayValid
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testAddTypeArrayValid($value): void
	{
		$optionName = 'foo_bar_array';

		$option = new Option($this->hook, null, 1);
		$option->setSchema([$optionName => ['type' => 'array']]);
		$option->register();

		add_option($optionName, $value);

		$this->assertSame($value, get_option($optionName));

		delete_option($optionName);
	}

	/**
	 * @dataProvider dataTypeArrayValid
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testUpdateTypeArrayValid($value): void
	{
		$optionName = 'foo_bar_array';

		add_option($optionName, [1]);

		$option = new Option($this->hook, null, 1);
		$option->setSchema([$optionName => ['type' => 'array']]);
		$option->register();

		update_option($optionName, $value);

		$this->assertSame($value, get_option($optionName));

		delete_option($optionName);
	}

	/**
	 * @dataProvider dataTypeArrayInvalid
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testGetTypeArrayInvalid($value): void
	{
		$optionName = 'foo_bar_array';

		add_option($optionName, $value);

		$option = new Option($this->hook, null, 1);
		$option->setSchema([$optionName => ['type' => 'array']]);
		$option->register();

		$this->expectException(TypeError::class);

		get_option($optionName);
	}

	/**
	 * @dataProvider dataTypeArrayInvalid
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testAddTypeArrayInvalid($value): void
	{
		$optionName = 'foo_bar_array';
		$option = new Option($this->hook, null, 1);
		$option->setSchema([$optionName => ['type' => 'array']]);
		$option->register();

		$this->expectException(TypeError::class);

		add_option($optionName, $value);
	}

	/**
	 * @dataProvider dataTypeArrayInvalid
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testUpdateTypeArrayInvalid($value): void
	{
		$optionName = 'foo_bar_array';

		add_option($optionName, ['foo']);

		$option = new Option($this->hook, null, 1);
		$option->setSchema([$optionName => ['type' => 'array']]);
		$option->register();

		$this->expectException(TypeError::class);

		update_option($optionName, $value);
	}

	public function dataHasDefaultSet(): iterable
	{
		yield ['string', 'foo-bar-value-1'];
		yield ['boolean', true];
		yield ['integer', 123];
		yield ['float', 1.23];
		yield ['array', ['foo', 'bar']];
	}

	public function dataTypeString(): iterable
	{
		yield ['this-is-string', 'this-is-string'];
		yield [1, '1'];
		yield [1.2, '1.2'];
		yield [false, ''];
		yield [true, '1'];
		yield [null, ''];
		yield [[], null];
	}

	public function dataTypeStringStrictValid(): iterable
	{
		yield ['this-is-string'];
		yield [''];
		yield [' '];
	}

	public function dataTypeStringStrictInvalid(): iterable
	{
		yield [1];
		yield [1.2];
		yield [false];
		yield [true];
		yield [[]];
	}

	public function dataTypeBoolean(): iterable
	{
		yield ['this-is-string', true];
		yield ['', false];
		yield [0, false];
		yield [1, true];
		yield [1.2, true];
		yield [-1, true]; // -1 is considered true, like any other non-zero (whether negative or positive) number!
		yield [false, false];
		yield [true, true];
		yield [null, false];
		yield [[], false];
		yield ['false', true];
	}

	public function dataTypeBooleanStrictValid(): iterable
	{
		yield [true];
		yield [false];
	}

	public function dataTypeBooleanStrictInvalid(): iterable
	{
		yield ['this-is-string'];
		yield [''];
		yield [0];
		yield [1.2];
		yield [-1];
		yield [null];
		yield [[]];
		yield ['false'];
		yield ['true'];
	}

	public function dataTypeInteger(): iterable
	{
		yield ['this-is-string', 0];
		yield ['', 0];
		yield [0, 0];
		yield [1, 1];
		yield [1.2, 1];
		yield [-1, -1];
		yield [false, 0];
		yield [true, 1];
		yield [null, 0];
		yield [[], null];
	}

	public function dataTypeIntegerValid(): iterable
	{
		yield [1]; // Positive
		yield [-1]; // Negative
		yield [0123]; // Octal
		yield [0x1A]; // Hexadecimal
		yield [0b11111111]; // Binary
		yield [1_234_567];
	}

	public function dataTypeIntegerInvalid(): iterable
	{
		yield ['this-is-string'];
		yield [''];
		yield [1.2];
		yield [false];
		yield [true];
		yield [null];
		yield [[]];
	}

	public function dataTypeFloat(): iterable
	{
		yield ['this-is-string', 0.0];
		yield ['', 0.0];
		yield [0, 0.0];
		yield [1, 1.0];
		yield [1.2, 1.2];
		yield [-1, -1.0];
		yield [false, 0.0];
		yield [true, 1.0];
		yield [null, 0.0];
		yield [[], null];
	}

	public function dataTypeFloatValid(): iterable
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
	}

	public function dataTypeFloatInvalid(): iterable
	{
		yield ['this-is-string'];
		yield [''];
		yield [false];
		yield [true];
		yield [null];
		yield [[]];
	}

	public function dataTypeArray(): iterable
	{
		yield ['this-is-string', ['this-is-string']];
		yield ['', ['']];
		yield [0, [0]];
		yield [1, [1]];
		yield [1.2, [1.2]];
		yield [-1, [-1]];
		yield [false, [false]];
		yield [true, [true]];
		yield [null, ['']];
		yield [[], []];
		yield [['foo' => 'bar'], ['foo' => 'bar']];
		yield [['foo', 'bar'], ['foo', 'bar']];
		yield [['foo', 'bar', 'foo'], ['foo', 'bar', 'foo']];
		yield [['foo' => 'bar', 'bar' => 'foo'], ['foo' => 'bar', 'bar' => 'foo']];
		yield [['foo' => 'bar', 'bar' => 'foo', 'foo' => 'bar'], ['foo' => 'bar', 'bar' => 'foo', 'foo' => 'bar']];
	}

	public function dataTypeArrayValid(): iterable
	{
		yield [[]];
		yield [['foo' => 'bar']];
		yield [['foo', 'bar']];
		yield [['foo', 'bar', 'foo']];
		yield [['foo' => 'bar', 'bar' => 'foo']];
		yield [['foo' => 'bar', 'bar' => 'foo', 'foo' => 'bar']];
	}

	public function dataTypeArrayInvalid(): iterable
	{
		yield ['this-is-string'];
		yield [''];
		yield [0];
		yield [1];
		yield [1.2];
		yield [-1];
		yield [false];
		yield [true];
		yield [null];
	}
}
