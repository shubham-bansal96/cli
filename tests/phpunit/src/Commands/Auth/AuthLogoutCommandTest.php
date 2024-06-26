<?php

declare(strict_types = 1);

namespace Acquia\Cli\Tests\Commands\Auth;

use Acquia\Cli\Command\Auth\AuthLogoutCommand;
use Acquia\Cli\Command\CommandBase;
use Acquia\Cli\Config\CloudDataConfig;
use Acquia\Cli\DataStore\CloudDataStore;
use Acquia\Cli\Exception\AcquiaCliException;
use Acquia\Cli\Tests\CommandTestBase;

/**
 * @property AuthLogoutCommandTest $command
 */
class AuthLogoutCommandTest extends CommandTestBase {

  protected function createCommand(): CommandBase {
    return $this->injectCommand(AuthLogoutCommand::class);
  }

  public function testAuthLogoutCommand(): void {
    $this->executeCommand();
    $output = $this->getDisplay();
    $this->assertFileExists($this->cloudConfigFilepath);
    $config = new CloudDataStore($this->localMachineHelper, new CloudDataConfig(), $this->cloudConfigFilepath);
    $this->assertFalse($config->exists('acli_key'));
    $this->assertNotEmpty($config->get('keys'));
    $this->assertStringContainsString('The key Test Key will be deactivated on this machine.', $output);
    $this->assertStringContainsString('Do you want to delete the active Cloud Platform API credentials (option --delete)? (yes/no) [no]:', $output);
    $this->assertStringContainsString('The active Cloud Platform API credentials were deactivated', $output);
  }

  public function testAuthLogoutInvalidDatastore(): void {
    $this->clientServiceProphecy->isMachineAuthenticated()->willReturn(FALSE);
    $this->removeMockCloudConfigFile();
    $data = [
      'acli_key' => 'key2',
      'keys' => [
        'key1' => [
          'label' => 'foo',
          'secret' => 'foo',
          'uuid' => 'foo',
        ],
      ],
    ];
    $this->fs->dumpFile($this->cloudConfigFilepath, json_encode($data));
    $this->createDataStores();
    $this->command = $this->createCommand();
    $this->expectException(AcquiaCliException::class);
    $this->expectExceptionMessage("Invalid key in datastore at $this->cloudConfigFilepath");
    $this->executeCommand();
  }

}
