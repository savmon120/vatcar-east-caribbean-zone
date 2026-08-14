<?php

declare(strict_types=1);

namespace Drupal\fir_sso\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\user\Entity\User;

/**
 * Provides a VATSIM User Menu Block for the navbar.
 *
 * @Block(
 *   id = "fir_sso_user_menu",
 *   admin_label = @Translation("VATSIM User Menu"),
 *   category = @Translation("FIR SSO")
 * )
 */
class VatsimUserMenuBlock extends BlockBase {

    /**
     * {@inheritdoc}
     */
    public function build(): array {
        $current_user = \Drupal::currentUser();

        if ($current_user->isAuthenticated()) {
            $account = User::load($current_user->id());

            // Pull data directly from the fields this new module created
            $firstName = $account->get('field_vatsim_first_name')->value ?? '';
            $lastName = $account->get('field_vatsim_last_name')->value ?? '';
            $cid = $account->get('field_vatsim_cid')->value ?? $account->getAccountName();
            $rating = $account->get('field_vatsim_rating')->value ?? '';

            $displayName = trim("$firstName $lastName");
            if (empty($displayName)) {
                $displayName = $cid; // Fallback just in case
            }

            return [
                '#theme' => 'fir_sso_user_menu',
                '#logged_in' => TRUE,
                '#user_name' => $displayName,
                '#cid' => $cid,
                '#rating' => $rating,
                '#cache' => [
                    'contexts' => ['user'],
                ],
            ];
        }

        return [
            '#theme' => 'fir_sso_user_menu',
            '#logged_in' => FALSE,
            '#cache' => [
                'contexts' => ['user'],
            ],
        ];
    }

}