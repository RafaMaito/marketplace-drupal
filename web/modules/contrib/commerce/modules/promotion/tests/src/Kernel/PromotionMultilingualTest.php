<?php

namespace Drupal\Tests\commerce_promotion\Kernel;

use Drupal\Tests\commerce_order\Kernel\OrderKernelTestBase;
use Drupal\commerce_promotion\Entity\Promotion;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests promotions in a multilingual context.
 *
 * @group commerce
 */
class PromotionMultilingualTest extends OrderKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'commerce_promotion',
    'language',
    'content_translation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('commerce_promotion');
    ConfigurableLanguage::createFromLangcode('fr')->save();
    ConfigurableLanguage::createFromLangcode('sr')->save();
  }

  /**
   * Tests that a promotion returns stores in current language.
   */
  public function testPromotionStores() {
    $this->container->get('content_translation.manager')
      ->setEnabled('commerce_store', 'online', TRUE);
    $this->store = $this->reloadEntity($this->store);
    $this->store->addTranslation('fr', [
      'name' => 'Magasin par défaut',
    ])->save();

    // Starts now, enabled. No end time.
    $promotion = Promotion::create([
      'name' => 'Promotion 1',
      'order_types' => 'default',
      'stores' => [$this->store->id()],
      'status' => TRUE,
    ]);

    $stores = $promotion->getStores();
    $this->assertEquals('Default store', reset($stores)->label());

    $this->config('system.site')->set('default_langcode', 'fr')->save();
    $stores = $promotion->getStores();
    $this->assertEquals('Magasin par défaut', reset($stores)->label());

    // Change the default site language and ensure the store is returned
    // even if it has not been translated to that language.
    $this->config('system.site')->set('default_langcode', 'sr')->save();
    $stores = $promotion->getStores();
    $this->assertEquals('Default store', reset($stores)->label());
  }

}
