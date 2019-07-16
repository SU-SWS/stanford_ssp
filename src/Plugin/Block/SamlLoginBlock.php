<?php

namespace Drupal\stanford_ssp\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a 'SamlLoginBlock' block.
 *
 * @Block(
 *  id = "stanford_ssp_login_block",
 *  admin_label = @Translation("SUNetID Block"),
 * )
 */
class SamlLoginBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['link_text' => 'SUNetID Login'] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['link_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Text of the SUNetID link'),
      '#description' => $this->t('Here you can replace the text of the SUNetID link.'),
      '#default_value' => $this->configuration['link_text'],
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account, $return_as_object = FALSE) {
    $access = AccessResult::allowedIf($account->isAnonymous());
    return $return_as_object ? $access : $access->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['link_text'] = $form_state->getValue('link_text');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['saml_link'] = Link::createFromRoute($this->configuration['link_text'], 'simplesamlphp_auth.saml_login', [], ['attributes' => ['rel' => 'nofollow']])
      ->toRenderable();
    return $build;
  }

}
