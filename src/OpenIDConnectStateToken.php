<?php

namespace Drupal\openid_connect;

use Drupal\Component\Utility\Crypt;

/**
 * Creates and validates state tokens.
 *
 * @package Drupal\openid_connect
 */
class OpenIDConnectStateToken implements OpenIDConnectStateTokenInterface {

  /**
   * The OpenID Connect session service.
   *
   * @var \Drupal\openid_connect\OpenIDConnectSessionInterface
   */
  protected $session;

  /**
   * Construct an instance of the OpenID Connect state token service.
   *
   * @param \Drupal\openid_connect\OpenIDConnectSessionInterface $session
   *   The OpenID Connect session service.
   */
  public function __construct(OpenIDConnectSessionInterface $session) {
    $this->session = $session;
  }

  /**
   * {@inheritdoc}
   */
  public function generateToken(): string {
    $state = Crypt::randomBytesBase64();
    $this->session->saveStateToken($state);
    return $state;
  }

  /**
   * {@inheritdoc}
   */
  public function confirm(string $state_token): bool {
    $state = $this->session->retrieveStateToken();
    return !empty($state) && ($state_token == $state);
  }

}
