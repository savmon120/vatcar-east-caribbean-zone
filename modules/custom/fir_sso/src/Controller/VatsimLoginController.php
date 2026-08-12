<?php

declare(strict_types=1);

namespace Drupal\fir_sso\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\oauth2_client\PluginManager\Oauth2ClientPluginManager;
use Drupal\oauth2_client\Service\Oauth2ClientServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Initiates the VATSIM OAuth2 authorization code flow.
 */
class VatsimLoginController extends ControllerBase {

  public function __construct(
    private readonly Oauth2ClientServiceInterface $oauth2ClientService,
    private readonly Oauth2ClientPluginManager $pluginManager,
  ) {}

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('oauth2_client.service'),
      $container->get('oauth2_client.plugin_manager'),
    );
  }

  /**
   * Triggers the VATSIM OAuth2 redirect.
   *
   * Clears any stored token first so the auth code flow always starts fresh,
   * then calls getAccessToken() which throws AuthCodeRedirect
   * (EnforcedResponseException). Drupal's kernel intercepts that and issues
   * the HTTP redirect to the VATSIM authorization server.
   */
  public function login(): never {
    /** @var \Drupal\fir_sso\Plugin\Oauth2Client\VatsimClient $plugin */
    $plugin = $this->pluginManager->createInstance('vatsim');
    $plugin->clearAccessToken();
    $this->oauth2ClientService->getAccessToken('vatsim');
    throw new \RuntimeException('OAuth2 redirect did not occur.');
  }

}
