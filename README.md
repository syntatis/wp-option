<div align="center">
  <strong>☔ wp-option</strong>
  <p>WordPress option with some safeguards</p>

  ![Packagist Dependency Version](https://img.shields.io/packagist/dependency-v/syntatis/wp-option/php?color=%237A86B8) [![wp](https://github.com/syntatis/wp-option/actions/workflows/wp.yml/badge.svg)](https://github.com/syntatis/wp-option/actions/workflows/wp.yml) [![codecov](https://codecov.io/gh/syntatis/wp-option/graph/badge.svg?token=QH387BY1PK)](https://codecov.io/gh/syntatis/wp-option)
</div>

---

A simple wrapper that adds validation and enforcing consistencies when dealing with WordPress options, such as when adding, updating, and retrieving an option. It supports both `*_option` as well as the corresponding function for network-enable installatiopn, `*_site_option` functions.

## Why?

WordPress option can handle various type of values including strings, booleans, arrays, and integers. However, it lacks the capability to check the type of value being stored or retrieved. The retrieved value can vary depending on the type value stored in the option, for example:

- `false` returns `string(0) ""`
- `true` returns `string(1) "1"`
- `0` returns `string(1) "0"`
- `1` returns `string(1) "1"`
- `'0'` returns `string(1) "0"`
- `'1'` returns `string(1) "1"`
- `null` returns `string(0) ""`

It is the responsibility of developers to ensure that the value of the option aligns with the expected type. This library aims to help handling this situation better by enabling developers to implement validations, ensuring the correct type of values when adding, updating, and retrieving an option value.

## Installation

```sh
composer require syntatis/wp-option
```

## Usage

First, create an instance of the `Option` class from the library and define the schema of the options. The schema is an array of options where the key is the name of the option and the value is an array of the option's schema defining:

| Schema | Description | Values |
| --- | --- | --- |
| `type` | The type of the option value | The value can be one of the following: `string`, `boolean`, `integer`, `float`, and `array`. |
| `default` | The default value of the option | Ideally, the value should be of the same type as the `type` value. |

```php
use Syntatis\WP\Hook\Hook;
use Syntatis\WP\Option\Option;

$option = new Option(new Hook());
$option->setSchema([
  'wporg_custom_option' => [
    'type' => 'integer',
  ],
]);
$option->register();
```

After the schema defined and registered, it will ensure that the returned value of the option is of the correct type. For example, if the option value is `"1"` (numeric string) and the type defined in the schema for the option is `integer`, the value will be converted to `1` when retrieved.

```php
add_option('wporg_custom_option', '1');
get_option('wporg_custom_option'); // int(1)
```

By default, when a default is not set for the option registered, the value returned will be a `null`, instead of a `false` [as how WordPress handles it](https://developer.wordpress.org/reference/functions/get_option/).

```php
get_option('wporg_custom_option'); // null
```

If the option is defined in the schema, the default value will be returned instead.

```php
use Syntatis\WP\Hook\Hook;
use Syntatis\WP\Option\Option;

$option = new Option(new Hook());
$option->setSchema([
  'wporg_custom_option' => [
    'type' => 'integer',
    'default' => 0,
  ],
]);
$option->register();

get_option('wporg_custom_option'); // int(0)
```


For more advanced usage, please refer to the [Wiki](https://github.com/syntatis/wp-option/wiki).

## Reference

- [add_option()](https://developer.wordpress.org/reference/functions/add_option/)
- [update_option()](https://developer.wordpress.org/reference/functions/update_option/)
- [get_option()](https://developer.wordpress.org/reference/functions/get_option/)
- [get_site_option()](https://developer.wordpress.org/reference/functions/get_site_option/)
- [add_site_option()](https://developer.wordpress.org/reference/functions/add_site_option/)
- [update_site_option()](https://developer.wordpress.org/reference/functions/update_site_option/)
