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
use Drupal\store_locator_import\Service\ApCrmService;
use Drupal\store_locator_import\Service\UserCountryService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;

class ForceNodeSaveForm extends FormBase
{

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'force_node_save_form';
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
        $form['type'] = ['#type' => 'value', '#value' => 'store'];
        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = [
          '#type' => 'submit',
          '#value' => $this->t('Force SAVE on all nodes'),
          '#button_type' => 'primary',
        ];

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
        $entity_type = $form_state->getValue('type');
        $batch = [
            'title' => t('Save store nodes to get coordinates'),
            'operations' => [
                [[get_class(), 'sliForceNodeSave'], [50]],
            ],
        ];

        batch_set($batch);
    }

    public static function sliForceNodeSave($limit = 25, &$context)
    {
        \Drupal::moduleHandler()->load('store_locator_import');
        sli_relocate($limit, $context);
    }

}