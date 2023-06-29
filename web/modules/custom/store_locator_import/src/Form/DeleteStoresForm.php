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

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;


class DeleteStoresForm extends FormBase
{

  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'delete_store_form';
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

    $form['import_type'] = [
      '#type' => 'select',
      '#title' => 'import Type',
      '#options' => ['XML'],
    ];

    $form['timestamp'] = [
      '#type' => 'number',
      '#title' => 'Delete stores before this date',
      '#default_value' => time(),
    ];

    $form['limit'] = [
      '#type' => 'select',
      '#title' => 'Stores per patch',
      '#options' => [
        25 => 25,
        50 => 50,
        100 => 100,
        150 => 150,
        200 => 200,
        300 => 300,
      ],
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete Stores'),
      '#button_type' => 'primary',
      '#attributes' => array('onclick' => 'if(!confirm("Really Delete?")){return false;}')
    ];

    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\migrate\Plugin\Migration $migration*/
    $migration = \Drupal::service('plugin.manager.migration')->createInstance('store_locator_xml_import');
    if ( $migration->getStatus() !== 0 ) {
      $form_state->setErrorByName('migration', $this->t('store_locator_xml_import migration is already running.'));
    }
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
    /** @var DrupalDateTime $date_time*/

    $timestamp = $form_state->getValue('timestamp', 0);
    $limit = $form_state->getValue('limit', 25);

    $batch = [
      'title' => t('Deleting @bundle',
        ['@bundle' => 'store',]),
      'operations' => [
        ['sli_deleteByDate', ['node', 'store', $limit, $timestamp]],
      ],
      'finished' => 'sli_finish',
    ];

    batch_set($batch);


  }

}