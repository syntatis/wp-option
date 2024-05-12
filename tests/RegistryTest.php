<?php

declare(strict_types=1);

namespace Syntatis\WP\Option\Tests;

use Syntatis\WP\Hook\Hook;
use Syntatis\WP\Option\NetworkOption;
use Syntatis\WP\Option\Option;
use Syntatis\WP\Option\Registry;

class RegistryTest extends TestCase
{
	private Hook $hook;

	private string $optionGroup = 'tests';

	// phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
	public function set_up(): void
	{
		parent::set_up();

		$this->hook = new Hook();
	}

	public function testUninstallOptions(): void
	{
		$registry = new Registry(
			[
				(new Option('hello_world', 'string'))->setDefault('Hello, World!'),
				(new Option('one', 'number'))->setDefault(1),
				(new Option('list', 'array'))->setDefault(['one', 'two', 'three']),
			],
		);
		$registry->hook($this->hook);
		$registry->register();
		$this->hook->run();

		$this->assertSame('Hello, World!', get_option('hello_world'));
		$this->assertSame(1, get_option('one'));
		$this->assertSame(['one', 'two', 'three'], get_option('list'));

		$this->assertTrue(update_option('hello_world', 'Hello Earth!'));
		$this->assertTrue(update_option('one', 2));
		$this->assertTrue(update_option('list', ['four', 'five', 'six']));

		$this->assertSame('Hello Earth!', get_option('hello_world'));
		$this->assertSame(2, get_option('one'));
		$this->assertSame(['four', 'five', 'six'], get_option('list'));

		$registry->deregister();

		$this->assertFalse(get_option('hello_world'));
		$this->assertFalse(get_option('one'));
		$this->assertFalse(get_option('list'));
	}

	public function testUninstallRegisteredOptions(): void
	{
		$registry = new Registry(
			[
				(new Option('hello_world', 'string'))->setDefault('Hello, World!'),
			],
		);
		$registry->hook($this->hook);
		$registry->register('tests');
		$this->hook->run();

		$registeredSettings = get_registered_settings();

		$this->assertArrayHasKey('hello_world', $registeredSettings);
		$this->assertSame('string', $registeredSettings['hello_world']['type']);
		$this->assertSame('tests', $registeredSettings['hello_world']['group']);
		$this->assertSame('Hello, World!', $registeredSettings['hello_world']['default']);

		$this->assertSame('Hello, World!', get_option('hello_world'));
		$this->assertTrue(update_option('hello_world', 'Hello, Earth!'));
		$this->assertSame('Hello, Earth!', get_option('hello_world'));

		$registry->deregister('tests');

		$registeredSettings = get_registered_settings();

		$this->assertFalse(get_option('hello_world'));
		$this->assertArrayNotHasKey('hello_world', $registeredSettings);
	}

	/** @group network-option */
	public function testUninstallNetworkOptions(): void
	{
		$registry = new Registry(
			[
				(new NetworkOption('hello_world', 'string'))->setDefault('Hello, World!'),
				(new NetworkOption('one', 'number'))->setDefault(1),
				(new NetworkOption('list', 'array'))->setDefault(['one', 'two', 'three']),
			],
		);
		$registry->hook($this->hook);
		$registry->register();
		$this->hook->run();

		$this->assertSame('Hello, World!', get_site_option('hello_world'));
		$this->assertSame(1, get_site_option('one'));
		$this->assertSame(['one', 'two', 'three'], get_site_option('list'));

		$this->assertTrue(add_site_option('hello_world', 'Hello Earth!'));
		$this->assertTrue(add_site_option('one', 2));
		$this->assertTrue(add_site_option('list', ['four', 'five', 'six']));

		$this->assertSame('Hello Earth!', get_site_option('hello_world'));
		$this->assertSame(2, get_site_option('one'));
		$this->assertSame(['four', 'five', 'six'], get_site_option('list'));

		$registry->deregister();

		$this->assertFalse(get_site_option('hello_world'));
		$this->assertFalse(get_site_option('one'));
		$this->assertFalse(get_site_option('list'));
	}
}
