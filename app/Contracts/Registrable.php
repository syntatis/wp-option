<?php

declare(strict_types=1);

namespace Syntatis\WP\Option\Contracts;

interface Registrable
{
	public function register(): void;
}
