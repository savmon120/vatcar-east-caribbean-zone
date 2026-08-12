<?php

declare(strict_types=1);

namespace Drupal\oauth2_client\Plugin\Oauth2GrantType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Utility\Error;
use Drupal\oauth2_client\Attribute\Oauth2GrantType;
use Drupal\oauth2_client\OAuth2\Client\OptionProvider\ClientCredentialsOptionProvider;
use Drupal\oauth2_client\Plugin\Oauth2Client\Oauth2ClientPluginInterface;
use League\OAuth2\Client\Token\AccessTokenInterface;

/**
 * Handles Client Credential Grants for the OAuth2 Client module..
 */
#[Oauth2GrantType(
  id: 'client_credentials',
  label: new TranslatableMarkup('Client Credential Grant'),
  description: new TranslatableMarkup('Makes Client Credential grant requests.'),
)]
class ClientCredentials extends Oauth2GrantTypePluginBase {

  /**
   * {@inheritdoc}
   */
  public function getAccessToken(Oauth2ClientPluginInterface $clientPlugin): ?AccessTokenInterface {
    $provider = $clientPlugin->getProvider();
    $optionProvider = $provider->getOptionProvider();
    // If the provider was just created, our OptionProvider must be set.
    if (!($optionProvider instanceof ClientCredentialsOptionProvider)) {
      $provider->setOptionProvider(new ClientCredentialsOptionProvider($clientPlugin));
    }
    try {
      return $provider->getAccessToken('client_credentials', $clientPlugin->getRequestOptions());
    }
    catch (\Exception $e) {
      // Failed to get the access token.
      Error::logException($this->logger, $e);
      return NULL;
    }
  }

}
