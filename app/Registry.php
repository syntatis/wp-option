<?php

declare(strict_types=1);

namespace Syntatis\WPOption;

use Syntatis\WPHook\Contract\WithHook;
use Syntatis\WPHook\Hook;
use Syntatis\WPOption\Registries\NetworkOptionRegistry;
use Syntatis\WPOption\Registries\OptionRegistry;

class Registry implements WithHook
{
	private int $strict = 0;

	private Hook $hook;

	private string $prefix = '';

	/** @var array<Option|NetworkOption> */
	private array $options = [];

	/** @var array<string, array<string, OptionRegistry|NetworkOptionRegistry>> */
	private array $registries = [];

	/**
	 * @param array<Option|NetworkOption> $options The options to register.
	 * @param int                         $strict  The level of strictness to apply to the option values.
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
	 * @param string|null $settingGroup The option group to register the options with.
	 *                                 When it is provided, the options will be registered with the WordPress settings API,
	 *                                 `register_setting`, and would make the option available in the WordPress API
	 *                                 `/wp/v2/settings` endpoint. This argument is not applicable to the network
	 *                                 options as they are currently not supported by the WordPress settings API.
	 */
	public function register(?string $settingGroup = null): void
	{
		foreach ($this->options as $option) {
			if ($option instanceof NetworkOption) {
				$registry = new NetworkOptionRegistry($option, $this->strict);
				$registry->setPrefix($this->prefix);
				$registry->hook($this->hook);
				$registry->register();

				$this->registries[NetworkOptionRegistry::class][$option->getName()] = $registry;
				continue;
			}

			if (! $option instanceof Option) {
				continue;
			}

			$registry = new OptionRegistry($option, $this->strict);
			$registry->setSettingGroup($settingGroup);
			$registry->setPrefix($this->prefix);
			$registry->hook($this->hook);
			$registry->register();

			$this->registries[OptionRegistry::class][$option->getName()] = $registry;
		}
	}

	/**
	 * Remove options from the registry and delete all the existing options. Optionally,
	 * if the `$settingGroup` argument is provided it will also deregister the options
	 * from the WordPress settings API.
	 */
	public function deregister(?string $settingGroup = null): void
	{
		foreach ($this->options as $option) {
			if ($option instanceof NetworkOption) {
				$registry = $this->registries[NetworkOptionRegistry::class][$option->getName()] ?? null;

				if (! $registry instanceof NetworkOptionRegistry) {
					continue;
				}

				$registry->setPrefix($this->prefix);
				$registry->deregister();

				continue;
			}

			if (! $option instanceof Option) {
				continue;
			}

			$registry = $this->registries[OptionRegistry::class][$option->getName()] ?? null;

			if (! $registry instanceof OptionRegistry) {
				continue;
			}

			$registry->setSettingGroup($settingGroup);
			$registry->setPrefix($this->prefix);
			$registry->deregister();
		}
	}
}
