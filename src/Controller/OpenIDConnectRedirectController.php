<?php

namespace Drupal\openid_connect\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Url;
use Drupal\openid_connect\OpenIDConnectClientEntityInterface;
use Drupal\openid_connect\OpenIDConnectSessionInterface;
use Drupal\openid_connect\Plugin\OpenIDConnectClientInterface;
use Drupal\openid_connect\OpenIDConnect;
use Drupal\openid_connect\OpenIDConnectStateTokenInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Redirect controller.
 *
 * @package Drupal\openid_connect\Controller
 */
class OpenIDConnectRedirectController extends ControllerBase implements AccessInterface {

  /**
   * The OpenID state token service.
   *
   * @var \Drupal\openid_connect\OpenIDConnectStateTokenInterface
   */
  protected $stateToken;

  /**
   * The request stack used to access request globals.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The OpenID Connect service.
   *
   * @var \Drupal\openid_connect\OpenIDConnect
   */
  protected $openIDConnect;

  /**
   * The OpenID Connect session service.
   *
   * @var \Drupal\openid_connect\OpenIDConnectSessionInterface
   */
  protected $session;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The constructor.
   *
   * @param \Drupal\openid_connect\OpenIDConnect $openid_connect
   *   The OpenID Connect service.
   * @param \Drupal\openid_connect\OpenIDConnectStateTokenInterface $state_token
   *   The OpenID state token service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\openid_connect\OpenIDConnectSessionInterface $session
   *   The OpenID Connect session service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(OpenIDConnect $openid_connect, OpenIDConnectStateTokenInterface $state_token, RequestStack $request_stack, LoggerChannelFactoryInterface $logger_factory, OpenIDConnectSessionInterface $session, ConfigFactoryInterface $config_factory) {
    $this->openIDConnect = $openid_connect;
    $this->stateToken = $state_token;
    $this->requestStack = $request_stack;
    $this->loggerFactory = $logger_factory;
    $this->session = $session;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): OpenIDConnectRedirectController {
    return new static(
      $container->get('openid_connect.openid_connect'),
      $container->get('openid_connect.state_token'),
      $container->get('request_stack'),
      $container->get('logger.factory'),
      $container->get('openid_connect.session'),
      $container->get('config.factory')
    );
  }

  /**
   * Access callback: Redirect page.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   Whether the state token matches the previously created one that is stored
   *   in the session.
   */
  public function access(): AccessResultInterface {
    // Confirm anti-forgery state token. This round-trip verification helps to
    // ensure that the user, not a malicious script, is making the request.
    $request = $this->requestStack->getCurrentRequest();
    $state_token = $request->get('state');
    if ($state_token && $this->stateToken->confirm($state_token)) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

  /**
   * Redirect.
   *
   * @param \Drupal\openid_connect\OpenIDConnectClientEntityInterface $openid_connect_client
   *   The client.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response starting the authentication request.
   *
   * @throws \Exception
   */
  public function authenticate(OpenIDConnectClientEntityInterface $openid_connect_client): RedirectResponse {
    $request = $this->requestStack->getCurrentRequest();

    // Get parameters from the session, and then clean up.
    list($op, $uid) = $this->session->retrieveOp();
    if (!isset($op)) {
      $op = 'login';
      $uid = NULL;
    }

    $destination = $this->session->retrieveDestination() ?: $this->configFactory->get('openid_connect.settings')->get('redirect_login');

    $plugin = $openid_connect_client->getPlugin();
    if (!$request->get('error') && (!($plugin instanceof OpenIDConnectClientInterface) || !$request->get('code'))) {
      // In case we don't have an error, but the client could not be loaded or
      // there is no state token specified, the URI is probably being visited
      // outside of the login flow.
      throw new NotFoundHttpException();
    }

    $provider_param = ['@provider' => $openid_connect_client->label()];

    if ($request->get('error')) {
      if (in_array($request->get('error'), [
        'interaction_required',
        'login_required',
        'account_selection_required',
        'consent_required',
      ])) {
        // If we have an one of the above errors, that means the user hasn't
        // granted the authorization for the claims.
        $this->messenger()->addWarning($this->t('Logging in with @provider has been canceled.', $provider_param));
      }
      else {
        // Any other error should be logged. E.g. invalid scope.
        $variables = [
          '@error' => $request->get('error'),
          '@details' => $request->get('error_description') ? $request->get('error_description') : $this->t('Unknown error.'),
        ];
        $message = 'Authorization failed: @error. Details: @details';
        $this->loggerFactory->get('openid_connect_' . $openid_connect_client->getPluginId())->error($message, $variables);
        $this->messenger()->addError($this->t('Could not authenticate with @provider.', $provider_param));
      }
    }
    else {
      // Process the login or connect operations.
      $tokens = $plugin->retrieveTokens($request->get('code'));
      if ($tokens) {
        if ($op === 'login') {
          $success = $this->openIDConnect->completeAuthorization($plugin, $tokens, $destination);

          if (!$success) {
            // Check Drupal user register settings before saving.
            $register = $this->config('user.settings')->get('register');
            // Respect possible override from OpenID-Connect settings.
            $register_override = $this->config('openid_connect.settings')
              ->get('override_registration_settings');
            if ($register === UserInterface::REGISTER_ADMINISTRATORS_ONLY && $register_override) {
              $register = UserInterface::REGISTER_VISITORS;
            }

            switch ($register) {
              case UserInterface::REGISTER_ADMINISTRATORS_ONLY:
              case UserInterface::REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL:
                // Skip creating an error message, as completeAuthorization
                // already added according messages.
                break;

              default:
                $this->messenger()->addError($this->t('Logging in with @provider could not be completed due to an error.', $provider_param));
                break;
            }
          }
        }
        elseif (($op === 'connect') && ($uid === $this->currentUser()->id())) {
          $success = $this->openIDConnect->connectCurrentUser($plugin, $tokens);
          if ($success) {
            $this->messenger()->addMessage($this->t('Account successfully connected with @provider.', $provider_param));
          }
          else {
            $this->messenger()->addError($this->t('Connecting with @provider could not be completed due to an error.', $provider_param));
          }
        }
      }
      else {
        $this->messenger()->addError($this->t('Failed to get authentication tokens for @provider. Check logs for further details.', $provider_param));
      }
    }

    // The destination parameter should be a prepared uri and include any query
    // parameters or fragments already.
    //
    // @see \Drupal\openid_connect\OpenIDConnectSession::saveDestination()
    $redirect = Url::fromUri('internal:/' . ltrim($destination, '/'))->toString();
    return new RedirectResponse($redirect);
  }

}
