<?php

declare(strict_types=1);

namespace Syntatis\WP\Option\Tests;

use Syntatis\WP\Option\Option;

class OptionTest extends TestCase
{
	/** @testdox should return the name */
	public function testName(): void
	{
		$option = new Option('foo', 'string');

		$this->assertEquals('foo', $option->getName());
	}

	/** @testdox should set and return the constraints */
	public function testConstraints(): void
	{
		$option = new Option('foo', 'string');
		$option = $option->setConstraints('is_string');

		$this->assertEquals('is_string', $option->getConstraints());

		$option = new Option('foo', 'string');
		$option = $option->setConstraints(['is_string', 'is_numeric']);

		$this->assertEquals(
			['is_string', 'is_numeric'],
			$option->getConstraints(),
		);
	}

	/** @testdox should set and return the priority */
	public function testPriority(): void
	{
		$option = new Option('foo', 'string');

		$this->assertSame(99, $option->getPriority());

		$option = $option->setPriority(100);

		$this->assertSame(100, $option->getPriority());
	}

	/** @testdox should set and return the default value set */
	public function testSettingArgsDefault(): void
	{
		$option = new Option('foo', 'string');
		$option->setDefault('bar');

		$this->assertEquals(
			[
				'type' => 'string',
				'default' => 'bar',
			],
			$option->getSettingArgs(),
		);
	}

	/** @testdox should set and return the description */
	public function testSettingArgsDescription(): void
	{
		$option = new Option('foo', 'string');
		$option->setDescription('This is the description');

		$this->assertEquals(
			[
				'type' => 'string',
				'description' => 'This is the description',
			],
			$option->getSettingArgs(),
		);
	}

	/**
	 * @dataProvider dataSettingArgsTypeAPIConfig
	 * @testdox should override the inferred type when the type is set explicitly.
	 *
	 * @param mixed $config
	 */
	public function testSettingArgsTypeAPIConfig($config): void
	{
		$option = new Option('foo', 'string');
		$option->apiConfig($config);

		$this->assertEquals(
			$config,
			$option->getSettingArgs()['show_in_rest'],
		);
	}

	public function dataSettingArgsTypeAPIConfig(): iterable
	{
		yield [true];
		yield [false];
		yield [
			[
				'schema' => [
					'type'  => 'array',
					'items' => [
						'type'  => 'array',
						'items' => [
							'type'   => 'string',
							'format' => 'hex-color',
						],
					],
				],
			],
		];
	}
}
