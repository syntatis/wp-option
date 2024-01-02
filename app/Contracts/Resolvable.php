<?php

declare(strict_types=1);

namespace Syntatis\WP\Option\Contracts;

interface Resolvable
{
	/**
	 * @param mixed $value The value to resolve.
	 * @return mixed Return the value resolved.
	 */
	public function resolve($value);
}
