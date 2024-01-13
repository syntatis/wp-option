<?php

declare(strict_types=1);

namespace Syntatis\WP\Option\Tests\Support;

use Syntatis\WP\Option\Support\InputSanitizer;
use Syntatis\WP\Option\Tests\TestCase;

class InputSanitizerTest extends TestCase
{
	/**
	 * @dataProvider dataSanitize
	 *
	 * @param mixed $value  The value to validate.
	 * @param mixed $expect The type of the value to validate.
	 */
	public function testSanitize($value, $expect): void
	{
		$sanitizer = new InputSanitizer();

		$this->assertSame($expect, $sanitizer->sanitize($value));
	}

	public function dataSanitize(): iterable
	{
		yield 'string' => ['Hello world!', ['__syntatis' => 'Hello world!']];
		yield 'integer' => [1, ['__syntatis' => 1]];
		yield 'float' => [1.23, ['__syntatis' => 1.23]];
		yield 'boolean' => [true, ['__syntatis' => true]];
		yield 'array' => [['foo'], ['__syntatis' => ['foo']]];
		yield 'array+sanitized' => [['__syntatis' => 'bar'], ['__syntatis' => 'bar']];
	}
}
