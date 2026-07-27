<?php

namespace Drupal\ecz_vatsim\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class VatsimSettingsForm extends ConfigFormBase {

    protected function getEditableConfigNames() {
        return ['ecz_vatsim.settings'];
    }

    public function getFormId() {
        return 'ecz_vatsim_settings_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state) {
        $config = $this->config('ecz_vatsim.settings');

        $form['feed_url'] = [
            '#type' => 'textfield',
            '#title' => $this->t('VATSIM Data Feed URL'),
            '#default_value' => $config->get('feed_url') ?: 'https://data.vatsim.net/v3/vatsim-data.json',
            '#required' => TRUE,
        ];

        $form['bookings_url'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Bookings API URL'),
            '#description' => $this->t('Endpoint for upcoming ATC bookings. Leave blank if not using.'),
            '#default_value' => $config->get('bookings_url'),
        ];

        $form['prefixes'] = [
            '#type' => 'textarea',
            '#title' => $this->t('FIR & Airport Prefixes'),
            '#description' => $this->t('Comma-separated list of prefixes to track (e.g., TNCM, TAPA, MDPC).'),
            '#default_value' => $config->get('prefixes') ?: 'TNCA, TNCB, TNCC, TNCM, TQPF, TNCS, TNCE, TNCF, TBPB, TFFF, TFFR, TAPA, TGPY, TVSA, TLPL, TDPD, TKPK, TTPP',
        ];

        $form['refresh_rate'] = [
            '#type' => 'number',
            '#title' => $this->t('Refresh Interval (Seconds)'),
            '#default_value' => $config->get('refresh_rate') ?: 60,
            '#min' => 15,
        ];

        return parent::buildForm($form, $form_state);
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
        $this->config('ecz_vatsim.settings')
            ->set('feed_url', $form_state->getValue('feed_url'))
            ->set('bookings_url', $form_state->getValue('bookings_url'))
            ->set('prefixes', $form_state->getValue('prefixes'))
            ->set('refresh_rate', $form_state->getValue('refresh_rate'))
            ->save();

        parent::submitForm($form, $form_state);
    }
}