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
		delete_option($this->optionName);

		parent::tear_down();
	}

	/**
	 * @dataProvider dataNoDefaultSet
	 * @group test-here
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
	 * @group test-here
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
	 * @group test-here
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
	 * @group test-here
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
	 * @group test-here
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
	 * @group test-here
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
	 * @group test-here
	 *
	 * @param mixed $default               The default value passed in the schema.
	 * @param mixed $defaultPassed         The default value passed in the function `get_site_option`.
	 * @param mixed $defaultPassedReturned The default value returned or coerced by the function `get_site_option`.
	 */
	public function testHasDefaultPassedStrictInvalid(string $type, $default, $defaultPassed): void
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
	 * @dataProvider dataHasPrefix
	 *
	 * @param string $type          The default value passed in the schema.
	 * @param mixed  $value         The value to add with `add_site_option`.
	 * @param mixed  $defaultPassed The value to set ad the default when calling `get_site_option`.
	 */
	public function testHasPrefix(string $type, $value, $defaultPassed): void
	{
		$optionName = 'foo_bar_prefix';
		$option = new SiteOption($this->hook, 'syntatis_');
		$option->setSchema([$optionName => ['type' => $type]]);

		$this->assertFalse(has_filter('default_site_option_syntatis_' . $optionName));
		$this->assertFalse(has_filter('site_option_syntatis_' . $optionName));

		$option->register();

		$this->assertTrue(has_filter('default_site_option_syntatis_' . $optionName));
		$this->assertTrue(has_filter('site_option_syntatis_' . $optionName));

		$this->assertTrue(add_site_option('syntatis_' . $optionName, $value));
		$this->assertSame($value, get_site_option('syntatis_' . $optionName));
		$this->assertSame($value, get_site_option('syntatis_' . $optionName, $defaultPassed));
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

		add_site_option($optionName, $value);

		$option = new SiteOption($this->hook);
		$option->setSchema([$optionName => ['type' => 'string']]);
		$option->register();

		$this->assertSame($expect, get_site_option($optionName));

		delete_site_option($optionName);
	}

	/**
	 * @dataProvider dataTypeStringStrictValid
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testGetTypeStringStrictValid($value): void
	{
		$optionName = 'foo_bar_string';

		add_site_option($optionName, $value);

		$option = new SiteOption($this->hook, null, 1);
		$option->setSchema([$optionName => ['type' => 'string']]);
		$option->register();

		$this->assertSame($value, get_site_option($optionName));

		delete_site_option($optionName);
	}

	/**
	 * @dataProvider dataTypeStringStrictInvalid
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testGetTypeStringStrictInvalid($value): void
	{
		$optionName = 'foo_bar_string';

		add_site_option($optionName, $value);

		$option = new SiteOption($this->hook, null, 1);
		$option->setSchema([$optionName => ['type' => 'string']]);
		$option->register();

		$this->expectException(TypeError::class);

		get_site_option($optionName);
	}

	/**
	 * @dataProvider dataTypeBoolean
	 *
	 * @param mixed $value  The value to add in the option.
	 * @param mixed $expect The expected value to be returned.
	 */
	public function testGetTypeBoolean($value, $expect): void
	{
		$optionName = 'foo_bar_boolean';

		add_site_option($optionName, $value);

		$option = new SiteOption($this->hook);
		$option->setSchema([$optionName => ['type' => 'boolean']]);
		$option->register();

		$this->assertSame($expect, get_site_option($optionName));

		delete_site_option($optionName);
	}

	/**
	 * @dataProvider dataTypeBooleanStrictValid
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testGetTypeBooleanStrictValid($value): void
	{
		$optionName = 'foo_bar_boolean';

		add_site_option($optionName, $value);

		$option = new SiteOption($this->hook, null, 1);
		$option->setSchema([$optionName => ['type' => 'boolean']]);
		$option->register();

		$this->assertSame($value, get_site_option($optionName));

		delete_site_option($optionName);
	}

	/**
	 * @dataProvider dataTypeBooleanStrictInvalid
	 *
	 * @param mixed $value The value to add in the option.
	 */
	public function testGetTypeBooleanStrictInvalid($value): void
	{
		$optionName = 'foo_bar_boolean';

		add_site_option($optionName, $value);

		$option = new SiteOption($this->hook, null, 1);
		$option->setSchema([$optionName => ['type' => 'boolean']]);
		$option->register();

		$this->expectException(TypeError::class);

		get_site_option($optionName);
	}

	public function dataHasPrefix(): iterable
	{
		yield ['string', 'Hello World!', 12];
		yield ['boolean', false, 'true'];
		yield ['integer', 1, true];
	}

	/**
	 * Non-strict. Value may be coerced.
	 */
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
		yield [true];
		yield [[]];

		/**
		 * WordPress will convert to empty string.
		 *
		 * @todo Handle this case.
		 */
		// yield [false];
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

		/**
		 * WordPress will convert to empty string.
		 *
		 * @todo Handle this case.
		 */
		// yield [false];
	}

	public function dataTypeBooleanStrictInvalid(): iterable
	{
		yield ['this-is-string'];
		yield [''];
		yield [0];
		yield [1.2];
		yield [-1];
		yield [false];
		yield [[]];
		yield ['false'];
		yield ['true'];
	}
}
