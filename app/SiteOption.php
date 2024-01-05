<?php

declare(strict_types=1);

namespace Syntatis\WP\Option;

use Syntatis\WP\Hook\Hook;
use Syntatis\WP\Option\Resolvers\DefaultResolver;
use Syntatis\WP\Option\Resolvers\OutputResolver;

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

		$optionCache = $this->optionCache();

		foreach ($this->schema as $optionName => $schema) {
			$optionName = $this->prefix . $optionName;
			$optionType = $schema['type'];
			$optionDefault = $schema['default'] ?? null;
			$optionPriority = $schema['priority'] ?? $this->priority;

			$outputResolver = new OutputResolver($optionType, $this->strict);
			$defaultResolver = new DefaultResolver($optionType, $this->strict);

			$isNotOption = ! $optionCache || (isset($optionCache[$optionName]) && $optionCache[$optionName] === true);

			if ($isNotOption) {
				$this->hook->addFilter(
					'default_site_option_' . $optionName,
					static function ($default, $option, $networkId) use ($schema, $defaultResolver, $optionType) {
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
								return $defaultResolver->resolve($default);
							}

							/**
							 * Otherwise, check if the schema has a default value set, and pass that instead.
							 */
							if (isset($schema['default'])) {
								return $defaultResolver->resolve($schema['default']);
							}

							return null;
						}

						if ($default !== false) {
							return $defaultResolver->resolve($default);
						}

						return $defaultResolver->resolve($schema['default'] ?? null);
					},
					$optionPriority,
					3,
				);
			} else {
				$this->hook->addFilter(
					'site_option_' . $optionName,
					static function ($value) use ($outputResolver) {
						return $outputResolver->resolve($value);
					},
					$optionPriority,
				);
			}
		}

		$this->hook->run();
	}

	/** @return array<string, bool> */
	private function optionCache(): ?array
	{
		$networkId = get_current_network_id();
		$notOptionsKey = $networkId . ':notoptions';
		$notOptionsCache = wp_cache_get($notOptionsKey, 'site-options');

		return is_array($notOptionsCache) ? $notOptionsCache : [];
	}
}
