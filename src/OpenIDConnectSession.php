<?php

namespace Drupal\openid_connect;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;

/**
 * Session service of the OpenID Connect module.
 */
class OpenIDConnectSession {

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The redirect destination service.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * Construct an instance of the OpenID Connect session service.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RedirectDestinationInterface $redirect_destination) {
    $this->configFactory = $config_factory;
    $this->redirectDestination = $redirect_destination;
  }

  /**
   * Save the current path in the session, for redirecting after authorization.
   *
   * @todo Evaluate, whether we can now use the user.private_tempstore instead
   *   of the global $_SESSION variable, as https://www.drupal.org/node/2743931
   *   has been applied to 8.5+ core.
   *
   * @see \Drupal\openid_connect\Controller\OpenIDConnectRedirectController::authenticate()
   */
  public function saveDestination() {
    // If the current request includes a 'destination' query parameter we'll use
    // that in the redirection. Otherwise use the current request path and
    // query.
    $destination = ltrim($this->redirectDestination->get(), '/');

    // Don't redirect to user/login. In this case redirect to the user profile.
    if (strpos($destination, 'user/login') === 0) {
      $redirect_login = $this->configFactory->get('openid_connect.settings')->get('redirect_login');
      $destination = $redirect_login ?: 'user';
    }

    $_SESSION['openid_connect_destination'] = $destination;
  }

}
