<?php

declare(strict_types=1);

namespace Syntatis\WP\Option;

use Syntatis\WP\Hook\Hook;
use Syntatis\WP\Option\Support\InputSanitizer;
use Syntatis\WP\Option\Support\InputValidator;
use Syntatis\WP\Option\Support\OutputResolver;

use function array_merge;

/**
 * @phpstan-type OptionType 'array'|'boolean'|'float'|'integer'|'string'
 * @phpstan-type OptionSchema array{type: OptionType, default?: mixed, priority?: int, constraints?: array<callable>|callable}
 */
class Option
{
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

			$inputSanitizer = new InputSanitizer();
			$outputResolver = new OutputResolver($optionType, $this->strict);

			if ($this->strict === 1) {
				$inputValidator = new InputValidator($optionType, $schema['constraints'] ?? []);

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
				'sanitize_option_' . $optionName,
				static fn ($value, $option, $originalValue) => $inputSanitizer->sanitize($originalValue),
				$optionPriority,
				3,
			);

			$this->hook->addFilter(
				'default_option_' . $optionName,
				static fn ($default, $option, $passedDefault) => $outputResolver->resolve($passedDefault ? $default : $optionDefault),
				$optionPriority,
				3,
			);

			$this->hook->addFilter(
				'option_' . $optionName,
				static fn ($value) => $outputResolver->resolve($value),
				$optionPriority,
			);
		}

		$this->hook->run();
	}
}
