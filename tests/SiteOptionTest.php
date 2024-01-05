<?php

declare(strict_types=1);

namespace Syntatis\WP\Option\Tests;

use Syntatis\WP\Hook\Hook;
use Syntatis\WP\Option\SiteOption;

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

	public function dataHasDefaultSet(): iterable
	{
		yield ['string', 'foo-bar-value-1'];
		yield ['boolean', true];
		yield ['boolean', false];
		yield ['integer', 123];
		yield ['float', 1.23];
		yield ['array', ['foo', 'bar']];
	}

	public function dataHasNoDefaultSet(): iterable
	{
		yield ['string'];
		yield ['boolean'];
		yield ['integer'];
		yield ['float'];
		yield ['array'];
	}
}
