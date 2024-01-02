<?php

declare(strict_types=1);

namespace Syntatis\WP\Option;

use Syntatis\WP\Hook\Hook;
use Syntatis\WP\Option\Resolvers\DefaultResolver;
use Syntatis\WP\Option\Resolvers\InputValidator;
use Syntatis\WP\Option\Resolvers\OutputResolver;

use function array_merge;

/**
 * @phpstan-type OptionType value-of<Option::TYPES>
 * @phpstan-type OptionSchema array{type: OptionType, default?: mixed, priority?: int}
 */
final class Option
{
	public const TYPES = [
		'string',
		'boolean',
		'integer',
		'float',
		'array',
	];

	private int $priority = 99;

	private int $strict = 0;

	private Hook $hook;

	private ?string $prefix;

	/** @phpstan-var array<string, OptionSchema> */
	private array $schema = [];

	/**
	 * @param string|null $prefix The option prefix to apply to all the option anme registered in the schema e.g. 'my_plugin_'.
	 * @param int         $strict The level of strictness to apply to the option values.
	 */
	public function __construct(Hook $hook, ?string $prefix = null, int $strict = 0)
	{
		$this->hook = $hook;
		$this->prefix = $prefix;
		$this->strict = $strict;
	}

	/** @phpstan-param array<string, OptionSchema> $schema */
	public function setSchema(array $schema): void
	{
		$this->schema = array_merge($this->schema, $schema);
	}

	/** @phpstan-return array<string, OptionSchema> */
	public function getSchema(): array
	{
		return $this->schema;
	}

	public function register(): void
	{
		foreach ($this->schema as $optionName => $schema) {
			$optionName = $this->prefix . $optionName;
			$optionType = $schema['type'];
			$optionDefault = $schema['default'] ?? null;
			$optionPriority = $schema['priority'] ?? $this->priority;

			$outputResolver = new OutputResolver($optionType, $this->strict);
			$defaultResolver = new DefaultResolver($optionType, $this->strict);

			if ($this->strict === 1) {
				$inputValidator = new InputValidator($optionType);

				$this->hook->addAction(
					'add_option',
					static fn ($name, $value) => $inputValidator->validate($value),
					$optionPriority,
					2,
				);
				$this->hook->addAction(
					'update_option',
					static fn ($name, $oldValue, $newValue) => $inputValidator->validate($newValue),
					$optionPriority,
					3,
				);
			}

			$this->hook->addFilter(
				'default_option_' . $optionName,
				static function ($default, $option, $passedDefault) use ($optionDefault, $defaultResolver) {
					$value = $passedDefault ? $default : $optionDefault;

					return $defaultResolver->resolve($optionDefault);
				},
				$optionPriority,
				3,
			);

			$this->hook->addFilter(
				'option_' . $optionName,
				static function ($value) use ($outputResolver) {
					return $outputResolver->resolve($value);
				},
				$optionPriority,
			);
		}

		$this->hook->run();
	}
}
