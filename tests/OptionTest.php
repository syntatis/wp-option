<?php

declare(strict_types=1);

namespace Syntatis\WP\Option\Tests;

use Syntatis\WP\Option\Option;

class OptionTest extends TestCase
{
	/** @testdox should return the name */
	public function testName(): void
	{
		$option = new Option('foo');

		$this->assertEquals(
			'foo',
			$option->getName(),
		);
	}

	/** @testdox should return the constraints */
	public function testConstraints(): void
	{
		$option = new Option('foo');
		$option = $option->constrained('is_string');

		$this->assertEquals(
			'is_string',
			$option->getConstraints(),
		);

		$option = new Option('foo');
		$option = $option->constrained(['is_string', 'is_numeric']);

		$this->assertEquals(
			['is_string', 'is_numeric'],
			$option->getConstraints(),
		);
	}

	/** @testdox should return the default value set */
	public function testSettingArgsDefault(): void
	{
		$option = new Option('foo');
		$option->defaultTo('bar');

		$this->assertEquals(
			[
				'type' => 'string',
				'default' => 'bar',
			],
			$option->getSettingArgs(),
		);
	}

	/** @testdox should infer as 'integer' when the default is a string. */
	public function testSettingArgsTypeInferredFromString(): void
	{
		$option = new Option('foo');
		$option->defaultTo('Hello World!');

		$this->assertEquals(
			[
				'type' => 'string',
				'default' => 'Hello World!',
			],
			$option->getSettingArgs(),
		);
	}

	/** @testdox should infer as 'integer' when the default is an integer. */
	public function testSettingArgsTypeInferredFromInteger(): void
	{
		$option = new Option('foo');
		$option->defaultTo(1);

		$this->assertEquals(
			[
				'type' => 'integer',
				'default' => 1,
			],
			$option->getSettingArgs(),
		);
	}

	/** @testdox should infer as 'integer' when the default is an integer. */
	public function testSettingArgsTypeInferredFromBoolean(): void
	{
		$option = new Option('foo');
		$option->defaultTo(false);

		$this->assertEquals(
			[
				'type' => 'boolean',
				'default' => false,
			],
			$option->getSettingArgs(),
		);
	}

	/** @testdox should infer as 'number' when the default is a float. */
	public function testSettingArgsTypeInferredFromFloat(): void
	{
		$option = new Option('foo');
		$option->defaultTo(1.1);

		$this->assertEquals(
			[
				'type' => 'number',
				'default' => 1.1,
			],
			$option->getSettingArgs(),
		);
	}

	/** @testdox should infer as 'array' when the default is an array. */
	public function testSettingArgsTypeArray(): void
	{
		$option = new Option('foo');
		$option->defaultTo([]);

		$this->assertEquals(
			[
				'type' => 'array',
				'default' => [],
			],
			$option->getSettingArgs(),
		);
	}

	/** @testdox should infer as 'object' when the default is an associative array. */
	public function testSettingArgsTypeAssociativeArray(): void
	{
		$option = new Option('foo');
		$option->defaultTo(['foo' => 'bar']);

		$this->assertEquals(
			[
				'type' => 'object',
				'default' => ['foo' => 'bar'],
			],
			$option->getSettingArgs(),
		);
	}

	/** @testdox should override the inferred type when the type is set explicitly. */
	public function testSettingArgsTypeOverrideInference(): void
	{
		$option = new Option('foo');
		$option->defaultTo([]); // An empty array will by default be inferred as an array.
		$option->typedAs('object'); // But the expected value is an object.

		$this->assertEquals(
			[
				'type' => 'object',
				'default' => [],
			],
			$option->getSettingArgs(),
		);
	}

	/** @testdox should not infer type when the default is `null`. */
	public function testSettingArgsTypeNullDefault(): void
	{
		$option = new Option('foo');
		$option->defaultTo(null);

		$this->assertEquals(
			['default' => null],
			$option->getSettingArgs(),
		);
	}

	/**
	 * @dataProvider dataSettingArgsTypeRESTConfig
	 * @testdox should override the inferred type when the type is set explicitly.
	 *
	 * @param mixed $shouldrest
	 */
	public function testSettingArgsTypeRESTConfig($shouldrest): void
	{
		$option = new Option('foo');
		$option->shouldREST($shouldrest);
		$expected = $shouldrest;

		$this->assertEquals(
			$expected,
			$option->getSettingArgs()['show_in_rest'],
		);
	}

	public function dataSettingArgsTypeRESTConfig(): iterable
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
