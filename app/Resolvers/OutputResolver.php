<?php

declare(strict_types=1);

namespace Syntatis\WP\Option\Resolvers;

use Syntatis\WP\Option\Contracts\Castable;
use Syntatis\WP\Option\Contracts\Resolvable;
use Syntatis\WP\Option\Option;
use Syntatis\WP\Option\TypeCasters\TypeArray;
use Syntatis\WP\Option\TypeCasters\TypeBoolean;
use Syntatis\WP\Option\TypeCasters\TypeFloat;
use Syntatis\WP\Option\TypeCasters\TypeInteger;
use Syntatis\WP\Option\TypeCasters\TypeString;

/**
 * @phpstan-import-type OptionType from Option
 *
 * @template T of Castable
 */
class OutputResolver implements Resolvable
{
	private string $type;

	private int $strict;

	/**
	 * @var array<string, string>
	 * @phpstan-var array<OptionType, class-string<T>>
	 */
	private array $casters = [
		'array' => TypeArray::class,
		'boolean' => TypeBoolean::class,
		'float' => TypeFloat::class,
		'integer' => TypeInteger::class,
		'string' => TypeString::class,
	];

	/** @phpstan-param OptionType $type */
	public function __construct(string $type, int $strict = 0)
	{
		$this->type = $type;
		$this->strict = $strict;
	}

	/**
	 * Resolve the value passed into the select type.
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	public function resolve($value)
	{
		return isset($this->casters[$this->type]) ?
			(new $this->casters[$this->type]($value))->cast($this->strict) :
			$value;
	}
}
