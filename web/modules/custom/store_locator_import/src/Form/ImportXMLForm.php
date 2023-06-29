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
use Drupal\store_locator_import\Service\UserCountryService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user\Entity\User;


class ImportXMLForm extends FormBase
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
        return 'import_xml_form';
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
        $form['xml_file'] = array(
          '#type' => 'managed_file',
          '#name' => 'xml_file',
          '#title' => t('File'),
          '#size' => 20,
          '#required' => TRUE,
          '#autoupload' => TRUE,
          '#upload_location' => 'public://XML/',
          '#upload_validators' => ['file_validate_extensions' => ['xml']],
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
     *   The current state of the form.
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $fid = current($form_state->getValue('xml_file'));
        $file = File::load($fid);


        $query = \Drupal::entityQuery('node')
          ->condition('field_customerid', 'CSV-'. $form_state->getValue('country'), 'CONTAINS');
        $ids = $query->execute();

        $itemsToDelete = \Drupal::entityTypeManager()->getStorage('node')
          ->loadMultiple($ids);

        $stringIdToLog = '';
        foreach ($ids as $id) {
            $stringIdToLog .= $id . ',';
        }
        foreach ($itemsToDelete as $item) {
            $item->delete();
        }

        \Drupal::logger('store_locator_import')->notice('Store deleted:'.$stringIdToLog );


        $migration = \Drupal::service('plugin.manager.migration')->createInstance('store_locator_xml_import', [
          'source' => [
            'urls' => $file->getFileUri(),
            'selectedCountry' => $form_state->getValue('country'),
          ],
        ]);
        $executable = new \Drupal\migrate_tools\MigrateExecutable($migration, new MigrateMessage());
        $executable->import();

    }

}