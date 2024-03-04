<?php

declare(strict_types = 1);

namespace Acquia\Cli\Tests\Commands\CodeStudio;

use Acquia\Cli\Command\CodeStudio\CodeStudioPipelinesMigrateCommand;
use Acquia\Cli\Command\CommandBase;
use Acquia\Cli\Tests\Commands\Ide\IdeRequiredTestTrait;
use Acquia\Cli\Tests\CommandTestBase;
use Acquia\Cli\Tests\TestBase;
use Gitlab\Client;
use Prophecy\Argument;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Yaml\Yaml;

/**
 * @property \Acquia\Cli\Command\CodeStudio\CodeStudioPipelinesMigrateCommand $command
 * @requires OS linux|darwin
 */
class CodeStudioPipelinesMigrateCommandTest extends CommandTestBase {

  use IdeRequiredTestTrait;

  private string $gitLabHost = 'gitlabhost';
  private string $gitLabToken = 'gitlabtoken';
  private int $gitLabProjectId = 33;
  private int $gitLabTokenId = 118;
  public static string $applicationUuid = 'a47ac10b-58cc-4372-a567-0e02b2c3d470';

  public function setUp(): void {
    parent::setUp();
    $this->mockApplicationRequest();
    TestBase::setEnvVars(['GITLAB_HOST' => 'code.cloudservices.acquia.io']);
  }

  public function tearDown(): void {
    parent::tearDown();
    TestBase::unsetEnvVars(['GITLAB_HOST' => 'code.cloudservices.acquia.io']);
  }

  protected function createCommand(): CommandBase {
    return $this->injectCommand(CodeStudioPipelinesMigrateCommand::class);
  }

  /**
   * @return array<mixed>
   */
  public function providerTestCommand(): array {
    return [
      [
        // One project.
        [$this->getMockedGitLabProject($this->gitLabProjectId)],
        // Inputs.
        [
          // Would you like Acquia CLI to search for a Cloud application that matches your local git config?
          'n',
          // @todo
          '0',
          // Do you want to continue?
          'y',
        ],
        // Args.
        [
          '--key' => $this->key,
          '--secret' => $this->secret,
        ],
      ],
    ];
  }

  /**
   * @dataProvider providerTestCommand
   * @param $mockedGitlabProjects
   * @param $args
   * @param $inputs
   * @group brokenProphecy
   */
  public function testCommand(mixed $mockedGitlabProjects, mixed $inputs, mixed $args): void {
    copy(
      Path::join($this->realFixtureDir, 'acquia-pipelines.yml'),
      Path::join($this->projectDir, 'acquia-pipelines.yml')
    );
    $localMachineHelper = $this->mockLocalMachineHelper();
    $this->mockExecuteGlabExists($localMachineHelper);
    $this->mockGitlabGetHost($localMachineHelper, $this->gitLabHost);
    $this->mockGitlabGetToken($localMachineHelper, $this->gitLabToken, $this->gitLabHost);
    $gitlabClient = $this->prophet->prophesize(Client::class);
    $this->mockGitLabUsersMe($gitlabClient);
    $this->mockRequest('getAccount');
    $this->mockGitLabPermissionsRequest($this::$applicationUuid);
    $projects = $this->mockGetGitLabProjects($this::$applicationUuid, $this->gitLabProjectId, $mockedGitlabProjects);
    $gitlabCicdVariables = [
    [
        'key' => 'ACQUIA_APPLICATION_UUID',
        'masked' => TRUE,
        'protected' => FALSE,
        'value' => NULL,
        'variable_type' => 'env_var',
      ],
      [
        'key' => 'ACQUIA_CLOUD_API_TOKEN_KEY',
        'masked' => TRUE,
        'protected' => FALSE,
        'value' => NULL,
        'variable_type' => 'env_var',
      ],
      [
        'key' => 'ACQUIA_CLOUD_API_TOKEN_SECRET',
        'masked' => TRUE,
        'protected' => FALSE,
        'value' => NULL,
        'variable_type' => 'env_var',
      ],
      [
        'key' => 'ACQUIA_GLAB_TOKEN_NAME',
        'masked' => TRUE,
        'protected' => FALSE,
        'value' => NULL,
        'variable_type' => 'env_var',
      ],
      [
        'key' => 'ACQUIA_GLAB_TOKEN_SECRET',
        'masked' => TRUE,
        'protected' => FALSE,
        'value' => NULL,
        'variable_type' => 'env_var',
      ],
      [
        'key' => 'PHP_VERSION',
        'masked' => FALSE,
        'protected' => FALSE,
        'value' => NULL,
        'variable_type' => 'env_var',
      ],
    ];
    $projects->variables($this->gitLabProjectId)->willReturn($gitlabCicdVariables);
    $projects->update($this->gitLabProjectId, Argument::type('array'));
    $gitlabClient->projects()->willReturn($projects);
    $localMachineHelper->getFilesystem()->willReturn(new Filesystem())->shouldBeCalled();
    $this->command->setGitLabClient($gitlabClient->reveal());

    $this->mockRequest('getApplications');
    // Set properties and execute.
    $this->executeCommand($args, $inputs);

    // Assertions.
    $this->assertEquals(0, $this->getStatusCode());
    $gitlabCiYmlFilePath = $this->projectDir . '/.gitlab-ci.yml';
    $this->assertFileExists($gitlabCiYmlFilePath);
    // @todo Assert things about skips. Composer install, BLT, launch_ode.
    $contents = Yaml::parseFile($gitlabCiYmlFilePath);
    $arraySkipMap = ['composer install', '${BLT_DIR}', 'launch_ode'];
    foreach ($contents as $values) {
      if (array_key_exists('script', $values)) {
        foreach ($arraySkipMap as $map) {
          $this->assertNotContains($map, $values['script'], "Skip option found");
        }
      }
    }
    $this->assertArrayHasKey('include', $contents);
    $this->assertArrayHasKey('variables', $contents);
    $this->assertArrayHasKey('setup', $contents);
    $this->assertArrayHasKey('launch_ode', $contents);
    $this->assertArrayHasKey('script', $contents['launch_ode']);
    $this->assertNotEmpty($contents['launch_ode']['script']);
    $this->assertArrayHasKey('script', $contents['setup']);
    $this->assertArrayHasKey('stage', $contents['setup']);
    $this->assertEquals('Build Drupal', $contents['setup']['stage']);
    $this->assertArrayHasKey('needs', $contents['setup']);
    $this->assertIsArray($contents['setup']['needs']);
  }

}
