<?php

namespace Drupal\stanford_ssp\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\simplesamlphp_auth\Form\LocalSettingsForm;

/**
 * Class LocalLoginForm.
 *
 * @package Drupal\stanford_ssp\Form
 */
class LocalLoginForm extends LocalSettingsForm {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    $names = parent::getEditableConfigNames();
    $names[] = 'stanford_ssp.settings';
    return $names;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('simplesamlphp_auth.settings');
    $stanford_config = $this->config('stanford_ssp.settings');

    // Hide the ability to allow default login. We don't want to users to
    // disable this because it can prevent User 1 from being able to log in
    // using drush.
    $form['authentication']['allow_default_login']['#type'] = 'hidden';
    $form['authentication']['allow_default_login']['#value'] = $config->get('allow.default_login');
    $form['authentication']['allow_default_login_users']['#type'] = 'hidden';
    $form['authentication']['allow_default_login_users']['#value'] = $config->get('allow.default_login_users');

    $form['authentication']['hide_local_login'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide local login form on the user page.'),
      '#default_value' => $stanford_config->get('hide_local_login'),
      '#weight' => '-10',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('stanford_ssp.settings')
      ->set('hide_local_login', $form_state->getValue('hide_local_login'))
      ->save();
  }

}
