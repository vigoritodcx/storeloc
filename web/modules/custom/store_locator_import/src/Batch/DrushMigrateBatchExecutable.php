<?php

namespace Drupal\store_locator_import\Batch;

use Drupal\migrate\MigrateMessage;
use Drupal\migrate\MigrateMessageInterface;
use Drupal\migrate\Plugin\Migration;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_tools\MigrateExecutable;
use Drupal\store_locator_import\Service\LastSync;

/**
 * Defines a migrate executable class for batch migrations through UI.
 */
class DrushMigrateBatchExecutable extends MigrateExecutable
{

    /**
     * Representing a batch import operation.
     */
    const BATCH_IMPORT = 1;

    /**
     * Indicates if we need to update existing rows or skip them.
     *
     * @var int
     */
    protected $updateExistingRows = 0;

    /**
     * Indicates if we need import dependent migrations also.
     *
     * @var int
     */
    protected $checkDependencies = 0;

    /**
     * The current batch context.
     *
     * @var array
     */
    protected $batchContext = [];

    /**
     * Plugin manager for migration plugins.
     *
     * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
     */
    protected $migrationPluginManager;


    protected $operations = [];

    /**
     * {@inheritdoc}
     */
    public function __construct(MigrationInterface $migration, MigrateMessageInterface $message, array $options = [])
    {

        if (isset($options['update'])) {
            $this->updateExistingRows = $options['update'];
        }

        if (isset($options['force'])) {
            $this->checkDependencies = $options['force'];
        }

        parent::__construct($migration, $message, $options);
        $this->migrationPluginManager = \Drupal::getContainer()->get('plugin.manager.migration');
    }

    /**
     * Sets the current batch content so listeners can update the messages.
     *
     * @param array $context
     *   The batch context.
     */
    public function setBatchContext(array &$context)
    {
        $this->batchContext = &$context;
    }

    /**
     * Gets a reference to the current batch context.
     *
     * @return array
     *   The batch context.
     */
    public function &getBatchContext()
    {
        return $this->batchContext;
    }

    /**
     * Setup batch operations for running the migration.
     */
    public function batchImport()
    {
        $this->addOperation($this->migration);
        $operations = $this->operations;

        if (count($operations) > 0) {
            $batch = [
                'operations' => $operations,
                'title' => t('Migrating %migrate', ['%migrate' => $this->migration->label()]),
                'init_message' => t('Start migrating %migrate', ['%migrate' => $this->migration->label()]),
                'progress_message' => t('Migrating %migrate', ['%migrate' => $this->migration->label()]),
                'error_message' => t('An error occurred while migrating %migrate.', ['%migrate' => $this->migration->label()]),
                'finished' => '\Drupal\store_locator_import\Batch\DrushMigrateBatchExecutable::batchFinishedImport',
            ];

            batch_set($batch);
            $batch =& batch_get();

            //Because we are doing this on the back-end, we set progressive to false.
            $batch['progressive'] = FALSE;

            //Start processing the batch operations.
            drush_backend_batch_process();

        }
    }

    public function addOperation(MigrationInterface $migration) {

        if (!empty($this->updateExistingRows)) {
            $migration->getIdMap()->prepareUpdate();
        }

        if (!empty($this->checkDependencies)) {
            $migration->set('requirements', []);
        }

        $options = [
            'limit' => $this->itemLimit,
            'update' => $this->updateExistingRows,
            'force' => $this->checkDependencies,
        ];

        $this->operations[] = [
            '\Drupal\store_locator_import\Batch\DrushMigrateBatchExecutable::batchProcessImport',
            [$migration->id(), $options],
        ];
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
    public static function batchProcessImport($migration_id, array $options, &$contextObj)
    {
        $context = $contextObj->getArrayCopy();
        if (empty($context['sandbox'])) {
            $context['finished'] = 0;
            $context['sandbox'] = [];
            $context['sandbox']['total'] = 0;
            $context['sandbox']['counter'] = 0;
            $context['sandbox']['batch_limit'] = 0;
            $context['sandbox']['operation'] = DrushMigrateBatchExecutable::BATCH_IMPORT;
        }

        // Prepare the migration executable.
        $message = new MigrateMessage();
        /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
        $migration = \Drupal::getContainer()->get('plugin.manager.migration')->createInstance($migration_id);
        $executable = new DrushMigrateBatchExecutable($migration, $message, $options);

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
        } else {
            $context['sandbox']['counter'] = $context['results'][$migration->id()]['@numitems'];
            if ($context['sandbox']['counter'] <= $context['sandbox']['total']) {
                $context['finished'] = ((float)$context['sandbox']['counter'] / (float)$context['sandbox']['total']);
                $context['message'] = t('Importing %migration (@percent%).', [
                    '%migration' => $migration->label(),
                    '@percent' => (int)($context['finished'] * 100),
                ]);
            }
        }
        drush_print('Migration id: "' . $migration->id() . '" imported: ' . (int)($context['finished'] * 100) . '%' );
    }

    /**
     * Finished callback for import batches.
     *
     * @param bool $success
     *   A boolean indicating whether the batch has completed successfully.
     * @param array $results
     *   The value set in $context['results'] by callback_batch_operation().
     * @param array $operations
     *   If $success is FALSE, contains the operations that remained unprocessed.
     */
    public static function batchFinishedImport($success, array $results, array $operations)
    {
        /** @var LastSync $lastSync */
        if ($success) {
            foreach ($results as $migration_id => $result) {
                $singular_message = "Processed 1 item (@created created, @updated updated, @failures failed, @ignored ignored) - done with '@name'";
                $plural_message = "Processed @numitems items (@created created, @updated updated, @failures failed, @ignored ignored) - done with '@name'";
                drupal_set_message(\Drupal::translation()->formatPlural($result['@numitems'],
                    $singular_message,
                    $plural_message,
                    $result));
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function checkStatus()
    {
        $status = parent::checkStatus();

        if ($status == MigrationInterface::RESULT_COMPLETED) {
            // Do some batch housekeeping.
            $context = $this->getBatchContext();

            if (!empty($context['sandbox']) && $context['sandbox']['operation'] == DrushMigrateBatchExecutable::BATCH_IMPORT) {
                $context['sandbox']['batch_counter']++;
                if ($context['sandbox']['batch_counter'] >= $context['sandbox']['batch_limit']) {
                    $status = MigrationInterface::RESULT_INCOMPLETE;
                }
            }
        }

        return $status;
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
