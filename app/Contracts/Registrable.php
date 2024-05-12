<?php

declare(strict_types=1);

namespace Syntatis\WPOption\Contracts;

interface Registrable
{
	public function register(): void;

	public function deregister(): void;
}
