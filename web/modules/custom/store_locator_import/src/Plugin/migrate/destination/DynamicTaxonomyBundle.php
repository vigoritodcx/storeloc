<?php

namespace Drupal\store_locator_import\Plugin\migrate\destination;

use Drupal\migrate\Plugin\migrate\destination\EntityContentBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * @MigrateDestination(
 *   id = "taxonomy_term_dynamic_bundle"
 * )
 */

class DynamicTaxonomyBundle extends EntityContentBase {

    protected static function getEntityTypeId($plugin_id) {
        return 'taxonomy_term';
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
        return parent::create($container, $configuration, $plugin_id, $plugin_definition, $migration);
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntity(Row $row, array $old_destination_id_values)
    {
        $entity = parent::getEntity($row, $old_destination_id_values);
        if ( $row->hasDestinationProperty('vid') ) {
            $vid = $row->getDestinationProperty('vid');
            $entity->set('vid', $vid);
        }
        return $entity;
    }
}