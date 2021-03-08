<?php

namespace Drupal\openid_connect\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for building the OpenID Connect client instance add form.
 */
class OpenIDConnectClientAddController extends ControllerBase {

  /**
   * Build the OpenID Connect client instance add form.
   *
   * @param string $plugin_id
   *   The plugin ID for the OpenID Connect client instance.
   *
   * @return array
   *   The OpenID Connect client edit form.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function form(string $plugin_id): array {
    // Create an OpenID Connect client entity.
    $entity = $this->entityTypeManager()->getStorage('openid_connect_client')->create(['plugin' => $plugin_id]);

    return $this->entityFormBuilder()->getForm($entity, 'add');
  }

}
