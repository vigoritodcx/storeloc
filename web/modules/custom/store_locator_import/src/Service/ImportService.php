<?php

namespace Drupal\store_locator_import\Service;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\csv_importer\Plugin\ImporterManager;

class ImportService
{

    /**
     * The entity field manager service.
     *
     * @var \Drupal\Core\Entity\EntityFieldManagerInterface
     */
    protected $entityFieldManager;

    /**
     * The importer plugin manager service.
     *
     * @var \Drupal\csv_importer\Plugin\ImporterManager
     */
    protected $importer;

    /**
     * Entity type manager.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * Constructs Parser object.
     *
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
     *   Entity type manager service.
     */
    public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, ImporterManager $importer) {
        $this->entityTypeManager = $entity_type_manager;
        $this->entityFieldManager = $entity_field_manager;
        $this->importer = $importer;
    }


    public function data($csv, $fieldsList) {
        $return = [];

        if ($csv && is_array($csv)) {
            $csv_fields = $csv[0];
            unset($csv[0]);
            foreach ($csv as $index => $data) {
                foreach ($data as $key => $content) {
                    if ($content) {
                        $content = Unicode::convertToUtf8($content, mb_detect_encoding($content));
                        $fields = explode('|', $csv_fields[$key]);
                        if (count($fields) > 1) {
                            $field = $fields[0];
                            foreach ($fields as $in) {
                                $return[$index][$field][$in] = $content;
                            }
                        }
                        else {


                            $return[$index][current($fields)] = $content;

                        }
                    }
                }
                if (isset($return[$index])) {
                    $return[$index] = array_intersect_key($return[$index], array_flip($fieldsList));
                }
            }
        }

        return $return;
    }

    public function add($content) { //, array &$context
        if (!$content) {
            return NULL;
        }
        $entity_type = 'node';
        $entity_type_bundle = 'store';
        $entity_definition = $this->entityTypeManager->getDefinition($entity_type);

        if ($entity_definition->hasKey('bundle') && $entity_type_bundle) {
            $content[$entity_definition->getKey('bundle')] = $entity_type_bundle;
        }

        $added = 0;
        $entity = $this->entityTypeManager->getStorage($entity_type, $entity_type_bundle)->create($content);

        try {
            $added = $entity->save();
        }
        catch (\Exception $e) {
            dpm($e->getMessage());
        }

        //if ($added) {
        //    $context['results'][] = $entity;
        //}
    }


    public function import($fileId)
    {

        $entity_type = 'store';
        $entity_type_bundle = NULL;


        // Contenuto letto dal csv
        $csv_parse = $this->getCsvById($fileId);
        $csv_entity = $this->getCsvEntity($fileId);

        // TODO: Controllo sui record
        // Elimino i record con id nullo


        $entity_fields = $this->getEntityTypeFields('node', 'store');


         $entities = $this->data($csv_parse, $entity_fields['fields']) ;
         foreach ($entities as $entity)
         {
             $this->add($entity);

         }
        //return;
/*
        $this->importer->createInstance('node_importer', [
            'csv' => $csv_parse,
            'csv_entity' => $csv_entity,
            'entity_type' => 'node',
            'entity_type_bundle' => 'store',
            'fields' => $entity_fields['fields'],
        ])->process();
*/

        /*
        - field.field.node.store.field_active
        - field.field.node.store.field_affiliateid
        - field.field.node.store.field_apparecchiature
        - field.field.node.store.field_authsitoistituzionale
        - field.field.node.store.field_brandid
        - field.field.node.store.field_categoriacont
        - field.field.node.store.field_codfiscale
        - field.field.node.store.field_company
        - field.field.node.store.field_contactname
        - field.field.node.store.field_customer
        - field.field.node.store.field_customerid
        - field.field.node.store.field_customerlevelid
        - field.field.node.store.field_descustomerlevelid
        - field.field.node.store.field_email
        - field.field.node.store.field_indirizzo
        - field.field.node.store.field_location
        - field.field.node.store.field_notes
        - field.field.node.store.field_phone
        - field.field.node.store.field_prodotto
        - field.field.node.store.field_url
        - field.field.node.store.field_vatregistrationid
        * */

    }

    public function getCsvEntity(int $id) {
        if ($id) {
            return $this->entityTypeManager->getStorage('file')->load($id);
        }

        return NULL;
    }

    public function getCsvById(int $id) {
        /* @var \Drupal\file\Entity\File $entity */
        $entity = $this->getCsvEntity($id);

        if ($entity && !empty($entity)) {
            return array_map('str_getcsv', file($entity->uri->getString()));
        }

        return NULL;
    }

    /**
     * Get entity type fields.
     *
     * @param string $entity_type
     *   Entity type.
     * @param string|null $entity_type_bundle
     *   Entity type bundle.
     *
     * @return array
     *   Entity type fields.
     */
    protected function getEntityTypeFields(string $entity_type, string $entity_type_bundle = NULL) {
        $fields = [];

        if (!$entity_type_bundle) {
            $entity_type_bundle = key($this->entityBundleInfo->getBundleInfo($entity_type));
        }

        $entity_fields = $this->entityFieldManager->getFieldDefinitions($entity_type, $entity_type_bundle);
        foreach ($entity_fields as $entity_field) {
            $fields['fields'][] = $entity_field->getName();

            if ($entity_field->isRequired()) {
                $fields['required'][] = $entity_field->getName();
            }
        }

        return $fields;
    }
}