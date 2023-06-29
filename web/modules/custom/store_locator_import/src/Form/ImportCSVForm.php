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
use Drupal\file\Entity\File;
use Drupal\migrate\Row;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessageInterface;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Event\MigrateEvents;

use Drupal\migrate_plus\Entity\Migration;
use Drupal\migrate_plus\Entity\MigrationGroup;
use Drupal\migrate_plus\Plugin\MigrationConfigEntityPluginManager;
use Drupal\migrate_tools\MigrateBatchExecutable;
use Drupal\store_locator_import\Batch\StoreLocatorMigrateBatchExecutable;
use Drupal\store_locator_import\Service\UserCountryService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user\Entity\User;


class ImportCSVForm extends FormBase
{

    private $userCountryService;

    public function __construct(UserCountryService $userCountryService)
    {
        $this->userCountryService = $userCountryService;

    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container) {
        return new static(
          $container->get('store_locator_import.service.usercountry')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'import_csv_form';
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


        $form['file_upload_details'] = array(
          '#markup' => t('<b>The File</b>'),
        );
        $form = array(
          '#attributes' => array('enctype' => 'multipart/form-data'),
        );

        $usernameFolder = $this->currentUser()->getUsername();
        $dateFolder =$myDate = date('m-d-Y');

        $form['csv_file'] = array(
          '#type' => 'managed_file',
          '#name' => 'csv_file',
          '#title' => t('File'),
          '#size' => 20,
          '#required' => TRUE,
          '#autoupload' => TRUE,
          '#upload_location' => 'private://CSV/' . $usernameFolder . '/' . $dateFolder,
          '#upload_validators' => ['file_validate_extensions' => ['csv']],
        );

        $form['country'] = array(
          '#type' => 'address_country',
          '#name' => 'country',
          '#title' => t('Country'),
          '#required' => TRUE,
          '#description' => t("Attention: when you upload a file, all the stores of the selected country, will be deleted. Remember to upload the complete stores' list of the selected country "),
        );

        if (!$this->currentUser()->hasPermission('store locator import all')) {
            $form['country']['#available_countries'] = $this->userCountryService
              ->getAvailableCountries(User::load($this->currentUser()->id()));
        }

        $userBrands = $this->userCountryService->getAvailableBrands(User::load($this->currentUser()->id()));

        $form['userBrands'] = array(
          '#type' => 'select',
          '#name' => 'userBrands',
          '#title' => t('Brand'),
          '#required' => TRUE,
          '#options' => $userBrands,
        );

      $form['items'] = array(
        '#type' => 'value',
        '#value' => 25
      );

      if($this->currentUser()->hasPermission('administer site configuration')){
        $counts = [5 => 5, 10 => 10, 25 => 25, 50 => 50, 100 => 100];

        $form['items'] = array(
          '#type' => 'select',
          '#title' => t('Number of items for cycle'),
          '#options' => $counts,
        );

      }

        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = array(
          '#type' => 'submit',
          '#value' => $this->t('Import'),
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
     *   The current state of the form.Fuser
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {


        $fid = current($form_state->getValue('csv_file'));
        $file = File::load($fid);

        $country = $form_state->getValue('country');
        $fileUri = $file->getFileUri();
        $brand = $form_state->getValue('userBrands');

        $batch = [
          'title' => t('Updating all @country @bundle',
            ['@entity_type' => 'node', '@bundle' => 'store',]),
          'operations' => [
            ['sli_delete', ['node', 'store', $form_state->getValue('items'), $country, $brand, $fileUri]],
            ['sli_startImportCSV', [$form_state->getValue('items'), $country, $brand, $fileUri]],
          ],
          'finished' => 'sli_finish',
        ];

        batch_set($batch);

    }
}