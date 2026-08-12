<?php

namespace Drupal\oauth2_client\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines an Oauth2GrantType attribute object.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class Oauth2GrantType extends Plugin {

  /**
   * Constructs a Oauth2GrantType attribute.
   *
   * @param string $id
   *   The OAuth 2 plugin ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $label
   *   The human-readable name of the OAuth2 Client.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $description
   *   (optional) The description of the plugin.
   * @param class-string|null $deriver
   *   (optional) The deriver class.
   */
  public function __construct(
    public readonly string $id,
    public readonly TranslatableMarkup $label,
    public readonly ?TranslatableMarkup $description = NULL,
    public readonly ?string $deriver = NULL,
  ) {}

}
