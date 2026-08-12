<?php

namespace Drupal\ecz_vatsim_auth\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Vatsim\OAuth2\Client\Provider\Vatsim;
use Drupal\user\Entity\User;

class VatsimAuthController extends ControllerBase {

    private function getProvider() {
        return new Vatsim([
            'clientId'                => '1500',
            'clientSecret'            => '############',
            'redirectUri'             => 'https://curacao-rebuild.ddev.site/vatsim/callback',
            'scopes'                  => ['full_name', 'email', 'vatsim_details'],
            'domain'                  => 'https://auth-dev.vatsim.net',
        ]);
    }

    public function login(Request $request) {
        $provider = $this->getProvider();
        $authUrl = $provider->getAuthorizationUrl();
        $request->getSession()->set('oauth2state', $provider->getState());
        return new TrustedRedirectResponse($authUrl);
    }

    public function callback(Request $request) {
        $provider = $this->getProvider();
        $state = $request->query->get('state');
        $code = $request->query->get('code');
        $sessionState = $request->getSession()->get('oauth2state');

        if (empty($state) || ($state !== $sessionState)) {
            $request->getSession()->remove('oauth2state');
            $this->messenger()->addError('Invalid state. Please try logging in again.');
            return $this->redirect('<front>');
        }

        try {
            $accessToken = $provider->getAccessToken('authorization_code', [
                'code' => $code
            ]);

            $resourceOwner = $provider->getResourceOwner($accessToken);

            $cid = $resourceOwner->getId();
            $firstName = $resourceOwner->getFirstName();
            $lastName = $resourceOwner->getLastName();
            $email = $resourceOwner->getEmail();

            $fullName = trim("$firstName $lastName");

            $users = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['name' => $cid]);
            $account = reset($users);

            if (!$account) {
                $account = User::create([
                    'name' => $cid,
                    'mail' => $email,
                    'status' => 1,
                ]);
                $account->save();
            } else {
                if ($account->getEmail() !== $email) {
                    $account->setEmail($email);
                    $account->save();
                }
            }

            user_login_finalize($account);

            \Drupal::service('user.data')->set('ecz_vatsim_auth', $account->id(), 'full_name', $fullName);

            $this->messenger()->addStatus("Welcome to the East Caribbean Zone, $fullName!");

            return $this->redirect('<front>');

        } catch (\Exception $e) {
            $this->messenger()->addError('Failed to authenticate with VATSIM: ' . $e->getMessage());
            return $this->redirect('<front>');
        }
    }
}