<?php

declare(strict_types=1);

namespace Syntatis\WP\Option;

use Syntatis\WP\Hook\Hook;
use Syntatis\WP\Option\Support\InputSanitizer;
use Syntatis\WP\Option\Support\InputValidator;
use Syntatis\WP\Option\Support\OutputResolver;

use function array_merge;
use function is_array;
use function is_bool;

/**
 * @phpstan-import-type OptionType from Option
 * @phpstan-import-type OptionSchema from Option
 */
final class SiteOption
{
	public const TYPES = [
		'string',
		'boolean',
		'integer',
		'float',
		'array',
	];

	private int $priority = 99;

	private int $strict = 0;

	private Hook $hook;

	private ?string $prefix;

	/** @phpstan-var array<string, OptionSchema> */
	private array $schema = [];

	/**
	 * Holds the current state of the option.
	 *
	 * @var array<string, string>
	 */
	private array $states = [];

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
		if (! is_multisite()) {
			return;
		}

		foreach ($this->schema as $optionName => $schema) {
			$optionName = $this->prefix . $optionName;
			$optionType = $schema['type'];
			$optionDefault = $schema['default'] ?? null;
			$optionPriority = $schema['priority'] ?? $this->priority;

			$inputSanitizer = new InputSanitizer($optionType);
			$inputValidator = new InputValidator($optionType);
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

		$this->hook->run();
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
