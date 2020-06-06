<?php

/**
 * @file
 * UserPasswordFixture.php
 */

use Drupal\Core\File\FileSystemInterface;
use Drupal\user\UserInterface;
use PHPUnit\Framework\MockObject\MockObject;

const USER_REGISTER_ADMINISTRATORS_ONLY = 'admin_only';
const USER_REGISTER_VISITORS = 'visitors';
const USER_REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL = 'visitors_admin_approval';
const FILE_EXISTS_RENAME = FileSystemInterface::EXISTS_RENAME;

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

/**
 * This is a mock of the drupal_basename function.
 *
 * @param string $name
 *   The name of the file.
 *
 * @return string
 *   The basename for the file.
 */
function drupal_basename(string $name) {
  return 'test-file';
}

/**
 * Mock of the file_save_data function.
 *
 * @param string $data
 *   The data to save.
 * @param string|null $destination
 *   The destination to save.
 * @param int $replace
 *   Whether to replace the file or not.
 *
 * @return \PHPUnit\Framework\MockObject\MockObject
 *   Return a mock object that mimics the file_save_data.
 */
function file_save_data(
  $data,
  $destination = NULL,
  $replace = FileSystemInterface::EXISTS_RENAME
): MockObject {

  return $GLOBALS['oldFileMock'];
}
