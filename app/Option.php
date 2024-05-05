<?php

declare(strict_types=1);

namespace Syntatis\WP\Option;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;

use function array_is_list;
use function array_key_exists;
use function gettype;
use function in_array;
use function strtolower;

/**
 * @phpstan-type Constraints callable|array<callable>|Constraint|ValidatorInterface|null
 * @phpstan-type ValueDefault bool|float|int|string|array<array-key, bool|float|int|string|array<array-key, mixed>>
 * @phpstan-type ValueFormat 'date-time'|'uri'|'email'|'ip'|'uuid'|'hex-color'
 * @phpstan-type ValueType 'string'|'boolean'|'integer'|'number'|'array'|'object'
 * @phpstan-type RESTSchemaProperties array<string, array{type: ValueType, default?: array<mixed>|bool|float|int|string}>
 * @phpstan-type RESTSchema array{properties?: RESTSchemaProperties, items?: array{type?: ValueType, format?: ValueFormat}}
 * @phpstan-type RESTConfig array{name?: string, schema: RESTSchema}
 * @phpstan-type SettingArgs array{type?: ValueType, default?: ValueDefault|null, description?: string, show_in_rest?: RESTConfig|bool}
 */
final class Option
{
	private string $name;

	private int $priority = 99;

	/**
	 * @var mixed
	 * @phpstan-var Constraints
	 */
	private $constraints;

	/**
	 * @var array<string, mixed>
	 * @phpstan-var SettingArgs
	 */
	private array $settingArgs = ['default' => null];

	/** @phpstan-param ValueType $type */
	public function __construct(string $name, string $type)
	{
		$this->name = $name;
		$this->settingArgs['type'] = $type;
	}

	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * @param array|bool|float|int|string $value
	 *
	 * @phpstan-param ValueDefault $value
	 */
	public function setDefault($value): self
	{
		$this->settingArgs['default'] = $value;

		return clone $this;
	}

	public function setDescription(string $value): self
	{
		$this->settingArgs['description'] = $value;

		return clone $this;
	}

	/**
	 * Whether to show the option on WordPress REST API endpoint, `/wp/v2/settings`.
	 *
	 * @param array|bool $value
	 *
	 * @phpstan-param RESTConfig|bool $value
	 */
	public function apiConfig($value): self
	{
		$this->settingArgs['show_in_rest'] = $value;

		return clone $this;
	}

	/**
	 * @param mixed $value
	 *
	 * @phpstan-param Constraints $value
	 */
	public function setConstraints($value): self
	{
		$this->constraints = $value;

		return clone $this;
	}

	/**
	 * @return mixed
	 *
	 * @phpstan-return Constraints
	 */
	public function getConstraints()
	{
		return $this->constraints;
	}

	public function setSanitizer(callable $value): self
	{
		$this->settingArgs['sanitize_callback'] = $value;

		return clone $this;
	}

	/**
	 * The priority determines the order in which the `option_` related hooks are executed.
	 * It is usually not necessary to change this value. However, if there is a conflict
	 * with other plugins or themes that use the same hook, you can set a specific
	 * priority to ensure that your hook runs before or after them.
	 */
	public function setPriority(int $value): self
	{
		$this->priority = $value;

		return clone $this;
	}

	public function getPriority(): int
	{
		return $this->priority;
	}

	/**
	 * Retrieve the arguments to pass for the `register_setting` function.
	 *
	 * @see https://developer.wordpress.org/reference/functions/register_setting/#parameters
	 *
	 * @return array<string, mixed>
	 *
	 * @phpstan-return SettingArgs|array{}
	 */
	public function getSettingArgs(): array
	{
		try {
			if (
				! array_key_exists('type', $this->settingArgs)
				&& isset($this->settingArgs['default'])
				&& $this->settingArgs['default'] !== null
			) {
				$default = $this->settingArgs['default'];
				$inferredType = $this->inferType($default);

				if ($inferredType) {
					$this->settingArgs['type'] = $inferredType;
				}
			}

			return $this->settingArgs;
		} catch (Throwable $e) {
			return [];
		}
	}

	/**
	 * @param mixed $value
	 * @return string Inferred type of the value.
	 *
	 * @phpstan-return ValueType|null
	 */
	private function inferType($value): ?string
	{
		$type = strtolower(gettype($value));
		$inferredType = null;

		if (! in_array($type, ['boolean', 'integer', 'double', 'string', 'array'], true)) {
			return $inferredType;
		}

		switch ($type) {
			case 'array':
				// @phpstan-ignore-next-line -- The type is already inferred from the default.
				$inferredType = array_is_list($value) ? $type : 'object';
				break;
			case 'double':
				$inferredType = 'number';
				break;
			default:
				if (in_array($type, ['integer', 'boolean', 'string'], true)) {
					$inferredType = $type;
				}
		}

		return $inferredType;
	}
}
