<?php

declare(strict_types = 1);

use Drupal\Component\Utility\EmailValidatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\openid_connect\OpenIDConnectAuthmap;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserDataInterface;
use Drupal\openid_connect\OpenIDConnect;

/**
 * Class OpenIDConnectTest.
 */
class OpenIDConnectTest extends UnitTestCase {

  /**
   * Mock of the config factory.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $configFactory;

  /**
   * Mock of the OpenIDConnectAuthMap service.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $authMap;

  /**
   * Mock of the entity_type.manager service.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * Mock of the entity field manager service.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityFieldManager;

  /**
   * Mock of the account_proxy service.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $currentUser;

  /**
   * Mock of the user data interface.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $userData;

  /**
   * Mock of the email validator.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $emailValidator;

  /**
   * Mock of the messenger service.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $messenger;

  /**
   * Mock of the module handler service.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $moduleHandler;

  /**
   * Mock of the logger interface.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * The OpenIDConnect class being tested.
   *
   * @var \Drupal\openid_connect\OpenIDConnect
   */
  protected $openIdConnect;

  /**
   * {@inheritDoc}
   */
  protected function setUp() {
    parent::setUp();

    // Mock the config_factory service.
    $this->configFactory = $this
      ->createMock(ConfigFactoryInterface::class);

    // Mock the authMap open id connect service.
    $this->authMap = $this
      ->createMock(OpenIDConnectAuthmap::class);

    // Mock the entity type manager service.
    $this->entityTypeManager = $this
      ->createMock(EntityTypeManagerInterface::class);

    $this->entityFieldManager = $this
      ->createMock(EntityFieldManagerInterface::class);

    $this->currentUser = $this
      ->createMock(AccountProxyInterface::class);

    $this->userData = $this
      ->createMock(UserDataInterface::class);

    $this->emailValidator = $this
      ->createMock(EmailValidatorInterface::class);

    $this->messenger = $this
      ->createMock(MessengerInterface::class);

    $this->moduleHandler = $this
      ->createMock(ModuleHandler::class);

    $this->logger = $this
      ->createMock(LoggerChannelFactoryInterface::class);

    $this->openIdConnect = new OpenIDConnect(
      $this->configFactory,
      $this->authMap,
      $this->entityTypeManager,
      $this->entityFieldManager,
      $this->currentUser,
      $this->userData,
      $this->emailValidator,
      $this->messenger,
      $this->moduleHandler,
      $this->logger
    );
  }

  /**
   * Test for the userPropertiesIgnore method.
   */
  public function testUserPropertiesIgnore(): void {
    $defaultPropertiesIgnore = [
      'uid',
      'uuid',
      'langcode',
      'preferred_langcode',
      'preferred_admin_langcode',
      'name',
      'pass',
      'mail',
      'status',
      'created',
      'changed',
      'access',
      'login',
      'init',
      'roles',
      'default_langcode',
    ];
    $expectedResults = array_combine($defaultPropertiesIgnore, $defaultPropertiesIgnore);

    $this->moduleHandler->expects($this->once())
      ->method('alter')
      ->with(
        'openid_connect_user_properties_ignore',
        $defaultPropertiesIgnore,
        []
      );

    $this->moduleHandler->expects($this->once())
      ->method('alterDeprecated')
      ->with(
        'hook_openid_connect_user_properties_to_skip_alter() is deprecated and will be removed in 8.x-1.x-rc1.', 'openid_connect_user_properties_to_skip',
        $defaultPropertiesIgnore
      );

    $actualPropertiesIgnored = $this->openIdConnect->userPropertiesIgnore([]);

    $this->assertArrayEquals($expectedResults, $actualPropertiesIgnored);
  }

  /**
   * Test the extractSub method.
   *
   * @param array $userData
   *   The user data as returned from
   *   OpenIDConnectClientInterface::decodeIdToken().
   * @param array $userInfo
   *   The user claims as returned from
   *   OpenIDConnectClientInterface::retrieveUserInfo().
   * @param bool|string $expected
   *   The expected result from the test.
   *
   * @dataProvider dataProviderForExtractSub
   */
  public function testExtractSub(
    array $userData,
    array $userInfo,
    $expected
  ): void {
    $actual = $this->openIdConnect->extractSub($userData, $userInfo);
    $this->assertEquals($expected, $actual);
  }

  /**
   * Data provider for the testExtractSub method.
   *
   * @return array|array[]
   *   The array of tests for the method.
   */
  public function dataProviderForExtractSub(): array {
    $randomSub = $this->randomMachineName();
    return [
      [
        [],
        [],
        FALSE,
      ],
      [
        ['sub' => $randomSub],
        [],
        $randomSub,
      ],
      [
        [],
        ['sub' => $randomSub],
        $randomSub,
      ],
      [
        ['sub' => $this->randomMachineName()],
        ['sub' => $randomSub],
        FALSE,
      ],
    ];
  }

  /**
   * Test for the hasSetPassword method.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface|null $account
   *   The account to test or null if none provided.
   * @param bool $hasPermission
   *   Whether the account should have the correct permission
   *   to change their own password.
   * @param array $connectedAccounts
   *   The connected accounts array from the authMap method.
   * @param bool $expectedResult
   *   The result expected.
   *
   * @dataProvider dataProviderForHasSetPasswordAccess
   */
  public function testHasSetPasswordAccess(
    ?AccountProxyInterface $account,
    bool $hasPermission,
    array $connectedAccounts,
    bool $expectedResult
  ): void {
    if (empty($account)) {
      $this->currentUser->expects($this->once())
        ->method('hasPermission')
        ->with('openid connect set own password')
        ->willReturn($hasPermission);

      if (!$hasPermission) {
        $this->authMap->expects($this->once())
          ->method('getConnectedAccounts')
          ->with($this->currentUser)
          ->willReturn($connectedAccounts);
      }
    }
    else {
      $account->expects($this->once())
        ->method('hasPermission')
        ->with('openid connect set own password')
        ->willReturn($hasPermission);

      if (!$hasPermission) {
        $this->authMap->expects($this->once())
          ->method('getConnectedAccounts')
          ->with($account)
          ->willReturn($connectedAccounts);
      }
    }

    $actualResult = $this->openIdConnect->hasSetPasswordAccess($account);

    $this->assertEquals($expectedResult, $actualResult);
  }

  /**
   * Data provider for the testHasSetPasswordAccess method.
   *
   * @return array|array[]
   *   Data provider parameters for the testHasSetPassword() method.
   */
  public function dataProviderForHasSetPasswordAccess(): array {
    $connectedAccounts = [
      $this->randomMachineName() => 'sub',
    ];

    return [
      [
        $this->currentUser, FALSE, [], TRUE,
      ],
      [
        $this->currentUser, TRUE, [], TRUE,
      ],
      [
        NULL, TRUE, [], TRUE,
      ],
      [
        NULL, FALSE, [], TRUE,
      ],
      [
        $this->currentUser, FALSE, $connectedAccounts, FALSE,
      ],
      [
        $this->currentUser, TRUE, $connectedAccounts, TRUE,
      ],
      [
        NULL, TRUE, $connectedAccounts, TRUE,
      ],
      [
        NULL, FALSE, $connectedAccounts, FALSE,
      ],
    ];
  }

}
