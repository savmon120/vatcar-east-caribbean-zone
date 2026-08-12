<?php

declare(strict_types=1);

namespace Drupal\fir_sso\Service;

use Drupal\user\UserInterface;

/**
 * Defines a contract for live VATSIM profile data.
 *
 * Other modules (fir_bookings, fir_training, fir_events) must type-hint
 * against this interface rather than the concrete VatsimProfileSync class,
 * so that rating-dependent logic depends on a stable contract and never
 * reaches into fir_sso internals.
 */
interface VatsimProfileSyncInterface {

  /**
   * Syncs VATSIM profile data from an OAuth2 payload onto a user account.
   *
   * @param array $payload
   *   The decoded VATSIM Connect user data payload.
   * @param \Drupal\user\UserInterface $account
   *   The Drupal user account to update.
   */
  public function syncFromPayload(array $payload, UserInterface $account): void;

  /**
   * Returns the account's current VATSIM ATC rating.
   *
   * @param \Drupal\user\UserInterface $account
   *   The Drupal user account.
   *
   * @return int
   *   The current ATC rating.
   */
  public function getCurrentRating(UserInterface $account): int;

  /**
   * Returns the account's VATSIM division ID, if set.
   *
   * @param \Drupal\user\UserInterface $account
   *   The Drupal user account.
   *
   * @return string|null
   *   The division ID (e.g. "EMEA"), or NULL if the account has none.
   */
  public function getDivision(UserInterface $account): ?string;

}
