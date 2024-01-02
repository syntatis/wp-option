<?php

declare(strict_types=1);

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Error\Deprecated;
use PHPUnit\Framework\Error\Notice;
use PHPUnit\Framework\Error\Warning;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\Test;
/**
 * Hotfix to for intelephense.
 *
 * @see https://github.com/bmewburn/vscode-intelephense/issues/600
 */
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Util\Getopt;
use PHPUnit\Util\GlobalState;

class PHPUnit_Framework_TestCase extends TestCase
{
}
class PHPUnit_Framework_Exception extends Exception
{
}
class PHPUnit_Framework_ExpectationFailedException extends ExpectationFailedException
{
}
class PHPUnit_Framework_Error_Deprecated extends Deprecated
{
}
class PHPUnit_Framework_Error_Notice extends Notice
{
}
class PHPUnit_Framework_Error_Warning extends Warning
{
}
class PHPUnit_Framework_Test extends Test
{
}
class PHPUnit_Framework_Warning extends \PHPUnit\Framework\Warning
{
}
class PHPUnit_Framework_AssertionFailedError extends AssertionFailedError
{
}
class PHPUnit_Framework_TestSuite extends TestSuite
{
}
class PHPUnit_Framework_TestListener extends TestListener
{
}
class PHPUnit_Util_GlobalState extends GlobalState
{
}
class PHPUnit_Util_Getopt extends Getopt
{
}
