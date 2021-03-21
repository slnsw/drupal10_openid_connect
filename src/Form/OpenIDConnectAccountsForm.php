<?php

namespace Drupal\openid_connect\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\openid_connect\OpenIDConnectSession;
use Drupal\openid_connect\OpenIDConnectAuthmap;
use Drupal\openid_connect\OpenIDConnectClaims;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the user-specific OpenID Connect settings form.
 *
 * @package Drupal\openid_connect\Form
 */
class OpenIDConnectAccountsForm extends FormBase implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * The OpenID Connect session service.
   *
   * @var \Drupal\openid_connect\OpenIDConnectSession
   */
  protected $session;

  /**
   * The OpenID Connect authmap service.
   *
   * @var \Drupal\openid_connect\OpenIDConnectAuthmap
   */
  protected $authmap;

  /**
   * The OpenID Connect claims service.
   *
   * @var \Drupal\openid_connect\OpenIDConnectClaims
   */
  protected $claims;

  /**
   * The constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountProxy $current_user
   *   The current user account.
   * @param \Drupal\openid_connect\OpenIDConnectAuthmap $authmap
   *   The authmap storage.
   * @param \Drupal\openid_connect\OpenIDConnectClaims $claims
   *   The OpenID Connect claims.
   * @param \Drupal\openid_connect\OpenIDConnectSession $session
   *   The OpenID Connect service.
   */
  public function __construct(ConfigFactory $config_factory, EntityTypeManagerInterface $entity_type_manager, AccountProxy $current_user, OpenIDConnectAuthmap $authmap, OpenIDConnectClaims $claims, OpenIDConnectSession $session) {
    $this->setConfigFactory($config_factory);
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->authmap = $authmap;
    $this->claims = $claims;
    $this->session = $session;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): OpenIDConnectAccountsForm {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('openid_connect.authmap'),
      $container->get('openid_connect.claims'),
      $container->get('openid_connect.session')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'openid_connect_accounts_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, AccountInterface $user = NULL): array {
    $form_state->set('account', $user);

    /** @var \Drupal\openid_connect\OpenIDConnectClientEntityInterface[] $clients */
    $clients = $this->entityTypeManager->getStorage('openid_connect_client')->loadByProperties(['status' => TRUE]);

    $form['help'] = [
      '#prefix' => '<p class="description">',
      '#suffix' => '</p>',
    ];

    if (empty($clients)) {
      $form['help']['#markup'] = $this->t('No external account providers are available.');
      return $form;
    }
    elseif ($this->currentUser->id() == $user->id()) {
      $form['help']['#markup'] = $this->t('You can connect your account with these external providers.');
    }

    $connected_accounts = $this->authmap->getConnectedAccounts($user);

    foreach ($clients as $client) {
      $id = $client->getPluginId();
      $label = $client->label();

      $form[$id] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Provider: @title', ['@title' => $label]),
      ];
      $fieldset = &$form[$id];
      $connected = isset($connected_accounts[$id]);
      $fieldset['status'] = [
        '#type' => 'item',
        '#title' => $this->t('Status'),
      ];
      if ($connected) {
        $fieldset['status']['#markup'] = $this->t('Connected as %sub', [
          '%sub' => $connected_accounts[$id],
        ]);
        $fieldset['openid_connect_client_' . $id . '_disconnect'] = [
          '#type' => 'submit',
          '#value' => $this->t('Disconnect from @client_title', ['@client_title' => $label]),
          '#name' => 'disconnect__' . $id,
        ];
      }
      else {
        $fieldset['status']['#markup'] = $this->t('Not connected');
        $fieldset['openid_connect_client_' . $id . '_connect'] = [
          '#type' => 'submit',
          '#value' => $this->t('Connect with @client_title', ['@client_title' => $label]),
          '#name' => 'connect__' . $id,
          '#access' => $this->currentUser->id() == $user->id(),
        ];
      }
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($this->currentUser->id() !== $form_state->get('account')->id()) {
      $this->messenger()->addError($this->t("You cannot connect another user's account."));
      return;
    }

    list($op, $client_name) = explode('__', $form_state->getTriggeringElement()['#name'], 2);
    /** @var \Drupal\openid_connect\OpenIDConnectClientEntityInterface $entity */
    $client = $this->entityTypeManager->getStorage('openid_connect_client')->loadByProperties(['id' => $client_name])[$client_name];

    switch ($op) {
      case 'disconnect':
        $this->authmap->deleteAssociation($form_state->get('account')->id(), $client_name);
        $this->messenger()->addMessage($this->t('Account successfully disconnected from @client.', ['@client' => $client->label()]));
        break;

      case 'connect':
        $this->session->saveDestination();

        $plugin = $entity->getPlugin();
        $scopes = $this->claims->getScopes($plugin);
        $_SESSION['openid_connect_op'] = 'connect';
        $_SESSION['openid_connect_connect_uid'] = $this->currentUser->id();
        $response = $plugin->authorize($scopes);
        $form_state->setResponse($response);
        break;
    }
  }

  /**
   * Checks access for the OpenID-Connect accounts form.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user having accounts.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $user): AccessResultInterface {
    if ($this->currentUser->hasPermission('administer users')) {
      return AccessResult::allowed();
    }

    if ($this->currentUser->id() && $this->currentUser->id() === $user->id() &&
      $this->currentUser->hasPermission('manage own openid connect accounts')) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

}
