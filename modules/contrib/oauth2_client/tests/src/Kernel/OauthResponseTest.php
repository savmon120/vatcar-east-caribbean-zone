<?php

declare(strict_types=1);

namespace src\Kernel;

use Drupal\Core\TempStore\PrivateTempStore;
use Drupal\Tests\oauth2_client\Kernel\Oauth2ClientKernelTestBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the OauthResponse controller.
 *
 * @group oauth2_client
 */
class OauthResponseTest extends Oauth2ClientKernelTestBase {

  /**
   * The Drupal tempstore.
   */
  protected PrivateTempStore $tempstore;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user', 'oauth2_client', 'oauth2_client_test_plugins'];

  /**
   * Set up the test.
   */
  public function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user_role');
    $this->installEntitySchema('user');
    $this->installConfig(['user']);
    $this->container
      ->get('module_handler')
      ->loadInclude('user', 'install');
    user_install();
    /** @var \Drupal\user\Entity\Role $anonymous */
    $anonymous = $this->entityTypeManager->getStorage('user_role')->load('anonymous');
    $anonymous->grantPermission('access content')->save();

    // Mock up the temp store to simulate state storage.
    $tempStore = $this->getMockBuilder(PrivateTempStore::class)
      ->disableOriginalConstructor()->onlyMethods(['get', 'set'])->getMock();
    $tempStore->expects($this->any())
      ->method('get')
      ->willReturn('test-state');
    $tempstoreFactory = $this->getMockBuilder('\Drupal\Core\TempStore\PrivateTempStoreFactory')
      ->disableOriginalConstructor()->onlyMethods(['get'])->getMock();
    $tempstoreFactory->expects($this->any())
      ->method('get')
      ->willReturn($tempStore);
    $this->container->set('tempstore.private', $tempstoreFactory);
    $this->tempstore = $tempStore;
  }

  /**
   * Test controller response.
   *
   * @covers \Drupal\oauth2_client\Controller\OauthResponse::code.
   * @covers \Drupal\oauth2_client\Controller\OauthResponse::getAuthCodeGrant
   */
  public function testResponse() {
    $app = $this->getApp('authcode_access_test');
    /** @var \Drupal\oauth2_client_test_plugins\Plugin\Oauth2Client\AuthCode $plugin */
    $plugin = $app->getClient();
    // Store query state.
    $this->tempstore->set('oauth2_client_state-' . $plugin->getPluginId(), 'test-state');
    $httpKernel = $this->container->get('http_kernel');

    $request = Request::create('/oauth2-client/' . $plugin->getPluginId() . '/code');
    $request->query->set('code', 'test-code');
    $request->query->set('state', 'test-state');
    $response = $httpKernel->handle($request);
    $this->assertInstanceOf(RedirectResponse::class, $response);
  }

}
