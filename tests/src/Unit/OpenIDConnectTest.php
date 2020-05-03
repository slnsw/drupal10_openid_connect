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

  protected $configFactory;
  protected $authMap;
  protected $entityTypeManager;
  protected $entityFieldManager;
  protected $currentUser;
  protected $userData;
  protected $emailValidator;
  protected $messenger;
  protected $moduleHandler;
  protected $logger;
  protected $openIdConnect;


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


}
