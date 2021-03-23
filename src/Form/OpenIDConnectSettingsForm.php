<?php

namespace Drupal\openid_connect\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\openid_connect\OpenIDConnect;
use Drupal\openid_connect\OpenIDConnectClaims;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the OpenID Connect settings form.
 *
 * @package Drupal\openid_connect\Form
 */
class OpenIDConnectSettingsForm extends ConfigFormBase {

  /**
   * The OpenID Connect service.
   *
   * @var \Drupal\openid_connect\OpenIDConnect
   */
  protected $openIDConnect;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The OpenID Connect claims.
   *
   * @var \Drupal\openid_connect\OpenIDConnectClaims
   */
  protected $claims;

  /**
   * OpenID Connect client plugins.
   *
   * @var \Drupal\openid_connect\Plugin\OpenIDConnectClientInterface[]
   */
  protected static $clients;

  /**
   * The constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\openid_connect\OpenIDConnect $openid_connect
   *   The OpenID Connect service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\openid_connect\OpenIDConnectClaims $claims
   *   The claims.
   */
  public function __construct(ConfigFactoryInterface $config_factory, OpenIDConnect $openid_connect, EntityFieldManagerInterface $entity_field_manager, OpenIDConnectClaims $claims) {
    parent::__construct($config_factory);
    $this->openIDConnect = $openid_connect;
    $this->entityFieldManager = $entity_field_manager;
    $this->claims = $claims;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('openid_connect.openid_connect'),
      $container->get('entity_field.manager'),
      $container->get('openid_connect.claims')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['openid_connect.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'openid_connect_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $settings = $this->configFactory()
      ->getEditable('openid_connect.settings');

    $form['always_save_userinfo'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Save user claims on every login'),
      '#description' => $this->t('If disabled, user claims will only be saved when the account is first created.'),
      '#default_value' => $settings->get('always_save_userinfo'),
    ];

    $form['connect_existing_users'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Automatically connect existing users'),
      '#description' => $this->t('If disabled, authentication will fail for existing email addresses.'),
      '#default_value' => $settings->get('connect_existing_users'),
    ];

    $form['override_registration_settings'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Override registration settings'),
      '#description' => $this->t('If enabled, user creation will always be allowed, even if the registration setting is set to require admin approval, or only allowing admins to create users.'),
      '#default_value' => $settings->get('override_registration_settings'),
    ];

    $form['user_login_display'] = [
      '#type' => 'radios',
      '#title' => $this->t('OpenID buttons display in user login form'),
      '#options' => [
        'hidden' => $this->t('Hidden'),
        'above' => $this->t('Above'),
        'below' => $this->t('Below'),
        'replace' => $this->t('Replace'),
      ],
      '#description' => $this->t("Modify the user login form to show the the OpenID login buttons. If the 'Replace' option is selected, only the OpenID buttons will be displayed. In this case, pass the 'showcore' URL parameter to return to a password-based login form."),
      '#default_value' => $settings->get('user_login_display'),
    ];

    $form['redirects'] = [
      '#title' => $this->t('Redirects'),
      '#type' => 'fieldset',
    ];

    $form['redirects']['redirect_login'] = [
      '#title' => $this->t('Login'),
      '#type' => 'textfield',
      '#description' => $this->t('Path to redirect to on client login'),
      '#default_value' => $settings->get('redirect_login'),
    ];

    $form['userinfo_mappings'] = [
      '#title' => $this->t('User claims mapping'),
      '#type' => 'fieldset',
      '#tree' => TRUE,
    ];

    $properties = $this->entityFieldManager->getFieldDefinitions('user', 'user');
    $properties_skip = $this->openIDConnect->userPropertiesIgnore();
    $claims = $this->claims->getOptions();
    $mappings = $settings->get('userinfo_mappings');
    foreach ($properties as $property_name => $property) {
      if (isset($properties_skip[$property_name])) {
        continue;
      }
      // Always map the timezone.
      $default_value = '';
      if ($property_name == 'timezone') {
        $default_value = 'zoneinfo';
      }

      $form['userinfo_mappings'][$property_name] = [
        '#type' => 'select',
        '#title' => $property->getLabel(),
        '#description' => $property->getDescription(),
        '#options' => (array) $claims,
        '#empty_value' => '',
        '#empty_option' => $this->t('- No mapping -'),
        '#default_value' => isset($mappings[$property_name]) ? $mappings[$property_name] : $default_value,
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('openid_connect.settings')
      ->set('always_save_userinfo', $form_state->getValue('always_save_userinfo'))
      ->set('connect_existing_users', $form_state->getValue('connect_existing_users'))
      ->set('override_registration_settings', $form_state->getValue('override_registration_settings'))
      ->set('user_login_display', $form_state->getValue('user_login_display'))
      ->set('redirect_login', $form_state->getValue('redirect_login'))
      ->set('userinfo_mappings', array_filter($form_state->getValue('userinfo_mappings')))
      ->save();
  }

}
