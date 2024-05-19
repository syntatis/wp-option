<?php

declare(strict_types=1);

namespace Syntatis\WPOption\Registries;

use Syntatis\WPHook\Contract\WithHook;
use Syntatis\WPHook\Hook;
use Syntatis\WPOption\Contracts\Registrable;
use Syntatis\WPOption\Option;
use Syntatis\WPOption\Support\InputSanitizer;
use Syntatis\WPOption\Support\InputValidator;
use Syntatis\WPOption\Support\OutputResolver;

use function array_merge;
use function trim;

class OptionRegistry implements Registrable, WithHook
{
	private Hook $hook;

	private Option $option;

	private int $strict;

	private string $optionName;

	private ?string $settingGroup = null;

	public function __construct(Option $option, int $strict = 0)
	{
		$this->option = $option;
		$this->optionName = $option->getName();
		$this->strict = $strict;
	}

	/**
	 * Set the name of the setting group for the option. By setting the group,
	 * the option will be registered with the WordPress settings API.
	 *
	 * @see https://developer.wordpress.org/reference/functions/register_setting/
	 */
	public function setSettingGroup(?string $settingGroup = null): void
	{
		$this->settingGroup = $settingGroup;
	}

	/**
	 * Set the option prefix. e.g. `wp_starter_plugin_`.
	 */
	public function setPrefix(string $prefix = ''): void
	{
		$this->optionName = trim($prefix) . $this->optionName;
	}

	/**
	 * Retrieve the option name to register, which may contain the prefix if set.
	 */
	public function getName(): string
	{
		return $this->optionName;
	}

	public function hook(Hook $hook): void
	{
		$this->hook = $hook;
	}

	public function register(): void
	{
		$optionType = $this->option->getType();
		$optionPriority = $this->option->getPriority();

		$inputSanitizer = new InputSanitizer();
		$outputResolver = new OutputResolver($optionType, $this->strict);

		$this->hook->addFilter(
			'default_option_' . $this->optionName,
			function ($default, $option, $passedDefault) use ($outputResolver) {
				return $outputResolver->resolve($passedDefault ? $default : $this->option->getDefault());
			},
			$optionPriority,
			3,
		);

		$this->hook->addFilter(
			'option_' . $this->optionName,
			static fn ($value) => $outputResolver->resolve($value),
			$optionPriority,
		);

		$sanitizeCallback = static fn ($value) => $inputSanitizer->sanitize($value);

		if ($this->settingGroup) {
			register_setting(
				$this->settingGroup,
				$this->optionName,
				array_merge(
					$this->option->getSettingArgs(),
					['sanitize_callback' => $sanitizeCallback],
				),
			);
		} else {
			$this->hook->addFilter(
				'sanitize_option_' . $this->optionName,
				$sanitizeCallback,
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

	public function deregister(): void
	{
		if ($this->settingGroup) {
			unregister_setting($this->settingGroup, $this->optionName);
		}

		$this->hook->removeAllActions();
		$this->hook->removeAllFilters();
	}
}
