<?php
/**
 * Created by PhpStorm.
 * User: cgrimoldi
 * Date: 13/07/18
 * Time: 23.49
 */

namespace Drupal\store_locator_import\Service;


use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;

class ApgWsBatchMigrateService {

  private $pluginManager;

  public function __construct(MigrationPluginManagerInterface $pluginManager) {
    $this->pluginManager = $pluginManager;
  }

  /**
   * @param $migrationId
   * @param $options
   * @TODO: sostituire con il batch
   */
  public function migrateData($migrationId, $options) {
    if (!$this->pluginManager->hasDefinition($migrationId)) {
      throw new \InvalidArgumentException(
        sprintf('The migration specified %s does not exist', $migrationId)
      );
    }

    try {
      $migration = $this->pluginManager
        ->createInstance($migrationId, $options);

      $executable = new \Drupal\migrate_tools\MigrateExecutable($migration, new MigrateMessage());
      $executable->import();
    }
    catch (PluginException | MigrateException $e) {
      throw new \RuntimeException("Can't initialize migration");
    }
  }
}