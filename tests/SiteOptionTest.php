<?php

declare(strict_types=1);

namespace Syntatis\WP\Option\Tests;

use Syntatis\WP\Hook\Hook;
use Syntatis\WP\Option\SiteOption;
use TypeError;

/** @group site-option */
class SiteOptionTest extends TestCase
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
		delete_site_option($this->optionName);

		parent::tear_down();
	}

	/**
	 * @dataProvider dataNoDefaultSet
	 *
	 * @param mixed $default The default value to return
	 */
	public function testNoDefaultSet(string $type): void
	{
		$option = new SiteOption($this->hook);
		$option->setSchema([$this->optionName => ['type' => $type]]);
		$option->register();

		$this->assertNull(get_site_option($this->optionName));
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
	 *
	 * @param mixed $default         The default value to return.
	 * @param mixed $defaultReturned The default value returned or coerced by the function `get_site_option`.
	 */
	public function testDefaultSet(string $type, $default, $defaultReturned): void
	{
		$option = new SiteOption($this->hook);
		$option->setSchema([
			$this->optionName => [
				'type' => $type,
				'default' => $default,
			],
		]);
		$option->register();

		$this->assertSame($defaultReturned, get_site_option($this->optionName));
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
	 *
	 * @param mixed $default The default value to return
	 */
	public function testDefaultSetStrictValid(string $type, $default): void
	{
		$option = new SiteOption($this->hook, null, 1);
		$option->setSchema([
			$this->optionName => [
				'type' => $type,
				'default' => $default,
			],
		]);
		$option->register();

		$this->assertSame($default, get_site_option($this->optionName));
	}

	public function dataDefaultSetStrictValid(): iterable
	{
		yield ['string', 'Hello world!'];
		yield ['boolean', true];
		yield ['boolean', false];
		yield ['integer', 123];
		yield ['float', 1.23];
		yield ['array', ['foo', 'bar']];
	}

	/**
	 * @dataProvider dataDefaultSetStrictInvalid
	 *
	 * @param mixed $default The default value to return
	 */
	public function testDefaultSetStrictInvalid(string $type, $default): void
	{
		$option = new SiteOption($this->hook, null, 1);
		$option->setSchema([
			$this->optionName => [
				'type' => $type,
				'default' => $default,
			],
		]);
		$option->register();

		$this->expectException(TypeError::class);

		get_site_option($this->optionName);
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
		$option = new SiteOption($this->hook);
		$option->setSchema([
			$this->optionName => [
				'type' => $type,
				'default' => $default,
			],
		]);
		$option->register();

		$this->assertSame($default, get_site_option($this->optionName));
		$this->assertSame($defaultPassedReturned, get_site_option($this->optionName, $defaultPassed));
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
	 *
	 * @param mixed $default       The default value passed in the schema.
	 * @param mixed $defaultPassed The default value passed in the function `get_site_option`.
	 */
	public function testDefaultPassedStrictValid(string $type, $default, $defaultPassed): void
	{
		$option = new SiteOption($this->hook, null, 1);
		$option->setSchema([
			$this->optionName => [
				'type' => $type,
				'default' => $default,
			],
		]);
		$option->register();

		$this->assertSame($default, get_site_option($this->optionName));
		$this->assertSame($defaultPassed, get_site_option($this->optionName, $defaultPassed));
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
	 *
	 * @param mixed $default               The default value passed in the schema.
	 * @param mixed $defaultPassed         The default value passed in the function `get_site_option`.
	 * @param mixed $defaultPassedReturned The default value returned or coerced by the function `get_site_option`.
	 */
	public function testDefaultPassedStrictInvalid(string $type, $default, $defaultPassed): void
	{
		$option = new SiteOption($this->hook, null, 1);
		$option->setSchema([
			$this->optionName => [
				'type' => $type,
				'default' => $default,
			],
		]);
		$option->register();

		$this->assertSame($default, get_site_option($this->optionName));

		$this->expectException(TypeError::class);

		get_site_option($this->optionName, $defaultPassed);
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
		$option = new SiteOption($this->hook, 'syntatis_');
		$option->setSchema([$this->optionName => ['type' => $type]]);

		$this->assertFalse(has_filter('default_site_option_syntatis_' . $this->optionName));
		$this->assertFalse(has_filter('site_option_syntatis_' . $this->optionName));

		$option->register();

		$this->assertTrue(has_filter('default_site_option_syntatis_' . $this->optionName));
		$this->assertTrue(has_filter('site_option_syntatis_' . $this->optionName));

		$this->assertTrue(add_site_option('syntatis_' . $this->optionName, $value));
		$this->assertSame($value, get_site_option('syntatis_' . $this->optionName));
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
		add_site_option($this->optionName, ['__syntatis' => $value]);

		$option = new SiteOption($this->hook);
		$option->setSchema([$this->optionName => ['type' => 'string']]);
		$option->register();

		$this->assertSame($expect, get_site_option($this->optionName));
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
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testGetTypeStringStrictValid($value): void
	{
		add_site_option($this->optionName, ['__syntatis' => $value]);

		$option = new SiteOption($this->hook, null, 1);
		$option->setSchema([$this->optionName => ['type' => 'string']]);
		$option->register();

		$this->assertSame($value, get_site_option($this->optionName));
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
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testGetTypeStringStrictInvalid($value): void
	{
		add_site_option($this->optionName, ['__syntatis' => $value]);

		$option = new SiteOption($this->hook, null, 1);
		$option->setSchema([$this->optionName => ['type' => 'string']]);
		$option->register();

		$this->expectException(TypeError::class);

		get_site_option($this->optionName);
	}

	public function dataTypeStringStrictInvalid(): iterable
	{
		yield [1];
		yield [1.2];
		yield [true];
		yield [[]];
		yield [false];
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
		add_site_option($this->optionName, ['__syntatis' => $value]);

		$option = new SiteOption($this->hook);
		$option->setSchema([$this->optionName => ['type' => 'boolean']]);
		$option->register();

		$this->assertSame($expect, get_site_option($this->optionName));
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
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testGetTypeBooleanStrictValid($value): void
	{
		add_site_option($this->optionName, ['__syntatis' => $value]);

		$option = new SiteOption($this->hook, null, 1);
		$option->setSchema([$this->optionName => ['type' => 'boolean']]);
		$option->register();

		$this->assertSame($value, get_site_option($this->optionName));
	}

	public function dataTypeBooleanStrictValid(): iterable
	{
		yield [true];
		yield [false];
	}

	/**
	 * @dataProvider dataTypeBooleanStrictInvalid
	 * @group type-boolean
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testGetTypeBooleanStrictInvalid($value): void
	{
		add_site_option($this->optionName, $value);

		$option = new SiteOption($this->hook, null, 1);
		$option->setSchema([$this->optionName => ['type' => 'boolean']]);
		$option->register();

		$this->expectException(TypeError::class);

		get_site_option($this->optionName);
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
}
