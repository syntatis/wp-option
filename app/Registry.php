<?php

declare(strict_types=1);

namespace Syntatis\WP\Option;

use InvalidArgumentException;
use Syntatis\WP\Hook\Contract\WithHook;
use Syntatis\WP\Hook\Hook;
use Syntatis\WP\Option\Support\InputSanitizer;
use Syntatis\WP\Option\Support\InputValidator;
use Syntatis\WP\Option\Support\OutputResolver;

use function array_merge;
use function trim;

class Registry implements WithHook
{
	private int $strict = 0;

	private Hook $hook;

	private string $prefix = '';

	/** @var array<Option> */
	private array $options = [];

	/**
	 * @param array<Option> $options The options to register.
	 * @param int           $strict  The level of strictness to apply to the option values.
	 */
	public function __construct(array $options, int $strict = 0)
	{
		$this->options = $options;
		$this->strict = $strict;
	}

	public function hook(Hook $hook): void
	{
		$this->hook = $hook;
	}

	public function setPrefix(string $prefix = ''): void
	{
		$this->prefix = $prefix;
	}

	/**
	 * Register the options.
	 *
	 * @param string|null $optionGroup The option group to register the options with.
	 *                                 When it is provided, the options will be registered with the WordPress settings API,
	 *                                 `register_setting`, and would make the option available in the WordPress API
	 *                                 `/wp/v2/settings` endpoint.
	 */
	public function register(?string $optionGroup = null): void
	{
		foreach ($this->options as $option) {
			if (! $option instanceof Option) {
				continue;
			}

			$this->registerOption($option, $optionGroup);
		}
	}

	private function registerOption(Option $option, ?string $optionGroup = null): void
	{
		$optionName = $this->prefix . $option->getName();
		$optionPriority = $option->getPriority();
		$settingArgs = $option->getSettingArgs();

		if (! isset($settingArgs['type']) || trim($settingArgs['type']) === '') {
			throw new InvalidArgumentException('Unable to determine the "type" for ' . $option->getName() . ' option.');
		}

		$optionType = $settingArgs['type'];
		$optionDefault = $settingArgs['default'] ?? null;

		$inputSanitizer = new InputSanitizer();
		$outputResolver = new OutputResolver($optionType, $this->strict);

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

		if ($optionGroup) {
			register_setting(
				$optionGroup,
				$optionName,
				array_merge(
					$settingArgs,
					[
						'sanitize_callback' => static fn ($value) => $inputSanitizer->sanitize($value),
					],
				),
			);
		} else {
			$this->hook->addFilter(
				'sanitize_option_' . $optionName,
				static fn ($value) => $inputSanitizer->sanitize($value),
				$optionPriority,
			);
		}

		if ($this->strict !== 1) {
			return;
		}

		$inputValidator = new InputValidator($optionType, $option->getConstraints());

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

	private function registerNetworkOption(NetworkOption $option): void
	{
	}
}
