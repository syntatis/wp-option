<?php

declare(strict_types=1);

namespace Syntatis\WPOption;

use InvalidArgumentException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use function array_merge;
use function Syntatis\Utils\is_blank;

/**
 * @phpstan-type Constraints callable|array<callable>|Constraint|ValidatorInterface|null
 * @phpstan-type ValueDefault bool|float|int|string|array<array-key, bool|float|int|string|array<array-key, mixed>>|null
 * @phpstan-type ValueFormat 'date-time'|'uri'|'email'|'ip'|'uuid'|'hex-color'
 * @phpstan-type ValueType 'string'|'boolean'|'integer'|'number'|'array'
 * @phpstan-type APISchemaProperties array<string, array{type: ValueType, default?: array<mixed>|bool|float|int|string}>
 * @phpstan-type APISchema array{properties?: APISchemaProperties, items?: array{type?: ValueType, format?: ValueFormat}}
 * @phpstan-type APIConfig array{name?: string, schema: APISchema}
 * @phpstan-type SettingVars array{description?: string, show_in_rest?: APIConfig|bool}
 * @phpstan-type SettingArgs array{type: ValueType, default: ValueDefault, description?: string, show_in_rest?: APIConfig|bool}
 */
class Option
{
	private string $name;

	/** @phpstan-var ValueType */
	private string $type;

	/** @phpstan-var ValueDefault */
	private $default;

	private int $priority = 99;

	/**
	 * @var mixed
	 * @phpstan-var Constraints
	 */
	private $constraints;

	/**
	 * @var array<string, mixed>
	 * @phpstan-var SettingVars
	 */
	private array $settingVars = [];

	/** @phpstan-param ValueType $type */
	public function __construct(string $name, string $type)
	{
		if (is_blank($name)) {
			throw new InvalidArgumentException('Option name must not be blank.');
		}

		$this->name = $name;
		$this->type = $type;
	}

	public function getName(): string
	{
		return $this->name;
	}

	/** @phpstan-return ValueType */
	public function getType(): string
	{
		return $this->type;
	}

	/**
	 * @param array|bool|float|int|string $value
	 *
	 * @phpstan-param ValueDefault $value
	 */
	public function setDefault($value): self
	{
		$this->default = $value;

		return clone $this;
	}

	/** @phpstan-return ValueDefault */
	public function getDefault()
	{
		return $this->default;
	}

	public function setDescription(string $value): self
	{
		$this->settingVars['description'] = $value;

		return clone $this;
	}

	/**
	 * Whether to show the option on WordPress REST API endpoint, `/wp/v2/settings`.
	 *
	 * @param array|bool $value
	 *
	 * @phpstan-param APIConfig|bool $value
	 */
	public function apiEnabled($value = true): self
	{
		$this->settingVars['show_in_rest'] = $value;

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
	 * @phpstan-return SettingArgs
	 */
	public function getSettingArgs(): array
	{
		$settingArgs = [
			'type' => $this->type,
			'default' => $this->default,
		];

		return array_merge($settingArgs, $this->settingVars);
	}
}
