<?php

declare(strict_types = 1);

namespace Drupal\Tests\openid_connect\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\openid_connect\OpenIDConnectSession;
use Drupal\Tests\UnitTestCase;

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
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Mock the configuration factory service.
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);

    // Mock the 'redirect.destination' service.
    $this->redirectDestination = $this->createMock(RedirectDestinationInterface::class);
  }

  /**
   * Test the save destination method.
   */
  public function testSaveDestination(): void {
    // Get the expected session array.
    $expectedSession = $this->getExpectedSessionArray(
      self::TEST_PATH,
      self::TEST_QUERY
    );

    $destination = self::TEST_PATH . '?' . self::TEST_QUERY;
    // Mock the get method for the 'redirect.destination' service.
    $this->redirectDestination->expects($this->once())
      ->method('get')
      ->willReturn($destination);

    // Create a new OpenIDConnectSession class.
    $session = new OpenIDConnectSession($this->configFactory, $this->redirectDestination);

    // Call the saveDestination() method.
    $session->saveDestination();

    // Assert the $_SESSOIN global matches our expectation.
    $this->assertEquals($expectedSession, $_SESSION);
  }

  /**
   * Test the saveDestination() method with the /user/login path.
   */
  public function testSaveDestinationUserPath(): void {
    // Setup our expected results.
    $expectedSession = $this->getExpectedSessionArray(
      'user'
    );

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

    // Create a class to test with.
    $session = new OpenIDConnectSession($this->configFactory, $this->redirectDestination);

    // Call the saveDestination method.
    $session->saveDestination();

    // Assert the $_SESSION matches our expectations.
    $this->assertEquals($expectedSession, $_SESSION);
  }

  /**
   * Get the expected session array to compare.
   *
   * @param string $path
   *   The path that is expected in the session global.
   * @param string $queryString
   *   The query string that is expected in the session global.
   *
   * @return array
   *   The expected session array.
   */
  private function getExpectedSessionArray(string $path, string $queryString = ''): array {
    $destination = $path;
    if ($queryString) {
      $destination .= '?' . $queryString;
    }

    return [
      'openid_connect_destination' => ltrim($destination, '/'),
    ];
  }

}
