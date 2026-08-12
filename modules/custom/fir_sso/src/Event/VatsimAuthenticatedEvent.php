<?php

declare(strict_types=1);

namespace Drupal\fir_sso\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched by VatsimClient::storeAccessToken() after a successful VATSIM
 * token exchange. Carries the raw /api/user response so subscribers can
 * provision Drupal accounts without needing to know about the OAuth flow.
 *
 * Data shape:
 *   $data['data']['cid']
 *   $data['data']['personal']['name_first']
 *   $data['data']['personal']['name_last']
 *   $data['data']['personal']['email']
 *   $data['data']['vatsim']['rating']['short']
 *   $data['data']['vatsim']['region']['id']
 *   $data['data']['vatsim']['division']['id']
 *   $data['data']['vatsim']['subdivision']['id']
 */
final class VatsimAuthenticatedEvent extends Event {

  /**
   * Constructs a VatsimAuthenticatedEvent.
   *
   * @param array $vatsimData
   *   The decoded JSON response from VATSIM's /api/user endpoint.
   */
  public function __construct(private readonly array $vatsimData) {}

  /**
   * Returns the raw VATSIM user data array.
   *
   * @return array
   *   The full response array from /api/user.
   */
  public function getVatsimData(): array {
    return $this->vatsimData;
  }

}
