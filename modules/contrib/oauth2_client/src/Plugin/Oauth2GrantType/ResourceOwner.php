<?php

declare(strict_types=1);

namespace Drupal\oauth2_client\Plugin\Oauth2GrantType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Utility\Error;
use Drupal\oauth2_client\Attribute\Oauth2GrantType;
use Drupal\oauth2_client\OwnerCredentials;
use Drupal\oauth2_client\Plugin\Oauth2Client\Oauth2ClientPluginInterface;
use League\OAuth2\Client\Token\AccessTokenInterface;

/**
 * Handles Client Credential Grants for the OAuth2 Client module..
 */
#[Oauth2GrantType(
  id: 'resource_owner',
  label: new TranslatableMarkup('Resource Owner Grant'),
  description: new TranslatableMarkup('Makes Resource Owner grant requests.')
)]
class ResourceOwner extends Oauth2GrantTypePluginBase implements GrantWithCredentialsInterface {

  /**
   * Username and password.
   *
   * @var \Drupal\oauth2_client\OwnerCredentials
   */
  private OwnerCredentials $userCredentials;

  /**
   * {@inheritdoc}
   */
  public function getAccessToken(Oauth2ClientPluginInterface $clientPlugin): ?AccessTokenInterface {
    $provider = $clientPlugin->getProvider();
    if (empty($this->userCredentials)) {
      throw new \RuntimeException('Missing username and password for grant plugin ' . $this->getPluginId());
    }
    $options = $clientPlugin->getRequestOptions([
      'username' => $this->userCredentials->getUsername(),
      'password' => $this->userCredentials->getPassword(),
    ]);
    try {
      return $provider->getAccessToken('password', $options);
    }
    catch (\Exception $e) {
      // Failed to get the access token.
      Error::logException($this->logger, $e);
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setUsernamePassword(OwnerCredentials $credentials): void {
    $this->userCredentials = $credentials;
  }

}
