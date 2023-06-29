<?php
/**
 *
 *  Copyright (c) 2007-2018 "Posit sc" ( info@posit.it )
 *  Progetti Open Source Innovazione e Tecnologia
 *
 *  It is free software; you can redistribute it and/or modify it under
 *  the terms of the GNU Lesser General Public License, either version 3
 *  of the License, or (at your option) any later version.
 *
 *  All rights reserved
 *  DigitslMills
 *
 *  Initial version by: stefano
 *  Initial version created on: 12/06/18
 *
 */

namespace Drupal\store_locator_import\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\migrate\MigrateMessage;

use Drupal\migrate_plus\Entity\Migration;
use Drupal\migrate_tools\MigrateBatchExecutable;
use Drupal\store_locator_import\Service\LastSync;
use Drupal\store_locator_import\Service\UserCountryService;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\store_locator_import\Service\XmlHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;


class SyncSAPForm extends FormBase
{

    private $userCountryService;
    private $xmlHandler;
    protected $configFactory;
    protected $lastSync;

    public function __construct(UserCountryService $userCountryService, XmlHandler $xmlHandler, ConfigFactoryInterface $configFactory, LastSync $lastSync)
    {
        $this->userCountryService = $userCountryService;
        $this->xmlHandler = $xmlHandler;
        $this->configFactory = $configFactory;
        $this->lastSync = $lastSync;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('store_locator_import.service.usercountry'),
            $container->get('store_locator_import.service.xml_handler'),
            $container->get('config.factory'),
            $container->get('store_locator_import.service.last_sync')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'sync_sap_form';
    }

    /**
     * Form constructor.
     *
     * @param array $form
     *   An associative array containing the structure of the form.
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     *   The current state of the form.
     *
     * @return array
     *   The form structure.
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {

        $last_import = $this->lastSync->getLastSync();

        $form = [
            '#attributes' => array('enctype' => 'multipart/form-data'),
        ];

        $form['last_import'] = [
            '#type' => 'date',
            '#title' => $this->t('Last Import'),
            '#default_value' => date('Y-m-d', $last_import),
        ];

        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Sync from SAP'),
            '#button_type' => 'primary',
        );
        return $form;
    }

    /**
     * Form submission handler.
     *
     * @param array $form
     *   An associative array containing the structure of the form.
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     *   The current state of the form.
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        /** @var Migration $migration */
        $date = $form_state->getValue('last_import');
        $date = str_replace('-', '', $date);

        try {
          $this->xmlHandler->downloadAll(['yyyyMMdd' => $date]);
        } catch ( \Exception $e ) {
          $message = sprintf('Error downloading from SAP [Code: %d ] [Exception: %s ] [Date: %s]', $e->getCode(), $e->getMessage(), $date);
          $this->logger($message);
          \Drupal::messenger()->addError($message);
        }

        /*$limit = 100;
        $force = false;
        $update = true;

        $options = [
            'limit' => $limit,
            'update' => $update,
            'force' => $force,
        ];

        $migration = \Drupal::service('plugin.manager.migration')
            ->createInstance('store_locator_xml_import');

        $migrateMessage = new MigrateMessage();
        $test = $this->lastSync->setNewSync();

        $executable = new MigrateBatchExecutable($migration, $migrateMessage, $options);
        $executable->batchImport();*/

    }
}
