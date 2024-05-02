<?php

declare(strict_types=1);

namespace Syntatis\WP\Option\Tests;

use Syntatis\WP\Hook\Hook;
use Syntatis\WP\Option\Setting;

class SettingTest extends TestCase
{
	private Hook $hook;

	// phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
	public function set_up(): void
	{
		parent::set_up();

		$this->hook = new Hook();
	}

	public function testDefault(): void
	{
		$optionItem = new Setting('foo');
		$optionItem->hasDefault('bar');

		$this->assertEquals(
			[
				'type' => 'string', // Type automatically inferred from the default.
				'default' => 'bar',
			],
			$optionItem->getArgs(),
		);
	}

	/** @testdox should infer from the default when type is not explicitly set. */
	public function testTypeInferredFromDefault(): void
	{
		$optionItem = new Setting('foo');
		$optionItem->hasDefault(1);

		$this->assertEquals(
			[
				'type' => 'integer',
				'default' => 1, // Type retrieved from the default.
			],
			$optionItem->getArgs(),
		);
	}

	/** @testdox should infer as 'number' when default is set to float. */
	public function testTypeInferredFromFloat(): void
	{
		$optionItem = new Setting('foo');
		$optionItem->hasDefault(1.1);

		$this->assertEquals(
			[
				'type' => 'number',
				'default' => 1.1, // Type retrieved from the default.
			],
			$optionItem->getArgs(),
		);
	}

	/** @testdox should override the inferred type when explicitly set. */
	public function testTypeOverrideInference(): void
	{
		$optionItem = (new Setting('foo'))
			->hasDefault([])
			->hasType('object');

		$this->assertEquals(
			[
				'type' => 'object',
				'default' => [],
			],
			$optionItem->getArgs(),
		);
	}

	/** @testdox should set the type to 'object' when the default is an associative array. */
	public function testTypeAssociativeArray(): void
	{
		$optionItem = new Setting('foo');
		$optionItem->hasDefault(['foo' => 'bar']);

		$this->assertEquals(
			[
				'type' => 'object',
				'default' => ['foo' => 'bar'],
			],
			$optionItem->getArgs(),
		);
	}
}
