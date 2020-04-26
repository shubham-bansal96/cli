<?php

namespace Acquia\Ads\Tests;

use Acquia\Ads\AdsApplication;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class BltTestBase.
 *
 * Base class for all tests that are executed for BLT itself.
 */
abstract class CommandTestBase extends TestCase
{

    /**
     * The command tester.
     *
     * @var \Symfony\Component\Console\Tester\CommandTester
     */
    private $commandTester;

    /**
     * Creates a command object to test.
     *
     * @return \Symfony\Component\Console\Command\Command
     *   A command object with mocked dependencies injected.
     */
    abstract protected function createCommand(): Command;

    /** @var Application */
    protected $application;

    /**
     * Executes a given command with the command tester.
     *
     * @param array $args
     *   The command arguments.
     * @param string[] $inputs
     *   An array of strings representing each input passed to the command input
     *   stream.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    protected function executeCommand(array $args = [], array $inputs = []): void {
        $tester = $this->getCommandTester();
        $tester->setInputs($inputs);
        $command_name = $this->createCommand()::getDefaultName();
        $args = array_merge(['command' => $command_name], $args);
        $tester->execute($args, ['verbosity' => Output::VERBOSITY_VERBOSE]);
    }

    /**
     * Gets the command tester.
     *
     * @return \Symfony\Component\Console\Tester\CommandTester
     *   A command tester.
     * @throws \Psr\Cache\InvalidArgumentException
     */
    protected function getCommandTester(): CommandTester {
        if ($this->commandTester) {
            return $this->commandTester;
        }

        $input = new ArrayInput([]);
        $output = new NullOutput();
        $logger = new ConsoleLogger($output);
        $repo_root = null;
        $this->application = new AdsApplication('ads', 'UNKNOWN', $input, $output, $logger, $repo_root);
        $created_command = $this->createCommand();
        $this->application->add($created_command);
        $found_command = $this->application->find($created_command::getDefaultName());
        $this->assertInstanceOf(get_class($created_command), $found_command, 'Instantiated class.');
        $this->commandTester = new CommandTester($found_command);

        return $this->commandTester;
    }

    /**
     * Gets the display returned by the last execution of the command.
     *
     * @return string
     *   The display.
     * @throws \Psr\Cache\InvalidArgumentException
     */
    protected function getDisplay(): string {
        return $this->getCommandTester()->getDisplay();
    }

    /**
     * Gets the status code returned by the last execution of the command.
     *
     * @return int
     *   The status code.
     * @throws \Psr\Cache\InvalidArgumentException
     */
    protected function getStatusCode(): int {
        return $this->getCommandTester()->getStatusCode();
    }
}