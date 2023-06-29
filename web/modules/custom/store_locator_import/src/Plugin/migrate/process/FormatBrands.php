<?php

namespace Drupal\store_locator_import\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Provides a 'FOrmatBrands' migrate process plugin.
 *
 * @MigrateProcessPlugin(
 *  id = "format_brands"
 * )
 */
class FormatBrands extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $value = strtolower($value);
    $return = $value;

    switch ($value) {
        case 'crossbrand':
            $return = 'brand';
            break;
        case 'circuito':
            $return = 'circuito_boutique';
            break;
    }
    return $return;
  }

}
