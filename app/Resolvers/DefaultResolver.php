<?php

declare(strict_types=1);

namespace Syntatis\WP\Option\Resolvers;

class DefaultResolver extends OutputResolver
{
	/**
	 * Resolve the value passed into the select type.
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	public function resolve($value)
	{
		if ($value === null) {
			return $value;
		}

		return parent::resolve($value);
	}
}
