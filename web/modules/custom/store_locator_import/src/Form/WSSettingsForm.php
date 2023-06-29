<?php

/**
 * Created by PhpStorm.
 * User: dg_dg_adminadmin
 * Date: 02/02/2018
 * Time: 09:38
 */

namespace Drupal\store_locator_import\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure example settings for this site.
 */
class WSSettingsForm extends ConfigFormBase
{
    const CONFIG_NAME = 'store_locator_import.settings';

    /**
     * @var MigrationInterface
     */
    protected $migration;

    public function __construct(
        ConfigFactoryInterface $config_factory,
        MigrationInterface $migration
    ) {
        parent::__construct($config_factory);
        $this->migration = $migration;
    }


    public static function create(ContainerInterface $container)
    {
        /** @var MigrationPluginManagerInterface $migrationManager */
        $migrationManager = $container->get('plugin.manager.migration');
        return new static(
        $container->get('config.factory'),
          $migrationManager->createInstance('store_locator_xml_import')
      );
    }

    /**
       * {@inheritdoc}
       */
    public function getFormId()
    {
        return str_replace('.', '_', self::CONFIG_NAME) . '_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $config = $this->config(self::CONFIG_NAME);
        $wsInfo = $config->get('wsinfo');
        $form['host'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Host with protocol'),
            '#default_value' => $wsInfo['host'],
        ];
        $form['basepath'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Endpoint basepath'),
            '#default_value' => $wsInfo['basepath'],
        ];
        $form['username'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Userneme'),
            '#default_value' => $wsInfo['username'],
        ];
        $form['password'] = [
            '#type' => 'password',
            '#title' => $this->t('Password'),
        ];
        $form['download_file'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Download new SAP file on import'),
            '#default_value' => (isset($wsInfo['download_file'])) ? $wsInfo['download_file'] : null,
        ];
        $form['last_import'] = [
            '#type' => 'number',
            '#title' => $this->t('Last Import'),
            '#default_value' => $config->get('last_import'),
        ];
        $form['force_update'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Force all stores to be updated'),
        ];

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        // Retrieve the configuration
        $config = $this->configFactory->getEditable(self::CONFIG_NAME)->get('wsinfo');
        $config = [
            'host' => $form_state->getValue('host'),
            'basepath' => $form_state->getValue('basepath'),
            'username' => $form_state->getValue('username'),
            'password' => ($form_state->getValue('password')) ? $form_state->getValue('password') : $config['password'],
            'download_file' => $form_state->getValue('download_file'),
        ];

        if ($form_state->getValue('force_update')) {
            $this->migration->getIdMap()->prepareUpdate();
        }

        $this->configFactory->getEditable(self::CONFIG_NAME)
            ->set('wsinfo', $config)
            ->set('last_import', $form_state->getValue('last_import'))
            ->save();

        parent::submitForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames()
    {
        return [self::CONFIG_NAME];
    }
}
