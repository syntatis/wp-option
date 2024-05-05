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
		yield 'string' => ['Hello world!', 'Hello world!'];
		yield 'integer' => [1, 1];
		yield 'number (float)' => [1.23, 1.23];
		yield 'boolean' => [true, true];
		yield 'array' => [['foo'], ['foo']];
		yield 'null' => [null, ['__syntatis' => null]];
	}
}
