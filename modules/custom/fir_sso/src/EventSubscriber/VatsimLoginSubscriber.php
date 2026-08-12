<?php

declare(strict_types=1);

namespace Drupal\fir_sso\EventSubscriber;

use Drupal\fir_sso\Event\VatsimAuthenticatedEvent;
use Drupal\fir_sso\Service\VatsimUserManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provisions and logs in a Drupal user after a successful VATSIM OAuth2 flow.
 *
 * This subscriber is triggered by VatsimClient::storeAccessToken(), which
 * dispatches VatsimAuthenticatedEvent immediately after receiving the access
 * token from VATSIM Connect. It delegates account creation/update to
 * VatsimUserManager, then calls user_login_finalize() to establish the session.
 *
 * NOTE: The oauth2_client module has no built-in event system. The event is
 * dispatched manually by the VatsimClient plugin, making this subscriber the
 * correct integration point for SSO business logic.
 */
class VatsimLoginSubscriber implements EventSubscriberInterface {

  /**
   * Constructs a VatsimLoginSubscriber.
   *
   * @param \Drupal\fir_sso\Service\VatsimUserManager $userManager
   *   The VATSIM user provisioning service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The fir_sso logger channel.
   */
  public function __construct(
    private readonly VatsimUserManager $userManager,
    private readonly LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      VatsimAuthenticatedEvent::class => 'onVatsimAuthenticated',
    ];
  }

  /**
   * Creates or updates the Drupal user account and starts the login session.
   *
   * @param \Drupal\fir_sso\Event\VatsimAuthenticatedEvent $event
   *   The authentication event carrying the raw VATSIM user data.
   */
  public function onVatsimAuthenticated(VatsimAuthenticatedEvent $event): void {
    try {
      $account = $this->userManager->provisionUser($event->getVatsimData());
      user_login_finalize($account);
      $this->logger->info('User @uid (@name) logged in via VATSIM Connect.', [
        '@uid' => $account->id(),
        '@name' => $account->getAccountName(),
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('VATSIM SSO login failed: @message', ['@message' => $e->getMessage()]);
      throw $e;
    }
  }

}
