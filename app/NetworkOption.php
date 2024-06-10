<?php

declare(strict_types=1);

namespace Syntatis\WPOption;

use Syntatis\WPOption\Exceptions\NotImplementedException;

class NetworkOption extends Option
{
	/** @inheritDoc */
	public function apiEnabled($value = true): self
	{
		/**
		 * @see https://core.trac.wordpress.org/ticket/37181
		 * @see https://core.trac.wordpress.org/ticket/41459
		 */
		throw new NotImplementedException('NetworkOption does not currently support including a setting in the REST API.');
	}
}
