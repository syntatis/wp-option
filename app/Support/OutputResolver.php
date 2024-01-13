<?php

declare(strict_types=1);

namespace Syntatis\WP\Option\Support;

use Syntatis\WP\Option\Contracts\Castable;
use Syntatis\WP\Option\Contracts\Resolvable;
use Syntatis\WP\Option\Option;
use Syntatis\WP\Option\Casters\TypeArray;
use Syntatis\WP\Option\Casters\TypeBoolean;
use Syntatis\WP\Option\Casters\TypeFloat;
use Syntatis\WP\Option\Casters\TypeInteger;
use Syntatis\WP\Option\Casters\TypeString;

use function array_key_exists;
use function is_array;

/**
 * @phpstan-import-type OptionType from Option
 *
 * @template T of Castable
 */
class OutputResolver implements Resolvable
{
	protected string $type;

	protected int $strict;

	/**
	 * @var array<string, string>
	 * @phpstan-var array<OptionType, class-string<T>>
	 */
	protected array $casters = [
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
		$value = is_array($value) && array_key_exists('__syntatis', $value) ? $value['__syntatis'] : $value;

		if ($value === null) {
			return $value;
		}

		return isset($this->casters[$this->type]) ?
			(new $this->casters[$this->type]($value))->cast($this->strict) :
			$value;
	}
}
