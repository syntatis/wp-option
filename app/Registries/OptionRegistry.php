<?php

declare(strict_types=1);

namespace Syntatis\WP\Option\Registries;

use InvalidArgumentException;
use Syntatis\WP\Hook\Contract\WithHook;
use Syntatis\WP\Hook\Hook;
use Syntatis\WP\Option\Contracts\Registrable;
use Syntatis\WP\Option\Option;
use Syntatis\WP\Option\Support\InputSanitizer;
use Syntatis\WP\Option\Support\InputValidator;
use Syntatis\WP\Option\Support\OutputResolver;

use function array_merge;
use function Syntatis\Utils\is_blank;
use function trim;

class OptionRegistry implements Registrable, WithHook
{
	private Hook $hook;

	private Option $option;

	private int $strict;

	private ?string $optionName = null;

	private ?string $optionGroup = null;

	public function __construct(Option $option, int $strict = 0)
	{
		$this->option = $option;
		$this->optionName = $option->getName();
		$this->strict = $strict;
	}

	public function setOptionGroup(?string $optionGroup = null): void
	{
		$this->optionGroup = $optionGroup;
	}

	public function setPrefix(string $prefix = ''): void
	{
		$this->optionName = trim($prefix) . $this->optionName;
	}

	public function hook(Hook $hook): void
	{
		$this->hook = $hook;
	}

	public function register(): void
	{
		if (is_blank($this->optionName)) {
			throw new InvalidArgumentException('Unable to register an option without a name.');
		}

		$settingArgs = $this->option->getSettingArgs();

		if (! isset($settingArgs['type']) || is_blank($settingArgs['type'])) {
			throw new InvalidArgumentException('Unable to determine the "type" for ' . $this->option->getName() . ' option.');
		}

		$optionType = $settingArgs['type'];
		$optionDefault = $settingArgs['default'] ?? null;
		$optionPriority = $this->option->getPriority();

		$inputSanitizer = new InputSanitizer();
		$outputResolver = new OutputResolver($optionType, $this->strict);

		$this->hook->addFilter(
			'default_option_' . $this->optionName,
			static fn ($default, $option, $passedDefault) => $outputResolver->resolve($passedDefault ? $default : $optionDefault),
			$optionPriority,
			3,
		);

		$this->hook->addFilter(
			'option_' . $this->optionName,
			static fn ($value) => $outputResolver->resolve($value),
			$optionPriority,
		);

		if ($this->optionGroup) {
			register_setting(
				$this->optionGroup,
				$this->optionName,
				array_merge(
					$settingArgs,
					[
						'sanitize_callback' => static fn ($value) => $inputSanitizer->sanitize($value),
					],
				),
			);
		} else {
			$this->hook->addFilter(
				'sanitize_option_' . $this->optionName,
				static fn ($value) => $inputSanitizer->sanitize($value),
				$optionPriority,
			);
		}

		if ($this->strict !== 1) {
			return;
		}

		$inputValidator = new InputValidator($optionType, $this->option->getConstraints());

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
}
