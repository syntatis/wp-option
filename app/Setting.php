<?php

declare(strict_types=1);

namespace Syntatis\WP\Option;

use function array_key_exists;
use function array_keys;
use function count;
use function gettype;
use function range;

/**
 * @phpstan-type ValueType 'array'|'boolean'|'float'|'integer'|'string'|'object'
 * @phpstan-type ValueFormat 'date-time'|'uri'|'email'|'ip'|'uuid'|'hex-color'
 * @phpstan-type Constraints callable|array<callable>|Constraint|ValidatorInterface|null
 * @phpstan-type RESTSchema array{type?: ValueType, format?: string, properties?: array<string, array{type: ValueType, default?: mixed}>, items?: array{type: ValueType, format?: ValueFormat}}
 * @phpstan-type RESTConfig array{name?: string, schema: RESTSchema}
 * @phpstan-type OptionConfig array{type: ValueType, default?: mixed, description?: string, show_in_rest?: bool|RESTSchema, constraints?: Constraints}
 */
final class Setting
{
	private string $name;

	/**
	 * @var array<string, mixed>
	 * @phpstan-var OptionConfig|array<array-key, empty>
	 */
	private array $args = [];

	public function __construct(string $name)
	{
		$this->name = $name;
	}

	public function getName(): string
	{
		return $this->name;
	}

	/** @phpstan-param ValueType $value */
	public function hasType(string $value): self
	{
		$this->args['type'] = $value;

		return clone $this;
	}

	/** @param array<mixed>|bool|float|int|string $value */
	public function hasDefault($value): self
	{
		$this->args['default'] = $value;

		return clone $this;
	}

	public function withDescription(string $value): self
	{
		$this->args['description'] = $value;

		return clone $this;
	}

	/**
	 * Whether to show the setting on the REST API.
	 *
	 * @param array|bool $value
	 *
	 * @phpstan-param bool|RESTSchema $value
	 */
	public function shouldREST($value = true): self
	{
		$this->args['show_in_rest'] = $value;

		return clone $this;
	}

	/** @return array<string, mixed> */
	public function getArgs(): array
	{
		if (
			! array_key_exists('type', $this->args)
			&& isset($this->args['default'])
			&& $this->args['default'] !== null
		) {
			$default = $this->args['default'];
			$inferredType = gettype($default);

			switch ($inferredType) {
				case 'array':
					// @phpstan-ignore-next-line -- The type is already inferred from the default.
					$this->args['type'] = $this->isAssociativeArray($default) ? 'object' : $inferredType;
					break;
				case 'double':
					$this->args['type'] = 'number';
					break;
				default:
					$this->args['type'] = $inferredType;
			}
		}

		$shouldREST = $this->args['show_in_rest'] ?? false;

		if ($shouldREST) {
			$schema = [
				'type' => $this->args['type'],
			];

			if ($this->args['type'] === 'object' && isset($this->args['default'])) {
				$schema['properties'] = [];

				foreach ($this->args['default'] as $key => $value) {
					$schema['properties'][$key] = [
						'type' => gettype($value),
						'default' => $value,
					];
				}
			}

			$this->args['show_in_rest'] = $shouldREST === true ?
				[
					'name' => $this->name,
					'schema' => $schema,
				] : $shouldREST;
		}

		return $this->args;
	}

	/**
	 * @param array<mixed> $array
	 *
	 * @phpstan-param array<array-key, mixed> $array
	 */
	private function isAssociativeArray(array $array): bool
	{
		return array_keys($array) !== range(0, count($array) - 1);
	}
}
