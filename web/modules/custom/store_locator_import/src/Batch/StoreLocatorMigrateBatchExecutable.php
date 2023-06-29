<?php
/**
 * Created by PhpStorm.
 * User: fcavallo
 * Date: 13/07/18
 * Time: 11.54
 */


namespace Drupal\store_locator_import\Batch;


use Drupal\migrate_tools\MigrateBatchExecutable;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\MigrateMessageInterface;
use Drupal\migrate\Plugin\Migration;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\store_locator_import\Service\LastSync;

class StoreLocatorMigrateBatchExecutable extends MigrateBatchExecutable
{

    /**
     * Helper to generate the batch operations for importing migrations.
     *
     * @param \Drupal\migrate\Plugin\MigrationInterface[] $migrations
     *   The migrations.
     * @param string $operation
     *   The batch operation to perform.
     * @param array $options
     *   The migration options.
     *
     * @return array
     *   The batch operations to perform.
     */
    protected function batchOperations(array $migrations, $operation, array $options = []) {
        $operations = [];
        foreach ($migrations as $id => $migration) {

            if (!empty($options['update'])) {
                $migration->getIdMap()->prepareUpdate();
            }

            if (!empty($options['force'])) {
                $migration->set('requirements', []);
            }
            else {
                $dependencies = $migration->getMigrationDependencies();
                if (!empty($dependencies['required'])) {
                    $required_migrations = $this->migrationPluginManager->createInstances($dependencies['required']);
                    // For dependent migrations will need to be migrate all items.
                    $dependent_options = $options;
                    $dependent_options['limit'] = 0;
                    $operations += $this->batchOperations($required_migrations, $operation, [
                        'limit' => 0,
                        'update' => $options['update'],
                        'force' => $options['force'],
                    ]);
                }
            }

            $operations[] = [
                '\Drupal\store_locator_import\Batch\StoreLocatorMigrateBatchExecutable::batchProcessImport',
                [$migration->id(), $options],
            ];
        }

        return $operations;
    }

    /**
     * Batch 'operation' callback.
     *
     * @param string $migration_id
     *   The migration id.
     * @param array $options
     *   The batch executable options.
     * @param array $context
     *   The sandbox context.
     */
    public static function batchProcessImport($migration_id, array $options, array &$context) {
        if (empty($context['sandbox'])) {
            $context['finished'] = 0;
            $context['sandbox'] = [];
            $context['sandbox']['total'] = 0;
            $context['sandbox']['counter'] = 0;
            $context['sandbox']['batch_limit'] = 0;
            $context['sandbox']['operation'] = StoreLocatorMigrateBatchExecutable::BATCH_IMPORT;
        }

        // Prepare the migration executable.
        $message = new MigrateMessage();
        /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
        $migration = \Drupal::getContainer()->get('plugin.manager.migration')->createInstance($migration_id);
        $executable = new StoreLocatorMigrateBatchExecutable($migration, $message, $options);

        if (empty($context['sandbox']['total'])) {
            $context['sandbox']['total'] = $executable->getSource()->count();
            $context['sandbox']['batch_limit'] = $executable->calculateBatchLimit($context);
            $context['results'][$migration->id()] = [
                '@numitems' => 0,
                '@created' => 0,
                '@updated' => 0,
                '@failures' => 0,
                '@ignored' => 0,
                '@name' => $migration->id(),
            ];
        }

        // Every iteration, we reset out batch counter.
        $context['sandbox']['batch_counter'] = 0;

        // Make sure we know our batch context.
        $executable->setBatchContext($context);

        // Do the import.
        $result = $executable->import();

        // Store the result; will need to combine the results of all our iterations.
        $context['results'][$migration->id()] = [
            '@numitems' => $context['results'][$migration->id()]['@numitems'] + $executable->getProcessedCount(),
            '@created' => $context['results'][$migration->id()]['@created'] + $executable->getCreatedCount(),
            '@updated' => $context['results'][$migration->id()]['@updated'] + $executable->getUpdatedCount(),
            '@failures' => $context['results'][$migration->id()]['@failures'] + $executable->getFailedCount(),
            '@ignored' => $context['results'][$migration->id()]['@ignored'] + $executable->getIgnoredCount(),
            '@name' => $migration->id(),
        ];

        // Do some housekeeping.
        if (
            $result != MigrationInterface::RESULT_INCOMPLETE
        ) {
            $context['finished'] = 1;
        }
        else {
            $context['sandbox']['counter'] = $context['results'][$migration->id()]['@numitems'];
            if ($context['sandbox']['counter'] <= $context['sandbox']['total']) {
                $context['finished'] = ((float) $context['sandbox']['counter'] / (float) $context['sandbox']['total']);
                $context['message'] = t('Importing %migration (@percent%).', [
                    '%migration' => $migration->label(),
                    '@percent' => (int) ($context['finished'] * 100),
                ]);
            }
        }

    }

    public static function batchFinishedImport($success, array $results, array $operations) {
        /** @var LastSync $lastSync */
        $lastSync = \Drupal::service('store_locator_import.service.last_sync');
        if ($success) {
            foreach ($results as $migration_id => $result) {
                $singular_message = "Processed 1 item (@created created, @updated updated, @failures failed, @ignored ignored) - done with '@name'";
                $plural_message = "Processed @numitems items (@created created, @updated updated, @failures failed, @ignored ignored) - done with '@name'";
                drupal_set_message(\Drupal::translation()->formatPlural($result['@numitems'],
                    $singular_message,
                    $plural_message,
                    $result));
                /* here we check if the store migration has been run and update the last run*/
                if ( $migration_id === 'store_locator_xml_import' ) {
                    $lastSync->setNewSync();
                }
            }
        }
    }

    /**
     * Calculates how much a single batch iteration will handle.
     *
     * @param array $context
     *   The sandbox context.
     *
     * @return float
     *   The batch limit.
     */
    public function calculateBatchLimit(array $context)
    {
        if ( !isset($this->itemLimit) ) {
            return ceil($context['sandbox']['total'] / 100);
        } else {
            return $this->itemLimit;
        }
    }
}