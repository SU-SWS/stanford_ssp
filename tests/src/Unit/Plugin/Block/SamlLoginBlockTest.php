<?php

namespace Drupal\Tests\stanford_ssp\Unit\Plugin\Block;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\FormState;
use Drupal\Core\Session\AccountInterface;
use Drupal\stanford_ssp\Plugin\Block\SamlLoginBlock;
use Drupal\Tests\UnitTestCase;

/**
 * Class SamlLoginBlockTest
 *
 * @package Drupal\Tests\stanford_ssp\Unit\Plugin\Block
 * @covers \Drupal\stanford_ssp\Plugin\Block\SamlLoginBlock
 */
class SamlLoginBlockTest extends UnitTestCase {

  /**
   * The block plugin.
   *
   * @var \Drupal\stanford_ssp\Plugin\Block\SamlLoginBlock
   */
  protected $block;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    $this->block = new SamlLoginBlock([], 'saml_login', ['provider' => 'stanford_ssp']);
  }

  /**
   * Test configuration and form methods.
   */
  public function testBlock() {
    $this->assertEquals(['link_text' => 'SUNetID Login'], $this->block->defaultConfiguration());
    $form_state = new FormState();
    $form = $this->block->blockForm([], $form_state);
    $this->assertCount(1, $form);
    $this->assertArrayHasKey('link_text', $form);

    $link_text = $this->getRandomGenerator()->string();
    $form_state->setValue('link_text', $link_text);
    $this->block->blockSubmit($form, $form_state);
    $new_config = $this->block->getConfiguration();
    $this->assertEquals($link_text, $new_config['link_text']);
  }

  /**
   * Test anonymous users would access the block, authenticated would not.
   */
  public function testAccess() {
    $account = $this->createMock(AccountInterface::class);
    $account->method('isAnonymous')->willReturn(TRUE);
    $this->assertTrue($this->block->access($account));

    $account = $this->createMock(AccountInterface::class);
    $account->method('isAnonymous')->willReturn(FALSE);
    $this->assertFALSE($this->block->access($account));
  }

  /**
   * Test build render array is structured correctly.
   */
  public function testBuild() {
    $build = $this->block->build();
    $this->assertCount(1, $build);
    $this->assertArrayHasKey('saml_link', $build);
    $this->assertTrue($build['saml_link']['#type'] == 'link');
    $this->assertTrue($build['saml_link']['#title'] == 'SUNetID Login');
    $this->assertInstanceOf('\Drupal\Core\Url', $build['saml_link']['#url']);
  }

}
