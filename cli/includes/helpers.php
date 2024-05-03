<?php

namespace Valet;

use Exception;
use Illuminate\Container\Container;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Define the ~/.valet path as a constant.
 */
if (! defined('VALET_HOME_PATH')) {
    if (testing()) {
        define('VALET_HOME_PATH', __DIR__.'/../../tests/config/valet');
    } else {
        define('VALET_HOME_PATH', $_SERVER['HOME'].'/.valet');
    }
}
if (! defined('VALET_STATIC_PREFIX')) {
    define('VALET_STATIC_PREFIX', '41c270e4-5535-4daa-b23e-c269744c2f45');
}

define('VALET_LOOPBACK', '127.0.0.1');
define('VALET_SERVER_PATH', realpath(__DIR__.'/../../server.php'));

define('BREW_PREFIX', (new CommandLine())->runAsUser('printf $(brew --prefix)'));

define('ISOLATED_PHP_VERSION', 'ISOLATED_PHP_VERSION');

/**
 * Set or get a global console writer.
 */
function writer(?OutputInterface $writer = null)
{
    $container = Container::getInstance();

    if (! $writer) {
        if (! $container->bound('writer')) {
            $container->instance('writer', new ConsoleOutput());
        }

        return $container->make('writer');
    }

    $container->instance('writer', $writer);

    return null;
}

/**
 * Output the given text to the console.
 */
function info(string $output): void
{
    output('<info>'.$output.'</info>');
}

/**
 * Output the given text to the console.
 */
function warning(string $output): void
{
    output('<fg=red>'.$output.'</>');
}

/**
 * Output a table to the console.
 *
 * @param array $headers
 * @param array $rows
 * @return void
 */
function table(array $headers = [], array $rows = [])
{
    $table = new Table(new ConsoleOutput);

    $table->setHeaders($headers)->setRows($rows);

    $table->render();
}

/**
 * Output the given text to the console.
 *
 * @param string|null $output
 *
 * @return void
 */
function output(?string $output = ''): void
{
    writer()->writeln($output);
}

/**
 * Return whether the app is in the testing environment.
 */
function testing(): bool
{
    return strpos($_SERVER['SCRIPT_NAME'], 'phpunit') !== false;
}

if (! function_exists('resolve')) {
    /**
     * Resolve the given class from the container.
     *
     * @param  string  $class
     * @return mixed
     */
    function resolve($class)
    {
        return Container::getInstance()->make($class);
    }
}

/**
 * Swap the given class implementation in the container.
 *
 * @param  string  $class
 * @param  mixed  $instance
 * @return void
 */
function swap($class, $instance)
{
    Container::getInstance()->instance($class, $instance);
}

if (! function_exists('retry')) {
    /**
     * Retry the given function N times.
     *
     * @param int $retries
     * @param $fn
     * @param  int $sleep
     * @return mixed
     * @throws Exception
     */
    function retry($retries, $fn, $sleep = 0)
    {
        beginning:
        try {
            return $fn();
        } catch (Exception $e) {
            if (! $retries) {
                throw $e;
            }

            $retries--;

            if ($sleep > 0) {
                usleep($sleep * 1000);
            }

            goto beginning;
        }
    }
}

/**
 * Verify that the script is currently running as "sudo".
 * @return void
 * @throws Exception
 */
function should_be_sudo()
{
    if (! isset($_SERVER['SUDO_USER'])) {
        throw new Exception('This command must be run with sudo.');
    }
}

if (! function_exists('tap')) {
    /**
     * Tap the given value.
     *
     * @param  mixed  $value
     * @param  callable  $callback
     * @return mixed
     */
    function tap($value, callable $callback)
    {
        $callback($value);

        return $value;
    }
}

if (! function_exists('ends_with')) {
    /**
     * Determine if a given string ends with a given substring.
     *
     * @param  string  $haystack
     * @param  string|array  $needles
     * @return bool
     */
    function ends_with($haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if (substr($haystack, -strlen($needle)) === (string) $needle) {
                return true;
            }
        }
        return false;
    }
}

/**
 * Get the user
 */
function user()
{
    if (isset($_SERVER['SUDO_USER']) && $_SERVER['SUDO_USER'] !== null) {
        return $_SERVER['SUDO_USER'];
    }

    if (isset($_SERVER['USER']) && $_SERVER['USER'] !== null) {
        return $_SERVER['USER'];
    }

    return '';
}
