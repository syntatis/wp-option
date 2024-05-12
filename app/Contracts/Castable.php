<?php

declare(strict_types=1);

namespace Syntatis\WPOption\Contracts;

interface Castable
{
	/** @return mixed Return the value resolved. */
	public function cast(int $strict);
}
