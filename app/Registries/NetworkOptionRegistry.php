<?php

declare(strict_types=1);

namespace Syntatis\WP\Option\Registries;

use InvalidArgumentException;
use Syntatis\WP\Hook\Contract\WithHook;
use Syntatis\WP\Hook\Hook;
use Syntatis\WP\Option\Contracts\Registrable;
use Syntatis\WP\Option\NetworkOption;
use Syntatis\WP\Option\Support\InputSanitizer;
use Syntatis\WP\Option\Support\InputValidator;
use Syntatis\WP\Option\Support\OutputResolver;

use function is_array;
use function is_bool;
use function Syntatis\Utils\is_blank;
use function trim;

class NetworkOptionRegistry implements Registrable, WithHook
{
	private Hook $hook;

	private NetworkOption $option;

	private int $strict;

	private ?string $optionName = null;

	/** @var array<string, mixed> */
	private array $states = [];

	public function __construct(NetworkOption $option, int $strict = 0)
	{
		$this->option = $option;
		$this->optionName = $option->getName();
		$this->strict = $strict;
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
		if (! is_multisite()) {
			return;
		}

		$settingArgs = $this->option->getSettingArgs();

		if (! isset($settingArgs['type']) || is_blank($settingArgs['type'])) {
			throw new InvalidArgumentException('Unable to determine the "type" for ' . $this->option->getName() . ' option.');
		}

		$optionType = $settingArgs['type'];
		$optionDefault = $settingArgs['default'] ?? null;
		$optionPriority = $this->option->getPriority();

		$inputSanitizer = new InputSanitizer();
		$inputValidator = new InputValidator($optionType, $this->option->getConstraints());
		$outputResolver = new OutputResolver($optionType, $this->strict);

		$this->hook->addFilter(
			'pre_add_site_option_' . $this->optionName,
			function ($value) use ($inputSanitizer, $inputValidator) {
				$this->states[$this->optionName] = 'adding';

				if ($this->strict === 1) {
					$inputValidator->validate($value);
				}

				return $inputSanitizer->sanitize($value);
			},
			$optionPriority,
		);

		$this->hook->addFilter(
			'pre_update_site_option_' . $this->optionName,
			function ($value) use ($inputSanitizer, $inputValidator) {
				if ($this->strict === 1) {
					$inputValidator->validate($value);
				}

				return $inputSanitizer->sanitize($value);
			},
			$optionPriority,
		);

		$this->hook->addAction(
			'add_site_option_' . $this->optionName,
			function (): void {
				unset($this->states[$this->optionName]);
			},
			$optionPriority,
		);

		$this->hook->addFilter(
			'default_site_option_' . $this->optionName,
			function ($default) use ($settingArgs, $outputResolver, $optionType) {
				$state = $this->states[$this->optionName] ?? null;

				/**
				 * WordPress will check the cache before making a database call. If the option is not found in the cache,
				 * it will return the default value passed on the `get_site_option` function. At this point, when
				 * adding an option the default should return `false` instead of a `null`, otherwise WordPress
				 * will skip adding the value.
				 *
				 * @see https://github.com/WordPress/wordpress-develop/blob/87dfd5514b52aef456b7232b1959873e69e651da/src/wp-includes/option.php#L1918-L1922
				 */
				if ($state === 'adding') {
					return $default;
				}

				$notOptionCache = $this->notOptionCache();
				$isNotOption = isset($notOptionCache[$this->optionName]) && $notOptionCache[$this->optionName] === true;

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
						if (isset($settingArgs['default'])) {
							return $outputResolver->resolve($settingArgs['default']);
						}

						return null;
					}

					if ($default !== false) {
						return $outputResolver->resolve($default);
					}

					return $outputResolver->resolve($settingArgs['default'] ?? null);
				}

				return $default;
			},
			$optionPriority,
		);

		$this->hook->addFilter(
			'site_option_' . $this->optionName,
			function ($value) use ($outputResolver) {
				$notOptionCache = $this->notOptionCache();
				$isNotOption = isset($notOptionCache[$this->optionName]) && $notOptionCache[$this->optionName] === true;

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
