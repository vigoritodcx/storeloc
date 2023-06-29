<?php

namespace Drupal\store_locator\Plugin\views\style;

use Drupal\rest\Plugin\views\style\Serializer;

/**
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "store_list_serializer",
 *   title = @Translation("Store List Serializer"),
 *   help = @Translation("Custom serializer for the store list"),
 *   display_types = {"data"}
 * )
 */
class StoreListSerializer extends Serializer {

  public function render() {
    $rows = array();

    foreach ($this->view->result as $row_index => $row) {
      $this->view->row_index = $row_index;

      //converting current row into array.
      $rowAssoc = $this->serializer->normalize($this->view->rowPlugin->render($row));
      $distance = $rowAssoc['field_location_proximity'];

      $store_obj = \Drupal::entityTypeManager()->getStorage('node')->load($rowAssoc['nid']);

      //Converting to array.
      $store_assoc = $this->serializer->normalize($store_obj);
      $store_assoc['field_location_proximity'] = $distance;
      $store_assoc = $this->taxonomyFieldDataAlter($store_assoc);

      $rows[] = $store_assoc;
    }

    unset($this->view->row_index);

    // Get the content type configured in the display or fallback to the
    // default.
    if ((empty($this->view->live_preview))) {
      $content_type = $this->displayHandler->getContentType();
    }
    else {
      $content_type = !empty($this->options['formats']) ? reset($this->options['formats']) : 'json';
    }

    return $this->serializer->serialize($rows, $content_type, ['views_style_plugin' => $this]);
  }

  /**
   * Load term object.
   * @param string $tid
   * @return type
   */
  private function loadTerm($tid) {
    $term_data = [];
    $term = \Drupal\taxonomy\Entity\Term::load($tid);
    $term_data['id'] = $term->id();
    $term_data['name'] = $term->label();

    if (isset($term->field_codice_identificativo)) {
      $term_data['Codice identificativo'] = $term->field_codice_identificativo->getString();
    }
    if (isset($term->field_ci_cb)) {
      $term_data['Codice identificativo'] = $term->field_ci_cb->getString();
    }
    if (isset($term->field_ci_ta)) {
      $term_data['Codice identificativo'] = $term->field_ci_ta->getString();
    }

    return $term_data;
  }

  /**
   * Alter the taxonomy result and construct custom result.
   * @param array $rowAssoc
   * @return array
   */
  private function taxonomyFieldDataAlter($rowAssoc) {
    $store_field_taxonomy_type = ['field_apparecchiature', 'field_brandid', 'field_main_brandid', 'field_categoriacont', 'field_products', 'field_prodotto'];
    foreach ($store_field_taxonomy_type as $taxonomy_referenced_field) {
      $term = [];
      foreach ($rowAssoc[$taxonomy_referenced_field] as $field_brandid) {
        if (isset($field_brandid['target_id']) && isset($field_brandid['target_type'])) {
          //loading the term object
          $term[] = $this->loadTerm($field_brandid['target_id']);
        }
      }
      $rowAssoc[$taxonomy_referenced_field] = $term;
    }
    return $rowAssoc;
  }

}
