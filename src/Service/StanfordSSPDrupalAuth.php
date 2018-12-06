<?php

namespace Drupal\stanford_ssp\Service;

use Drupal\simplesamlphp_auth\Service\SimplesamlphpAuthManager;
use Drupal\simplesamlphp_auth\Service\SimplesamlphpDrupalAuth;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;
use Psr\Log\LoggerInterface;
use Drupal\externalauth\ExternalAuthInterface;

class StanfordSSPDrupalAuth extends SimplesamlphpDrupalAuth {

  const ROLE_REEVALUATE = 1;

  const ROLE_ADDITIVE = 2;

  /**
   * Original Simplesamlphp_auth service.
   *
   * @var \Drupal\simplesamlphp_auth\Service\SimplesamlphpDrupalAuth
   */
  protected $samlAuth;

  /**
   * {@inheritdoc}
   */
  public function __construct(SimplesamlphpDrupalAuth $saml_auth, SimplesamlphpAuthManager $simplesaml_auth, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, LoggerInterface $logger, ExternalAuthInterface $externalauth, AccountInterface $account) {
    $this->samlAuth = $saml_auth;
    parent::__construct($simplesaml_auth, $config_factory, $entity_type_manager, $logger, $externalauth, $account);
  }

  /**
   * {@inheritdoc}
   */
  public function externalLoginRegister($authname) {
    $account = $this->externalauth->login($authname, 'simplesamlphp_auth');
    if (!$account) {
      $account = $this->externalRegister($authname);
    }

    if ($account) {
      // Determine if roles should be evaluated upon login.
      switch ($this->config->get('role.eval_every_time')) {
        case self::ROLE_REEVALUATE:
          $this->roleMatchSync($account);
          break;

        case self::ROLE_ADDITIVE:
          $this->roleMatchAdd($account);
          break;
      }
    }

    return $account;
  }

  /**
   * {@inheritdoc}
   *
   * At the time of this comment, the parent service does not remove any roles
   * of the user. It only adds roles. This is a good thing for us at this time
   * but a bug report will eventually change this. So let's be proactive and
   * implement our own methods in the preparation of that issue being fixed.
   *
   * @see https://www.drupal.org/project/simplesamlphp_auth/issues/2894327
   */
  public function roleMatchAdd(UserInterface $account, $force_save = FALSE) {
    // Get matching roles based on retrieved SimpleSAMLphp attributes.
    $matching_roles = $this->getMatchingRoles();

    if ($matching_roles || $force_save) {
      foreach ($matching_roles as $role_id) {
        if ($this->config->get('debug')) {
          $this->logger->debug('Adding role %role to user %name', [
            '%role' => $role_id,
            '%name' => $account->getAccountName(),
          ]);
        }
        $account->addRole($role_id);
      }
      $account->save();
    }

  }

  /**
   * Completely resync all roles on the user account.
   *
   * @param \Drupal\user\UserInterface $account
   *   The Drupal user to sync roles to.
   */
  public function roleMatchSync(UserInterface $account) {
    // Remove all the user's current roles before adding the matching roles.
    foreach ($account->getRoles(TRUE) as $role_id) {
      $account->removeRole($role_id);
    }
    $this->roleMatchAdd($account, TRUE);
  }

}
