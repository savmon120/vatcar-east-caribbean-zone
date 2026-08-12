<?php

declare(strict_types=1);

namespace Drupal\fir_sso\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user\UserInterface;

/**
 * Concrete implementation of VatsimProfileSyncInterface.
 *
 * Applies VATSIM OAuth2 payload data to a Drupal user account. This service
 * centralises all VATSIM profile mapping logic so that other modules (bookings,
 * training, events) depend only on the stable interface contract.
 */
final class VatsimProfileSync implements VatsimProfileSyncInterface {

  /**
   * The user storage handler.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $userStorage;

  /**
   * Constructs a new VatsimProfileSync object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->userStorage = $entity_type_manager->getStorage('user');
  }

  /**
   * {@inheritdoc}
   */
  public function syncFromPayload(array $payload, UserInterface $account): void {
    // Extract CID.
    $cid = $payload['cid'] ?? NULL;
    if (!$cid) {
      throw new \InvalidArgumentException('VATSIM payload missing CID.');
    }

    // Ensure CID uniqueness: no other account may have this CID.
    $existing = $this->userStorage->loadByProperties(['field_vatsim_cid' => $cid]);
    foreach ($existing as $other) {
      if ((int) $other->id() !== (int) $account->id()) {
        throw new \RuntimeException(sprintf(
          'CID %s is already assigned to user ID %d.',
          $cid,
          $other->id()
        ));
      }
    }

    // Map basic fields.
    $account->set('field_vatsim_cid', $cid);
    $account->set('field_vatsim_first_name', $payload['name_first'] ?? '');
    $account->set('field_vatsim_last_name', $payload['name_last'] ?? '');

    // Rating is always overwritten — never merged.
    $rating = (int) ($payload['rating']['id'] ?? 0);
    $account->set('field_vatsim_rating', $rating);

    // Region / division / subdivision.
    $account->set('field_vatsim_region', $payload['region']['id'] ?? NULL);
    $account->set('field_vatsim_division', $payload['division']['id'] ?? NULL);
    $account->set('field_vatsim_subdivision', $payload['subdivision']['id'] ?? NULL);

    // Save the updated account.
    $account->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentRating(UserInterface $account): int {
    return (int) $account->get('field_vatsim_rating')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getDivision(UserInterface $account): ?string {
    $value = $account->get('field_vatsim_division')->value;
    return $value !== '' ? $value : NULL;
  }

}
