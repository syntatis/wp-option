<?php

declare(strict_types=1);

namespace Syntatis\WP\Option;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Syntatis\WP\Hook\Contract\WithHook;
use Syntatis\WP\Hook\Hook;
use Syntatis\WP\Option\Support\InputSanitizer;
use Syntatis\WP\Option\Support\InputValidator;
use Syntatis\WP\Option\Support\OutputResolver;

/**
 * @phpstan-type OptionType 'array'|'boolean'|'float'|'integer'|'string'
 * @phpstan-type OptionConstraints callable|array<callable>|Constraint|ValidatorInterface|null
 * @phpstan-type OptionSchema array{type: OptionType, description?: string, default?: mixed, priority?: int, constraints?: OptionConstraints}
 */
class Options implements WithHook
{
	private int $priority = 99;

	private int $strict = 0;

	private Hook $hook;

	private ?string $prefix;

	/** @var array<Setting> */
	private array $settings = [];

	/**
	 * @param string|null $prefix The option prefix to apply to all the option anme registered in the schema e.g. 'my_plugin_'.
	 * @param int         $strict The level of strictness to apply to the option values.
	 */
	public function __construct(?string $prefix = null, int $strict = 0)
	{
		$this->prefix = $prefix;
		$this->strict = $strict;
	}

	public function hook(Hook $hook): void
	{
		$this->hook = $hook;
	}

	public function addSettings(Setting ...$settings): void
	{
		// WIP.
	}

	public function register(string $optionGroup = 'options'): void
	{
		foreach ($this->schema as $optionName => $schema) {
			$optionName = $this->prefix . $optionName;
			$optionType = $schema['type'];
			$optionDefault = $schema['default'] ?? null;
			$optionPriority = $schema['priority'] ?? $this->priority;

			$inputSanitizer = new InputSanitizer();
			$outputResolver = new OutputResolver($optionType, $this->strict);

			register_setting($optionGroup, $optionName, [
				'type' => $optionType,
				'default' => $optionDefault,
				'sanitize_callback' => static fn ($value) => $inputSanitizer->sanitize($value),
				'show_in_rest' => true,
			]);

			$this->hook->addFilter(
				'option_' . $optionName,
				static fn ($value) => $outputResolver->resolve($value),
				$optionPriority,
			);

			if ($this->strict !== 1) {
				continue;
			}

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

		$this->hook->run();
	}
}
