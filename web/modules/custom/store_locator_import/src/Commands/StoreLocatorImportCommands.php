<?php

namespace Drupal\store_locator_import\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\Plugin\Migration;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\node\Entity\Node;
use \Drupal\store_locator_import\Batch\DrushMigrateBatchExecutable;
use Drupal\migrate_tools\MigrateBatchExecutable;
use Drupal\store_locator_import\Batch\StoreLocatorMigrateBatchExecutable;
use Drupal\store_locator_import\Service\XmlHandler;
use Drupal\taxonomy\Plugin\views\argument\Taxonomy;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class StoreLocatorImportCommands extends DrushCommands
{

  /**
   * Command description here.
   *
   * @usage store_locator_import-syncSap syncsap
   *   Usage description
   *
   * @command store_locator_import:syncSap
   * @param array $options
   * @aliases syncsap
   */
  public function syncSap(array $options = ['limit' => 250, 'update' => false])
  {
    /** @var Migration $migration_stores */
    /** @var XmlHandler $xmlHandler */

    $dateTime = new \DateTime();

    $this->io()->title(sprintf('Starting import %s', $dateTime->format(\DateTimeInterface::ATOM)));

    $xmlHandler = \Drupal::service('store_locator_import.service.xml_handler');
    $lastSync = \Drupal::service('store_locator_import.service.last_sync');

    /* get the last time the xml files have been downloaded */
    $date = date('Ymd', $lastSync->getLastSync());

    $xmlHandler->downloadAll(['yyyyMMdd' => $date]);

    $context = [
      'update' => (bool) $options['update'],
    ];

    if ( $limit = $options['limit'] ) {
      $context['limit'] = $limit;
    }

    /* initiate all of the migrations that will be used */
    $migrationManager = \Drupal::service('plugin.manager.migration');
    $migration_brands = $migrationManager->createInstance('brands_xml_import');
    $migration_stores = $migrationManager->createInstance('store_locator_xml_import');
    $migration_products = $migrationManager->createInstance('products_xml_import');

    /* make sure the migrations are set to idle */
    $migration_brands->setStatus(0);
    $migration_stores->setStatus(0);
    $migration_products->setStatus(0);

    $migrateMessage = new MigrateMessage();
    $executable = new DrushMigrateBatchExecutable($migration_stores, $migrateMessage, $context);

    /* add the migrations to the executable */
    $executable->addOperation($migration_products);
    $executable->addOperation($migration_brands);

    /* start the import */
    $executable->batchImport();

    $this->io()->success(sprintf('Finish import %s', $dateTime->format(\DateTimeInterface::ATOM)));

    return 0;
  }

}
