<?php

declare(strict_types=1);

namespace Syntatis\WPOption\Registries;

use InvalidArgumentException;
use Syntatis\WP\Hook\Contract\WithHook;
use Syntatis\WP\Hook\Hook;
use Syntatis\WPOption\Contracts\Registrable;
use Syntatis\WPOption\Option;
use Syntatis\WPOption\Support\InputSanitizer;
use Syntatis\WPOption\Support\InputValidator;
use Syntatis\WPOption\Support\OutputResolver;

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

	/** @phpstan-var array<'actions'|'filters', array<string, callable>> */
	private array $callbacks = [];

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

		$optionType = $this->option->getType();
		$optionPriority = $this->option->getPriority();

		$inputSanitizer = new InputSanitizer();
		$outputResolver = new OutputResolver($optionType, $this->strict);

		$this->callbacks['filters']['default_option_' . $this->optionName] = function ($default, $option, $passedDefault) use ($outputResolver) {
			return $outputResolver->resolve($passedDefault ? $default : $this->option->getDefault());
		};
		$this->hook->addFilter(
			'default_option_' . $this->optionName,
			$this->callbacks['filters']['default_option_' . $this->optionName],
			$optionPriority,
			3,
		);

		$this->callbacks['actions']['option_' . $this->optionName] = static fn ($value) => $outputResolver->resolve($value);
		$this->hook->addFilter(
			'option_' . $this->optionName,
			$this->callbacks['actions']['option_' . $this->optionName],
			$optionPriority,
		);

		if ($this->optionGroup) {
			register_setting(
				$this->optionGroup,
				$this->optionName,
				array_merge(
					$this->option->getSettingArgs(),
					[
						'sanitize_callback' => static fn ($value) => $inputSanitizer->sanitize($value),
					],
				),
			);
		} else {
			$this->callbacks['filters']['sanitize_option_' . $this->optionName] = static fn ($value) => $inputSanitizer->sanitize($value);
			$this->hook->addFilter(
				'sanitize_option_' . $this->optionName,
				$this->callbacks['filters']['sanitize_option_' . $this->optionName],
				$optionPriority,
			);
		}

		if ($this->strict !== 1) {
			return;
		}

		$inputValidator = new InputValidator($optionType, $this->option->getConstraints());

		$this->callbacks['actions']['add_option'] = static fn ($name, $value) => $inputValidator->validate($value);
		$this->hook->addAction(
			'add_option',
			$this->callbacks['actions']['add_option'],
			$optionPriority,
			2,
		);

		$this->callbacks['actions']['update_option'] = static fn ($name, $oldValue, $newValue) => $inputValidator->validate($newValue);
		$this->hook->addAction(
			'update_option',
			$this->callbacks['actions']['update_option'],
			$optionPriority,
			3,
		);
	}

	public function deregister(): void
	{
		if (is_blank($this->optionName)) {
			throw new InvalidArgumentException('Unable to unregister an option without a name.');
		}

		if ($this->optionGroup) {
			unregister_setting($this->optionGroup, $this->optionName);
		}

		$filters = $this->callbacks['filters'] ?? [];
		$actions = $this->callbacks['actions'] ?? [];

		foreach ($filters as $name => $callback) {
			remove_filter($name, $callback, $this->option->getPriority());
		}

		foreach ($actions as $name => $callback) {
			remove_action($name, $callback, $this->option->getPriority());
		}

		delete_option($this->optionName);
	}
}
