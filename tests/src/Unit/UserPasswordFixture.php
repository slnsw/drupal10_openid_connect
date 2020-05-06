<?php

/**
 * @file
 * UserPasswordFixture.php
 */

/**
 * Override the user_password function if it does not exist..
 *
 * @return string
 *   Mocked password.
 */
function user_password() {
  return 'TestPassword123';
}
