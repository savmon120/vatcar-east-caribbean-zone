<?php

namespace Drupal\ecz_vatsim_auth\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\user\Entity\User;

/**
 * Provides a 'VATSIM User Navbar Item' Block.
 *
 * @Block(
 *   id = "ecz_vatsim_user_nav_block",
 *   admin_label = @Translation("VATSIM User Navbar Item"),
 *   category = @Translation("ECZ VATSIM")
 * )
 */
class VatsimUserNavBlock extends BlockBase {

    /**
     * {@inheritdoc}
     */
    public function build() {
        $current_user = \Drupal::currentUser();

        if ($current_user->isAuthenticated()) {
            $account = User::load($current_user->id());

            $fullName = \Drupal::service('user.data')->get('ecz_vatsim_auth', $current_user->id(), 'full_name');
            $displayName = !empty($fullName) ? $fullName : $account->getDisplayName();

            return [
                '#theme' => 'vatsim_user_nav',
                '#logged_in' => TRUE,
                '#user_name' => $displayName,
                '#cache' => [
                    'contexts' => ['user'],
                ],
            ];
        }

        return [
            '#theme' => 'vatsim_user_nav',
            '#logged_in' => FALSE,
            '#cache' => [
                'contexts' => ['user'],
            ],
        ];
    }

}