<?php

declare(strict_types=1);

namespace Drupal\Tests\oauth2_client\Kernel;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\oauth2_client\Entity\Oauth2Client;
use Drupal\oauth2_client\PluginManager\Oauth2ClientPluginManager;
use Drupal\oauth2_client\PluginManager\Oauth2GrantTypePluginManager;
use Drupal\oauth2_client\Service\Oauth2ClientService;

/**
 * Base class for building Oauth2Client kernel tests.
 */
abstract class Oauth2ClientKernelTestBase extends KernelTestBase {

  public const CLIENT_SECRET = 'client-secret-string';

  public const CLIENT_ID = 'client-id-string';

  public const STORAGE_KEY = 'oauth2-test-storage-id';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'oauth2_client', 'oauth2_client_test_plugins'];

  /**
   * Injected grant plugin manager.
   */
  protected Oauth2GrantTypePluginManager $grantPluginManager;

  /**
   * Injected entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Injected plugin manager.
   */
  protected Oauth2ClientPluginManager $clientPluginManager;

  /**
   * Injected service.
   */
  protected Oauth2ClientService $clientService;

  /**
   * Set up the test case.
   */
  public function setUp(): void {
    parent::setUp();
    /** @var \Drupal\Core\State\State $state */
    $state = $this->container->get('state');
    $entityManager = $this->container->get('entity_type.manager');
    $this->entityTypeManager = $entityManager;
    // Store test credentials.
    $credentials = [
      'client_id' => self::CLIENT_ID,
      'client_secret' => self::CLIENT_SECRET,
    ];
    $state->set(self::STORAGE_KEY, $credentials);

    // Setup services.
    $this->clientPluginManager = $this->container->get('oauth2_client.plugin_manager');
    $this->grantPluginManager = $this->container->get('plugin.manager.oauth2_grant_type');
    $this->clientService = $this->container->get('oauth2_client.service');
  }

  /**
   * Helper function that creates an Oauth2Client.
   *
   * @param string $id
   *   The entity id.
   *
   * @return \Drupal\oauth2_client\Entity\Oauth2Client
   *   A configured app.
   */
  public function getApp(string $id): Oauth2Client {
    // Trigger auto-populate.
    $this->clientPluginManager->getDefinitions();
    // Get the matching entity.
    $app = $this->entityTypeManager->getStorage('oauth2_client')->load($id);
    $this->assertInstanceOf(Oauth2Client::class, $app, 'Matching entity not generated.');
    $app->set('description', 'test_description');
    $app->set('credential_storage_key', self::STORAGE_KEY);
    $app->save();
    return $app;
  }

}
