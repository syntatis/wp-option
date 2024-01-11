<?php

declare(strict_types=1);

namespace Syntatis\WP\Option\Support;

use Syntatis\WP\Option\Option;

/** @phpstan-import-type OptionType from Option */
class InputSanitizer
{
	/** @phpstan-var OptionType */
	private string $type;

	/** @phpstan-param OptionType $type */
	public function __construct(string $type)
	{
		$this->type = $type;
	}

	/**
	 * @param mixed $value
	 * @return array{__syntatis: mixed}
	 */
	public function sanitize($value): array
	{
		return ['__syntatis' => $value];
	}
}
