<?php

declare(strict_types=1);

namespace Syntatis\WP\Option;

use Syntatis\WP\Hook\Contract\WithHook;
use Syntatis\WP\Hook\Hook;
use Syntatis\WP\Option\Registries\NetworkOptionRegistry;
use Syntatis\WP\Option\Registries\OptionRegistry;

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
			if ($option instanceof NetworkOption) {
				$registry = new NetworkOptionRegistry($option, $this->strict);
				$registry->setPrefix($this->prefix);
				$registry->hook($this->hook);
				$registry->register();
				continue;
			}

			if (! $option instanceof Option) {
				continue;
			}

			$registry = new OptionRegistry($option, $this->strict);
			$registry->setOptionGroup($optionGroup);
			$registry->setPrefix($this->prefix);
			$registry->hook($this->hook);
			$registry->register();
		}
	}
}
