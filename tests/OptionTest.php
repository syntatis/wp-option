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

	private string $optionName = 'foo_bar';

	// phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
	public function set_up(): void
	{
		parent::set_up();

		$this->hook = new Hook();
	}

	// phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
	public function tear_dowm(): void
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
	 * @dataProvider dataDefaultSetInvalid
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

	public function dataDefaultSetInvalid(): iterable
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
	 * @dataProvider dataHasDefaultPassedStrictValid
	 *
	 * @param mixed $default               The default value passed in the schema.
	 * @param mixed $defaultPassed         The default value passed in the function `get_site_option`.
	 * @param mixed $defaultPassedReturned The default value returned or coerced by the function `get_site_option`.
	 */
	public function testHasDefaultPassedStrictValid(string $type, $default, $defaultPassed): void
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

	/**
	 * @dataProvider dataDefaultPassedStrictInvalid
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

		add_option('syntatis_' . $this->optionName, $value);

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

	public function dataHasDefaultPassedStrictValid(): iterable
	{
		yield ['string', 'Hello World', '123'];
		yield ['boolean', true, null];
		yield ['integer', 1, 2];
		yield ['float', 1.2, 2.5];
		yield ['array', ['foo'], ['bar']];
	}

	/**
	 * Non-strict. Value may be coerced.
	 */
	public function dataTypeString(): iterable
	{
		yield ['Hello World!', 'Hello World!'];
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

	/**
	 * Non-strict. Value may be coerced.
	 */
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
		yield [[]];
		yield ['false'];
		yield ['true'];
	}

	/**
	 * Non-strict. Value may be coerced.
	 */
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

	/**
	 * Non-strict. Value may be coerced.
	 */
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

	/**
	 * Non-strict. Value may be coerced.
	 */
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
