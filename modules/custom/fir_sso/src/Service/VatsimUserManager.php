<?php

declare(strict_types=1);

namespace Drupal\fir_sso\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user\UserInterface;
use Psr\Log\LoggerInterface;

/**
 * Creates and updates Drupal user accounts from VATSIM Connect user data.
 *
 * Called by VatsimLoginSubscriber. All user provisioning logic lives here so
 * the subscriber stays thin and this class is independently testable.
 */
class VatsimUserManager {

  /**
   * Constructs a VatsimUserManager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   The fir_sso logger channel.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly LoggerInterface $logger,
  ) {}

  /**
   * Creates or loads a Drupal user and syncs VATSIM profile fields.
   *
   * New accounts are given the 'controller' role. All accounts have their
   * VATSIM profile fields updated on every login so data stays current.
   *
   * @param array $vatsimData
   *   The decoded JSON response from VATSIM's /api/user endpoint.
   *
   * @return \Drupal\user\UserInterface
   *   The saved Drupal user account.
   *
   * @throws \RuntimeException
   *   If the VATSIM payload does not contain a CID.
   */
  public function provisionUser(array $vatsimData): UserInterface {
    $data = $vatsimData['data'] ?? [];
    $cid = (string) ($data['cid'] ?? '');

    if (empty($cid)) {
      throw new \RuntimeException('VATSIM authentication payload is missing the required CID field.');
    }

    $email = $data['personal']['email'] ?? '';
    $firstName = $data['personal']['name_first'] ?? '';
    $lastName = $data['personal']['name_last'] ?? '';
    $rating = $data['vatsim']['rating']['short'] ?? '';
    $region = $data['vatsim']['region']['id'] ?? '';
    $division = $data['vatsim']['division']['id'] ?? '';
    $subdivision = $data['vatsim']['subdivision']['id'] ?? '';

    $storage = $this->entityTypeManager->getStorage('user');
    $existing = $storage->loadByProperties(['field_vatsim_cid' => $cid]);

    /** @var \Drupal\user\UserInterface $account */
    if (empty($existing)) {
      // First login — create a new Drupal account keyed on the VATSIM CID.
      $account = $storage->create([
        'name' => $cid,
        'mail' => $email,
        'status' => 1,
        'roles' => ['controller'],
      ]);
      $this->logger->info('Created new Drupal account for VATSIM CID @cid.', ['@cid' => $cid]);
    }
    else {
      $account = reset($existing);
      $this->logger->info('Loaded existing Drupal account for VATSIM CID @cid.', ['@cid' => $cid]);
    }

    // Sync VATSIM profile fields on every login so they stay current.
    $account->set('field_vatsim_cid', $cid);
    $account->set('field_vatsim_first_name', $firstName);
    $account->set('field_vatsim_last_name', $lastName);
    $account->set('field_vatsim_rating', $rating);
    $account->set('field_vatsim_region', $region);
    $account->set('field_vatsim_division', $division);
    $account->set('field_vatsim_subdivision', $subdivision);

    $account->save();

    return $account;
  }

}
