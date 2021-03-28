<?php

namespace Drupal\openid_connect;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Session service of the OpenID Connect module.
 */
class OpenIDConnectSession implements OpenIDConnectSessionInterface {

  /**
   * The config factory.
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
   * The session object.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * Construct an instance of the OpenID Connect session service.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination service.
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   The session object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RedirectDestinationInterface $redirect_destination, SessionInterface $session) {
    $this->configFactory = $config_factory;
    $this->redirectDestination = $redirect_destination;
    $this->session = $session;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): OpenIDConnectSession {
    return new static(
      $container->get('config.factory'),
      $container->get('redirect.destination'),
      $container->get('session')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function retrieveDestination(bool $clear = TRUE) : ?string {
    $ret = $this->session->get('openid_connect_destination');
    if ($clear) {
      $this->session->remove('openid_connect_destination');
    }
    return $ret;
  }

  /**
   * {@inheritdoc}
   */
  public function saveDestination() {
    // If the current request includes a 'destination' query parameter we'll use
    // that in the redirection. Otherwise use the current request path and
    // query.
    $destination = ltrim($this->redirectDestination->get(), '/');

    // Don't redirect to user/login. In this case redirect to the user profile.
    if (strpos($destination, ltrim(Url::fromRoute('user.login')->toString(), '/')) === 0) {
      $redirect_login = $this->configFactory->get('openid_connect.settings')->get('redirect_login');
      $destination = $redirect_login ?: 'user';
    }

    $this->session->set('openid_connect_destination', $destination);
  }

  /**
   * {@inheritdoc}
   */
  public function retrieveOp(bool $clear = TRUE): array {
    $ret = [
      'op' => $this->session->get('openid_connect_op'),
      'uid' => $this->session->get('openid_connect_uid'),
    ];
    if ($clear) {
      $this->session->remove('openid_connect_op');
      $this->session->remove('openid_connect_uid');
    }

    return $ret;
  }

  /**
   * {@inheritdoc}
   */
  public function saveOp(string $op, int $uid = NULL) {
    $this->session->set('openid_connect_op', $op);
    if (isset($uid)) {
      $this->session->set('openid_connect_uid', $uid);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function retrieveStateToken(bool $clear = TRUE) : ?string {
    $ret = $this->session->get('openid_connect_state');
    if ($clear) {
      $this->session->remove('openid_connect_state');
    }
    return $ret;
  }

  /**
   * {@inheritdoc}
   */
  public function saveStateToken(string $state) {
    $this->session->set('openid_connect_state', $state);
  }

}
