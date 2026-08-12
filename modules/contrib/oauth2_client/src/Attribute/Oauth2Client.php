<?php

namespace Drupal\oauth2_client\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines an Oauth2Client attribute object.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class Oauth2Client extends Plugin {

  /**
   * Constructs a Oauth2Client attribute.
   *
   * @param string $id
   *   The OAuth 2 plugin ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $name
   *   The human-readable name of the OAuth2 Client.
   * @param string $grant_type
   *   The grant type of the OAuth2 authorization. Possible values are
   *   'authorization_code', 'client_credentials', and 'resource_owner'.
   * @param string $authorization_uri
   *   The authorization endpoint of the OAuth2 server.
   * @param string $token_uri
   *   The token endpoint of the OAuth2 server.
   * @param string $resource_owner_uri
   *   (optional) The Resource Owner Details endpoint.
   * @param array|null $scopes
   *   (optional) The set of scopes for the provider to use by default.
   * @param string $scope_separator
   *   (optional) The separator used to join the scopes in the OAuth2 query
   *   string.
   * @param array $request_options
   *   (optional) A set of additional parameters on the token request.
   *   The array key will be used as the request parameter:
   *   @code
   *    request_options: [
   *      'parameter' => 'value',
   *    ],
   *   @endcode
   * @param bool $success_message
   *   (optional) A flag that may be used by
   *   Oauth2ClientPluginInterface::storeAccessToken. Implementations may
   *   conditionally display a message on successful storage.
   * @param array|null $collaborators
   *   (optional) An associative array of classes that are composed into the
   *   provider. Allowed keys are:
   *     - grantFactory
   *     - requestFactory
   *     - httpClient
   *     - optionProvider.
   * @param class-string|null $deriver
   *   (optional) The deriver class.
   */
  public function __construct(
    public readonly string $id,
    public readonly TranslatableMarkup $name,
    public readonly string $grant_type,
    public readonly string $authorization_uri,
    public readonly string $token_uri,
    public readonly string $resource_owner_uri = '',
    public readonly ?array $scopes = NULL,
    public readonly string $scope_separator = ',',
    public readonly array $request_options = [],
    public readonly bool $success_message = FALSE,
    public readonly ?array $collaborators = NULL,
    public readonly ?string $deriver = NULL,
  ) {}

}
