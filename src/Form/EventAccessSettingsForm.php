<?php

namespace Drupal\event_access_unifi\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class EventAccessSettingsForm extends ConfigFormBase {

  const CONFIG_NAME = 'event_access_unifi.settings';

  public function getFormId() {
    return 'event_access_unifi_settings_form';
  }

  protected function getEditableConfigNames() {
    return [self::CONFIG_NAME];
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $cfg = $this->config(self::CONFIG_NAME);

    $form['api_host'] = [
      '#type' => 'textfield',
      '#title' => $this->t('UniFi Access API Host'),
      '#description' => $this->t('Example: https://192.168.1.2:12445'),
      '#default_value' => $cfg->get('api_host') ?? '',
      '#required' => TRUE,
    ];

    $form['api_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('UniFi Access API Token'),
      '#description' => $this->t('Developer token with visitor create permission.'),
      '#default_value' => $cfg->get('api_token') ?? '',
      '#required' => TRUE,
    ];

    $form['verify_ssl'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Verify SSL certificates'),
      '#default_value' => (bool) $cfg->get('verify_ssl'),
    ];

    $form['visitor_default_door_ids'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default door IDs (JSON array)'),
      '#description' => $this->t('Optional: restrict visitor to doors. Example: ["door-guid-1","door-guid-2"]'),
      '#default_value' => json_encode($cfg->get('visitor_default_door_ids') ?? []),
    ];

    $form['offset_before_start_minutes'] = [
      '#type' => 'number',
      '#title' => $this->t('Offset before start (minutes)'),
      '#default_value' => $cfg->get('offset_before_start_minutes') ?? 60,
      '#min' => 0,
    ];

    $form['window_from_start_minutes'] = [
      '#type' => 'number',
      '#title' => $this->t('Window length from start (minutes)'),
      '#default_value' => $cfg->get('window_from_start_minutes') ?? 180,
      '#min' => 15,
    ];

    $form['email_sender'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email sender (optional)'),
      '#description' => $this->t('Overrides default site mail if set.'),
      '#default_value' => $cfg->get('email_sender') ?? '',
    ];

    $form['email_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email subject'),
      '#default_value' => $cfg->get('email_subject') ?? 'Your event access',
      '#required' => TRUE,
    ];

    $form['email_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Email body'),
      '#default_value' => $cfg->get('email_body') ?? "Hello {{ name }},\n\nYour access pass for {{ event_title }} is ready.\n{% if qrUrl %}QR Code: {{ qrUrl }}{% endif %}\n{% if pin %}\nPIN: {{ pin }}\n{% endif %}\n\nValid window:\nFrom: {{ valid_from_human }}\nTo:   {{ valid_to_human }}\n\nYou can also open your pass here:\n{{ pass_link }}\n\nThanks,\nMakeHaven",
      '#rows' => 12,
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $door_ids_raw = (string) $form_state->getValue('visitor_default_door_ids');
    $door_ids = [];
    if ($door_ids_raw !== '') {
      try {
        $decoded = json_decode($door_ids_raw, TRUE, 512, JSON_THROW_ON_ERROR);
        if (!is_array($decoded)) {
          throw new \UnexpectedValueException('JSON must decode to an array.');
        }
        $door_ids = array_values(array_map('strval', $decoded));
      }
      catch (\Throwable $e) {
        $form_state->setErrorByName('visitor_default_door_ids', $this->t('Default door IDs must be valid JSON array of strings. @error', ['@error' => $e->getMessage()]));
      }
    }

    if ($form_state->getErrors()) {
      return;
    }

    $this->configFactory()->getEditable(self::CONFIG_NAME)
      ->set('api_host', rtrim((string) $form_state->getValue('api_host'), '/'))
      ->set('api_token', (string) $form_state->getValue('api_token'))
      ->set('verify_ssl', (bool) $form_state->getValue('verify_ssl'))
      ->set('visitor_default_door_ids', $door_ids)
      ->set('offset_before_start_minutes', (int) $form_state->getValue('offset_before_start_minutes'))
      ->set('window_from_start_minutes', (int) $form_state->getValue('window_from_start_minutes'))
      ->set('email_sender', (string) $form_state->getValue('email_sender'))
      ->set('email_subject', (string) $form_state->getValue('email_subject'))
      ->set('email_body', (string) $form_state->getValue('email_body'))
      ->save();
    parent::submitForm($form, $form_state);
  }
}
