<?php

declare(strict_types = 1);

namespace Drupal\Tests\openid_connect\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\openid_connect\OpenIDConnectSession;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @coversDefaultClass \Drupal\openid_connect\OpenIDConnectSession
 * @group openid_connect
 */
class OpenIdConnectSessionTest extends UnitTestCase {

  /**
   * Create a test path for testing.
   */
  const TEST_PATH = '/test/path/1';

  /**
   * The user login path for testing.
   */
  const TEST_USER_PATH = '/user/login';

  /**
   * A query string to test with.
   */
  const TEST_QUERY = 'sport=baseball&team=reds';

  /**
   * A mock of the config.factory service.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $configFactory;

  /**
   * A mock of the redirect.destination service.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $redirectDestination;

  /**
   * A mock of the session service.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $session;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Mock the configuration factory service.
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    // Mock the 'redirect.destination' service.
    $this->redirectDestination = $this->createMock(RedirectDestinationInterface::class);
    // Mock the 'session' service.
    $this->session = $this->createMock(SessionInterface::class);
  }

  /**
   * Test the saveDestination method.
   */
  public function testSaveDestination(): void {
    // Get the expected destination.
    $expectedDestination = self::TEST_PATH . '?' . self::TEST_QUERY;

    // Mock the get method for the 'redirect.destination' service.
    $this->redirectDestination->expects($this->once())
      ->method('get')
      ->willReturn($expectedDestination);

    // Mock the get method for the 'session' service.
    $this->session->expects($this->once())
      ->method('get')
      ->willReturn($expectedDestination);

    // Create a new OpenIDConnectSession class.
    $session = new OpenIDConnectSession($this->configFactory, $this->redirectDestination, $this->session);

    // Call the saveDestination() method.
    $session->saveDestination();

    // Call the retrieveDestination method.
    $destination = $session->retrieveDestination();

    // Assert the destination matches our expectation.
    $this->assertEquals($expectedDestination, $destination);
  }

  /**
   * Test the saveDestination() method with the /user/login path.
   */
  public function testSaveDestinationUserPath(): void {
    // Setup our expected results.
    $expectedDestination = 'user';

    $immutableConfig = $this
      ->createMock(ImmutableConfig::class);

    $this->configFactory->expects($this->once())
      ->method('get')
      ->with('openid_connect.settings')
      ->willReturn($immutableConfig);

    // Mock the get method with the user login path.
    $this->redirectDestination->expects($this->once())
      ->method('get')
      ->willReturn(self::TEST_USER_PATH);

    // Mock the get method for the 'session' service.
    $this->session->expects($this->once())
      ->method('get')
      ->willReturn($expectedDestination);

    // Create a class to test with.
    $session = new OpenIDConnectSession($this->configFactory, $this->redirectDestination, $this->session);

    // Call the saveDestination method.
    $session->saveDestination();

    // Call the retrieveDestination method.
    $destination = $session->retrieveDestination();

    // Assert the destination matches our expectations.
    $this->assertEquals($expectedDestination, $destination);
  }

}
