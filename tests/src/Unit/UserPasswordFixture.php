<?php

/**
 * @file
 * UserPasswordFixture.php
 */

use Drupal\user\UserInterface;

const USER_REGISTER_ADMINISTRATORS_ONLY = 'admin_only';
const USER_REGISTER_VISITORS = 'visitors';
const USER_REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL = 'visitors_admin_approval';

/**
 * Override the user_password function if it does not exist.
 *
 * @return string
 *   Mocked password.
 */
function user_password() {
  return 'TestPassword123';
}

/**
 * Override the user_login_finalize function.
 *
 * @param \Drupal\user\UserInterface $account
 *   The user account.
 */
function user_login_finalize(UserInterface $account) {
  $_SESSION['uid'] = $account->id();
}
