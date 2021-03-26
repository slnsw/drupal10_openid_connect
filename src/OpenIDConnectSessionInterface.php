<?php

namespace Drupal\openid_connect;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

/**
 * Creates and validates state tokens.
 *
 * @package Drupal\openid_connect
 */
interface OpenIDConnectSessionInterface extends ContainerInjectionInterface {

  /**
   * Get the destination redirect path from the session.
   *
   * @param bool $clear
   *   The value is cleared from the session, unless this is set to FALSE.
   *
   * @return string|null
   *   The destination path.
   */
  public function retrieveDestination(bool $clear = TRUE): ?string;

  /**
   * Save the current path in the session, for redirecting after authorization.
   *
   * @see \Drupal\openid_connect\Controller\OpenIDConnectRedirectController::authenticate()
   */
  public function saveDestination();

  /**
   * Get the operation details from the session.
   *
   * @param bool $clear
   *   The value is cleared from the session, unless this is set to FALSE.
   *
   * @return array
   *   The operation details.
   */
  public function retrieveOp(bool $clear = TRUE): array;

  /**
   * Save the operation details in the session.
   *
   * @param string $op
   *   The operation.
   * @param int|null $uid
   *   The user ID.
   */
  public function saveOp(string $op, int $uid = NULL);

  /**
   * Get the state token from the session.
   *
   * @param bool $clear
   *   The value is cleared from the session, unless this is set to FALSE.
   *
   * @return string|null
   *   The state token.
   */
  public function retrieveStateToken(bool $clear = TRUE): ?string;

  /**
   * Save the state token in the session.
   *
   * @param string $state
   *   The state token.
   */
  public function saveStateToken(string $state);

}
