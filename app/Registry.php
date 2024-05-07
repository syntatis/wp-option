<?php

declare(strict_types=1);

namespace Syntatis\WP\Option;

use InvalidArgumentException;
use Syntatis\WP\Hook\Contract\WithHook;
use Syntatis\WP\Hook\Hook;
use Syntatis\WP\Option\Registries\OptionRegistry;
use Syntatis\WP\Option\Support\InputSanitizer;
use Syntatis\WP\Option\Support\InputValidator;
use Syntatis\WP\Option\Support\OutputResolver;

use function is_array;
use function is_bool;
use function Syntatis\Utils\is_blank;

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
				$this->registerNetworkOptions($option);
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

	private function registerNetworkOptions(NetworkOption $networkOption): void
	{
		if (! is_multisite()) {
			return;
		}

		$optionName = $this->prefix . $networkOption->getName();
		$settingArgs = $networkOption->getSettingArgs();

		if (! isset($settingArgs['type']) || is_blank($settingArgs['type'])) {
			throw new InvalidArgumentException('Unable to determine the "type" for ' . $networkOption->getName() . ' option.');
		}

		$optionName = $this->prefix . $optionName;
		$optionType = $settingArgs['type'];
		$optionDefault = $settingArgs['default'] ?? null;
		$optionPriority = $networkOption->getPriority();

		$inputSanitizer = new InputSanitizer();
		$inputValidator = new InputValidator($optionType, $networkOption->getConstraints());
		$outputResolver = new OutputResolver($optionType, $this->strict);

		$this->hook->addFilter('pre_add_site_option_' . $optionName, function ($value) use ($optionName, $inputSanitizer, $inputValidator) {
			$this->states[$optionName] = 'adding';

			if ($this->strict === 1) {
				$inputValidator->validate($value);
			}

			return $inputSanitizer->sanitize($value);
		}, $optionPriority);

		$this->hook->addFilter('pre_update_site_option_' . $optionName, function ($value) use ($inputSanitizer, $inputValidator) {
			if ($this->strict === 1) {
				$inputValidator->validate($value);
			}

			return $inputSanitizer->sanitize($value);
		}, $optionPriority);

		$this->hook->addAction('add_site_option_' . $optionName, function ($value) use ($optionName): void {
			unset($this->states[$optionName]);
		}, $optionPriority);

		$this->hook->addFilter(
			'default_site_option_' . $optionName,
			function ($default) use ($schema, $outputResolver, $optionType, $optionName) {
				$state = $this->states[$optionName] ?? null;

				/**
				 * WordPress will check the cache before making a database call. If the option is not found in the cache,
				 * it will return the default value passed on the `get_site_option` function. At this point, when
				 * adding an option the default should be a `false`, otherwise it will skip adding the value
				 * being added.
				 *
				 * @see https://github.com/WordPress/wordpress-develop/blob/87dfd5514b52aef456b7232b1959873e69e651da/src/wp-includes/option.php#L1918-L1922
				 */
				if ($state === 'adding') {
					return $default;
				}

				$notOptionCache = $this->notOptionCache();
				$isNotOption = isset($notOptionCache[$optionName]) && $notOptionCache[$optionName] === true;

				if ($isNotOption) {
					/**
					 * WordPress by default will always return the default as `false`. It's currently not possible to identify
					 * whether the `$default` is coming from the argument passed on the `get_site_option` function, or if
					 * it's the default value WordPress set.
					 */
					if ($optionType === 'boolean') {
						if ($default === true) {
							return true;
						}

						/**
						 * If the default value is not a boolean, it could mean the `get_site_option` function is
						 * passed with a default argument e.g. `get_site_option('foo', 1)`.
						 */
						if (! is_bool($default)) {
							return $outputResolver->resolve($default);
						}

						/**
						 * Otherwise, check if the schema has a default value set, and pass that instead.
						 */
						if (isset($schema['default'])) {
							return $outputResolver->resolve($schema['default']);
						}

						return null;
					}

					if ($default !== false) {
						return $outputResolver->resolve($default);
					}

					return $outputResolver->resolve($schema['default'] ?? null);
				}

				return $default;
			},
			$optionPriority,
		);

		$this->hook->addFilter(
			'site_option_' . $optionName,
			function ($value) use ($outputResolver, $optionName) {
				/**
				 * WordPress will check the cache before making a database call. If the option is not found in the cache,
				 * it will return the default value passed on the `get_site_option` function. At this point, when
				 * adding an option the default should be a `false`, otherwise it will skip adding the value
				 * being added.
				 *
				 * @see https://github.com/WordPress/wordpress-develop/blob/87dfd5514b52aef456b7232b1959873e69e651da/src/wp-includes/option.php#L1918-L1922
				 */
				if (isset($this->states[$optionName]) && $this->states[$optionName] === 'adding') {
					return $value;
				}

				$notOptionCache = $this->notOptionCache();
				$isNotOption = isset($notOptionCache[$optionName]) && $notOptionCache[$optionName] === true;

				/**
				 * If it is not an option, the value may have resolved from the `default_site_option_` hook,
				 */
				if ($isNotOption) {
					return $value;
				}

				return $outputResolver->resolve($value);
			},
			$optionPriority,
		);
	}

	/** @return array<string, bool> */
	private function notOptionCache(): array
	{
		$networkId = get_current_network_id();
		$notOptionsKey = $networkId . ':notoptions';
		$notOptionsCache = wp_cache_get($notOptionsKey, 'site-options');

		return is_array($notOptionsCache) ? $notOptionsCache : [];
	}
}
