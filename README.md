<div align="center">
  <strong>☔ wp-option</strong>
  <p>WordPress option with some safeguards</p>

  ![Packagist Dependency Version](https://img.shields.io/packagist/dependency-v/syntatis/wp-option/php?color=%237A86B8) [![wp](https://github.com/syntatis/wp-option/actions/workflows/wp.yml/badge.svg)](https://github.com/syntatis/wp-option/actions/workflows/wp.yml) [![codecov](https://codecov.io/gh/syntatis/wp-option/graph/badge.svg?token=QH387BY1PK)](https://codecov.io/gh/syntatis/wp-option)
</div>

---

A simple wrapper that adds validation and enforcing consistencies when dealing with WordPress options, such as when adding, updating, and retrieving an option. It supports both `*_option` as well as the corresponding function for network-enable installation, `*_site_option` functions.

## Why?

WordPress option can handle various type of values including strings, booleans, arrays, and integers. However, it lacks the capability to check the type of value being stored or retrieved. The retrieved value can vary depending on the type value stored in the option, for example:

- `false` returns `string(0) ""`
- `true` returns `string(1) "1"`
- `0` returns `string(1) "0"`
- `1` returns `string(1) "1"`
- `'0'` returns `string(1) "0"`
- `'1'` returns `string(1) "1"`
- `null` returns `string(0) ""`

This library aims to help handling this situation better by enabling developers to implement validations, ensuring the correct type of values when adding, updating, and retrieving an option value.

## Installation

```sh
composer require syntatis/wp-option
```

## Usage

First, create an instance of the `Option` class from the library and define the name and the type for the option. The type can be one of the following: `string`, `boolean`, `integer`, `array`, and `number`.

```php
use Syntatis\WPHook\Hook;
use Syntatis\WPOption\Option;
use Syntatis\WPOption\Registry;

$hook = new Hook();
$registry = new Registry([new Option('wporg_custom_option', 'integer')]);
$registry->hook($hook);
$registry->register();
$hook->register();
```

After the option is registered, it will ensure that the returned value of the option is of the correct type. For example, if the option value is `"1"` (numeric string) and the type defined for the option is `integer`, the value will be converted to `1` when retrieved.

```php
add_option('wporg_custom_option', '1');
get_option('wporg_custom_option'); // int(1)
```

By default, when a default is not set for the option registered, the value returned will be a `null`, instead of a `false` [as how WordPress handles it](https://developer.wordpress.org/reference/functions/get_option/).

```php
get_option('wporg_custom_option'); // null
```

If the option default is defined, the default value will be returned instead of returning `null`.

```php
use Syntatis\WPHook\Hook;
use Syntatis\WPOption\Option;
use Syntatis\WPOption\Registry;

$hook = new Hook();
$registry = new Registry([(new Option('wporg_custom_option', 'integer'))->setDefault(0)]);
$registry->hook($hook);
$registry->register();
$hook->register();

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
