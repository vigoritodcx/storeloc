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

class ExportForm extends FormBase
{

    private $userCountryService;

    public function __construct(UserCountryService $userCountryService)
    {
        $this->userCountryService = $userCountryService;
    }

    public static function create(ContainerInterface $container)
    {
        return new static($container->get('store_locator_import.service.usercountry'));
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'export_form';
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
        $form['country'] = [
          '#type' => 'address_country',
          '#name' => 'country',
          '#title' => t('Country'),
          '#required' => true,
          '#description' => t('Select Country'),
        ];

        if (!$this->currentUser()->hasPermission('store locator export all')) {
            $form['country']['#available_countries'] = $this->userCountryService
              ->getAvailableCountries(User::load($this->currentUser()->id()));
        }
        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = [
          '#type' => 'submit',
          '#value' => $this->t('Export'),
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
        $checkCountry = in_array($form_state->getValue('country'), $this->userCountryService
          ->getAvailableCountries(User::load($this->currentUser()->id())));
        if ($this->currentUser()->hasPermission('store locator export') && $checkCountry) {
            $form_state->setRedirect('view.csv_export.data_export_1',
              ['arg_0' => $form_state->getValue('country')]);
        }

    }

}