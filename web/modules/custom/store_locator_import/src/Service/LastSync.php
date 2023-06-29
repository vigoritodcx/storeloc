<?php

namespace Drupal\store_locator_import\Service;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\store_locator_import\Form\WSSettingsForm;

/**
 * Class LastSync.
 */
class LastSync {

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;
  
  const EDITABLEKEY='last_import';
  /**
   * Constructs a new LastSync object.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  public function setNewSync() {
      return $this->configFactory->getEditable(WSSettingsForm::CONFIG_NAME)->set(self::EDITABLEKEY, time())->save();
  }

  public function getLastSync() {
      return $this->configFactory->getEditable(WSSettingsForm::CONFIG_NAME)->get(self::EDITABLEKEY);
  }

}
