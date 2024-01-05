<?php

declare(strict_types=1);

namespace Syntatis\WP\Option\Tests;

use Syntatis\WP\Hook\Hook;
use Syntatis\WP\Option\SiteOption;
use TypeError;

/** @group network */
class SiteOptionTest extends TestCase
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
		$option = new SiteOption($this->hook);
		$option->setSchema([
			$optionName => [
				'type' => $type,
				'default' => $default,
			],
		]);
		$option->register();

		$this->assertSame($default, get_site_option($optionName));
	}

	/**
	 * @dataProvider dataHasDefaultSet
	 *
	 * @param mixed $default The default value to return
	 */
	public function testHasDefaultSetStrictValid(string $type, $default): void
	{
		$optionName = 'foo_bar_default';
		$option = new SiteOption($this->hook, null, 1);
		$option->setSchema([
			$optionName => [
				'type' => $type,
				'default' => $default,
			],
		]);
		$option->register();

		$this->assertSame($default, get_site_option($optionName));
	}

	/**
	 * @dataProvider dataHasDefaultSetInvalid
	 *
	 * @param mixed $default The default value to return
	 */
	public function testHasDefaultSetStrictInvalid(string $type, $default): void
	{
		$optionName = 'foo_bar_default';
		$option = new SiteOption($this->hook, null, 1);
		$option->setSchema([
			$optionName => [
				'type' => $type,
				'default' => $default,
			],
		]);
		$option->register();

		$this->expectException(TypeError::class);

		get_site_option($optionName);
	}

	/**
	 * @dataProvider dataHasNoDefaultSet
	 *
	 * @param mixed $default The default value to return
	 */
	public function testHasNoDefaultSet(string $type): void
	{
		$optionName = 'foo_bar_no_default';
		$option = new SiteOption($this->hook);
		$option->setSchema([$optionName => ['type' => $type]]);
		$option->register();

		$this->assertNull(get_site_option($optionName));
	}

	/**
	 * @dataProvider dataHasDefaultPassed
	 *
	 * @param mixed $default               The default value passed in the schema.
	 * @param mixed $defaultPassed         The default value passed in the function `get_site_option`.
	 * @param mixed $defaultPassedReturned The default value returned or coerced by the function `get_site_option`.
	 */
	public function testHasDefaultPassed(string $type, $default, $defaultPassed, $defaultPassedReturned): void
	{
		$optionName = 'foo_bar_default_passed';
		$option = new SiteOption($this->hook);
		$option->setSchema([
			$optionName => [
				'type' => $type,
				'default' => $default,
			],
		]);
		$option->register();

		$this->assertSame($default, get_site_option($optionName));
		$this->assertSame($defaultPassedReturned, get_site_option($optionName, $defaultPassed));
	}

	/**
	 * @dataProvider dataHasDefaultPassedValid
	 *
	 * @param mixed $default               The default value passed in the schema.
	 * @param mixed $defaultPassed         The default value passed in the function `get_site_option`.
	 * @param mixed $defaultPassedReturned The default value returned or coerced by the function `get_site_option`.
	 */
	public function testHasDefaultPassedStrictValid(string $type, $default, $defaultPassed, $defaultPassedReturned): void
	{
		$optionName = 'foo_bar_default_passed';
		$option = new SiteOption($this->hook, null, 1);
		$option->setSchema([
			$optionName => [
				'type' => $type,
				'default' => $default,
			],
		]);
		$option->register();

		$this->assertSame($default, get_site_option($optionName));
		$this->assertSame($defaultPassedReturned, get_site_option($optionName, $defaultPassed));
	}

	/**
	 * @dataProvider dataHasDefaultPassedInvalid
	 *
	 * @param mixed $default               The default value passed in the schema.
	 * @param mixed $defaultPassed         The default value passed in the function `get_site_option`.
	 * @param mixed $defaultPassedReturned The default value returned or coerced by the function `get_site_option`.
	 */
	public function testHasDefaultPassedStrictInvalid(string $type, $default, $defaultPassed, $defaultPassedReturned): void
	{
		$optionName = 'foo_bar_default_passed';
		$option = new SiteOption($this->hook, null, 1);
		$option->setSchema([
			$optionName => [
				'type' => $type,
				'default' => $default,
			],
		]);
		$option->register();

		$this->assertSame($default, get_site_option($optionName));

		$this->expectException(TypeError::class);

		get_site_option($optionName, $defaultPassed);
	}

	public function testHasPrefix(): void
	{
		$optionName = 'foo_bar_prefix';
		$option = new SiteOption($this->hook, 'syntatis_');
		$option->setSchema([$optionName => ['type' => 'string']]);

		$this->assertFalse(has_filter('default_site_option_syntatis_' . $optionName));
		$this->assertFalse(has_filter('site_option_syntatis_' . $optionName));

		$option->register();

		$this->assertTrue(has_filter('default_site_option_syntatis_' . $optionName));
		$this->assertTrue(has_filter('site_option_syntatis_' . $optionName));

		add_option('syntatis_' . $optionName, 'Hello world!');

		$this->assertSame('Hello world!', get_option('syntatis_' . $optionName));
	}

	public function dataHasDefaultSet(): iterable
	{
		yield ['string', 'foo-bar-value-1'];
		yield ['boolean', true];
		yield ['boolean', false];
		yield ['integer', 123];
		yield ['float', 1.23];
		yield ['array', ['foo', 'bar']];
	}

	public function dataHasDefaultSetInvalid(): iterable
	{
		yield ['string', true];
		yield ['boolean', 'true'];
		yield ['integer', ['foo']];
		yield ['float', '1.23'];
		yield ['array', false];
	}

	public function dataHasNoDefaultSet(): iterable
	{
		yield ['string'];
		yield ['boolean'];
		yield ['integer'];
		yield ['float'];
		yield ['array'];
	}

	/**
	 * Non-strict. Value may be coerced.
	 */
	public function dataHasDefaultPassed(): iterable
	{
		yield ['string', 'Hello World', 123, '123'];
		yield ['boolean', false, 'true', true];
		yield ['integer', 1, '2', 2];
		yield ['float', 1.2, '2.5', 2.5];
		yield ['array', ['foo'], 'bar', ['bar']];
	}

	public function dataHasDefaultPassedValid(): iterable
	{
		yield ['string', 'Hello World', '123', '123'];
		yield ['boolean', false, true, true];
		yield ['integer', 1, 2, 2];
		yield ['float', 1.2, 2.5, 2.5];
		yield ['array', ['foo'], ['bar'], ['bar']];
	}

	public function dataHasDefaultPassedInvalid(): iterable
	{
		yield ['string', 'Hello World', 123, '123'];
		yield ['boolean', true, '0', false];
		yield ['integer', 1, '2', 2];
		yield ['float', 1.2, '2.5', 2.5];
		yield ['array', ['foo'], 'bar', ['bar']];
	}
}
