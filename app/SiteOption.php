<?php

declare(strict_types=1);

namespace Syntatis\WP\Option;

use Syntatis\WP\Hook\Hook;
use Syntatis\WP\Option\Resolvers\DefaultResolver;
use Syntatis\WP\Option\Resolvers\OutputResolver;

use function array_merge;
use function is_array;

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

			$isNotOption = $optionCache[$optionName] ?? true;

			if ($isNotOption) {
				$this->hook->addFilter(
					'default_site_option_' . $optionName,
					static function ($default, $option, $networkId) use ($optionDefault, $defaultResolver) {
						return $defaultResolver->resolve($optionDefault);
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
