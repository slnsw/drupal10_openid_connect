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
   * Get the destination redirect path and langcode from the session.
   *
   * @param bool $clear
   *   The value is cleared from the session, unless this is set to FALSE.
   *
   * @return array
   *   The destination path and langcode.
   */
  public function retrieveDestination(bool $clear = TRUE): array;

  /**
   * Save the current path and langcode, for redirecting after authorization.
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
   * Get the id token from the session.
   *
   * @param bool $clear
   *   The value is cleared from the session, if this is set to TRUE.
   *
   * @return string|null
   *   The id token.
   */
  public function retrieveIdToken(bool $clear = FALSE): ?string;

  /**
   * Save the id token in the session.
   *
   * @param string $token
   *   The id token.
   */
  public function saveIdToken(string $token);

  /**
   * Get the access token from the session.
   *
   * @param bool $clear
   *   The value is cleared from the session, if this is set to TRUE.
   *
   * @return string|null
   *   The access token.
   */
  public function retrieveAccessToken(bool $clear = FALSE): ?string;

  /**
   * Save the access token in the session.
   *
   * @param string $token
   *   The access token.
   */
  public function saveAccessToken(string $token);

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
   * @param string $token
   *   The state token.
   */
  public function saveStateToken(string $token);

}
