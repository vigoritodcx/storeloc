<?php

namespace Drupal\store_locator_import\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_tools\Form\MigrationExecuteForm;
use Drupal\store_locator_import\Batch\StoreLocatorMigrateBatchExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This form is specifically for configuring process pipelines.
 */
class LocatorMigrationExecuteForm extends MigrationExecuteForm
{

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return parent::create($container);
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'locator_migration_execute_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {

        $form = parent::buildForm($form, $form_state);

//      add a new input to be able to set the limit per batch
        $form['operations']['limit'] = [
            '#type' => 'select',
            '#title' => $this->t('Limit'),
            '#options' => [
                1 => 1,
                25 => 25,
                50 => 50,
                100 => 100,
            ],
            '#default_value' => 25,
        ];

//       here I add a link to return to the migration list because otherwise you need to navigate back to that page
        $form['link'] = [
            '#type' => 'link',
            '#url' => Url::fromRoute('entity.migration.list', ['migration_group' => 'store_locator']),
            '#title' => $this->t('Migration list'),
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {

        $operation = $form_state->getValue('operation');

        if ($form_state->getValue('limit')) {
            $limit = $form_state->getValue('limit');
        } else {
            $limit = 25;
        }

        if ($form_state->getValue('update')) {
            $update = $form_state->getValue('update');
        } else {
            $update = 0;
        }
        if ($form_state->getValue('force')) {
            $force = $form_state->getValue('force');
        } else {
            $force = 0;
        }

        $migration = \Drupal::routeMatch()->getParameter('migration');
        if ($migration) {
            /** @var \Drupal\migrate\Plugin\MigrationInterface $migration_plugin */
            $migration_plugin = $this->migrationPluginManager->createInstance($migration->id(), $migration->toArray());
            $migrateMessage = new MigrateMessage();

            switch ($operation) {
                case 'import':

                    $options = [
                        'limit' => $limit,
                        'update' => $update,
                        'force' => $force,
                    ];

                    $executable = new StoreLocatorMigrateBatchExecutable($migration_plugin, $migrateMessage, $options);
                    $executable->batchImport();

                    break;

                case 'rollback':

                    $options = [
                        'limit' => $limit,
                        'update' => $update,
                        'force' => $force,
                    ];

                    $executable = new StoreLocatorMigrateBatchExecutable($migration_plugin, $migrateMessage, $options);
                    $executable->rollback();

                    break;

                case 'stop':

                    $migration_plugin->interruptMigration(MigrationInterface::RESULT_STOPPED);

                    break;

                case 'reset':

                    $migration_plugin->setStatus(MigrationInterface::STATUS_IDLE);

                    break;

            }
        }
    }

}
