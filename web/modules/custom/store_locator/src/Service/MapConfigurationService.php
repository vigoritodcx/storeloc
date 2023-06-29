<?php
/**
 * Created by PhpStorm.
 * User: cgrimoldi
 * Date: 30/05/18
 * Time: 17.46
 */

namespace Drupal\store_locator\Service;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use \Drupal\field\FieldConfigInterface;
use Drupal\node\Entity\Node;

class MapConfigurationService
{

    private $entityTypeManager;

    public function __construct(EntityFieldManagerInterface $entityTypeManager)
    {
        $this->entityTypeManager = $entityTypeManager;
    }

    //@TODO: format configuration before return if necessary
  //@TODO: use cache
    public function get($id)
    {
      $config = null;
        // getcache('mapconfigcache_' . $nid)
      //exists => return
        $node = Node::load($id);

        if ($node && $node->bundle() == 'mapconfig') {
            $configs = [];
            $fields = $this->contentTypeFields('mapconfig');
            foreach ($fields as $fieldID => $field) {
                $configs[$fieldID] = $node->get($fieldID)->getValue();
            }
            $config = empty($configs) ? null : $this->formatConfiguration($configs);
        }
        //setcache('mapconfigcache_' . $nid, $config)
      return $config;
    }


    private function formatConfiguration($configs)
    {
        $field_select_brand = $this->getFieldToSelect($configs, 'field_select_brand');
        $field_select_product = $this->getFieldToSelect($configs, 'field_select_product');
        $field_select_tech = $this->getFieldToSelect($configs, 'field_select_tech');
        $field_filter_country = $this->getFieldToSelect($configs, 'field_filter_country', 'value');
        $field_circuito_boutique = $this->getFieldToSelect($configs, 'field_select_boutique');        

        $field_check_brand = (Boolean)$configs["field_check_brand"][0]['value'];
        $field_check_country = (Boolean)$configs["field_check_country"][0]['value'];
        $field_check_product = (Boolean)$configs["field_check_product"][0]['value'];
        $field_check_tech = (Boolean)$configs["field_check_tech"][0]['value'];
        $field_check_boutique = (Boolean)$configs["field_check_boutique"][0]['value'];

        $arrConfig = [];

        $arrConfig["brand"] = ["visible" => $field_check_brand, "collections" => $field_select_brand];
        $arrConfig["country"] = ["visible" => $field_check_country, "collections" => $field_filter_country];
        $arrConfig["product"] = ["visible" => $field_check_product, "collections" => $field_select_product];
        $arrConfig["tech"] = ["visible" => $field_check_tech, "collections" => $field_select_tech];
        $arrConfig["boutique"] = ["visible" => $field_check_boutique, "collections" => $field_circuito_boutique];
        $arrConfig["pinRicaduta"] = $configs["field_pin_ricaduta"];
        $arrConfig["pinSovrascrittura"] = $configs["field_pin_sovrascrittura"];
        $arrConfig["pinBoutique"] = $configs["field_pin_boutique"];
        $arrConfig["mapStyle"] =   $configs["field_maps_style"];
        $arrConfig["customHeadItem"] = $configs["field_custom_head_item"];
        $arrConfig["autocompleteFilter"] = $configs["field_geo_location_autocomplete"];


        if(count($configs["field_map_extra_code"]) > 0) $arrConfig["mapExtraCode"] =   $configs["field_map_extra_code"][0]['value'];
        if(count($configs["field_map_search_text"]) > 0) $arrConfig["searchTitle"] =   $configs["field_map_search_text"][0]['value'];
        if(count($configs["field_map_title"]) > 0) $arrConfig["mapTitle"] =   $configs["field_map_title"][0]['value'];
        if(count($configs["field_map_subtitle"]) > 0) $arrConfig["mapSubtitle"] =   $configs["field_map_subtitle"][0]['value'];
        if(count($configs["field_map_button_search"]) > 0) $arrConfig["buttonSearch"] =   $configs["field_map_button_search"][0]['value'];

        if(count($configs["field_main_color"]) > 0) $arrConfig["mainColor"] =   $configs["field_main_color"][0]['value'];
        if(count($configs["field_secondary_color"]) > 0) $arrConfig["secondaryColor"] =   $configs["field_secondary_color"][0]['value'];
        if(count($configs["field_header_image"]) > 0){
            $pinfile_id = $configs["field_header_image"][0]['target_id'];
            $file = \Drupal\file\Entity\File::load($pinfile_id);
            $uri = file_create_url($file->getFileUri());
            $arrConfig["headerImage"]['url']  = $uri;
            $arrConfig["headerImage"]['alt']  = $configs["field_header_image"][0]['alt'];
        }

        if(count($configs["field_brand_label"]) > 0) $arrConfig["brandLabel"] = $configs["field_brand_label"][0]['value'];
        if(count($configs["field_country_label"]) > 0) $arrConfig["countryLabel"] = $configs["field_country_label"][0]['value'];
        if(count($configs["field_product_label"]) > 0) $arrConfig["productLabel"] = $configs["field_product_label"][0]['value'];
        if(count($configs["field_location_label"]) > 0) $arrConfig["locationLabel"] = $configs["field_location_label"][0]['value'];
        if(count($configs["field_equipment_label"]) > 0) $arrConfig["equipmentLabel"] = $configs["field_equipment_label"][0]['value'];
        if(count($configs["field_title_label"]) > 0) $arrConfig["titleLabel"] = $configs["field_title_label"][0]['value'];
        if(count($configs["field_boutique_label"]) > 0) $arrConfig["boutiqueLabel"] = $configs["field_boutique_label"][0]['value'];
        if(count($configs["field_range"]) > 0) $arrConfig['map_range'] = $configs["field_range"][0]["value"];

        if(count($configs["field_show_map"]) > 0) $arrConfig["field_show_map"] = $configs["field_show_map"][0]['value'];
        if(count($configs["field_show_only_map"]) > 0) $arrConfig["field_show_only_map"] = $configs["field_show_only_map"][0]['value'];

        if(count($configs["field_logo_circuito_boutique"]) > 0){
          $arrConfig["logo_circuito_boutique"] = buildTermReferenceToArray($configs["field_logo_circuito_boutique"]);         
        }
        
        if(count($configs["field_show_geolocation_button"]) > 0){
          $arrConfig["show_geolocation_button"] = $configs["field_show_geolocation_button"][0]['value'];         
        }
        
        return $arrConfig;//$this->arrayToObject($arrConfig);
    }

    function arrayToObject($array)
    {
        if (!is_array($array)) {
            return $array;
        }

        $object = new \stdClass();
        if (is_array($array) && count($array) > 0) {
            foreach ($array as $name => $value) {
                $name = strtolower(trim($name));
                if (!empty($name)) {
                    $object->$name = $this->arrayToObject($value);
                }
            }
            return $object;
        } else {
            return FALSE;
        }
    }


    function getFieldToSelect($map_config, $fieldName, $arrValue = 'target_id')
    {
        $arrReturn = [];
        foreach ($map_config[$fieldName] as $item) {
            //$arrReturn[]=$item[$arrValue];
            array_push($arrReturn, $item[$arrValue]);
            //$arrReturn.=$item[$arrValue].',';
        }
        return $arrReturn;
    }
    
    

    /**
     * @param $contentType
     * @TODO: scrivere cosa fa
     * @return array|\Drupal\Core\Field\FieldDefinitionInterface[]
     */
    private function contentTypeFields($contentType)
    {
        $fields = [];
        if (!empty($contentType)) {
            $filter_definitions = $this->entityTypeManager->getFieldDefinitions('node', $contentType);
            $fields = array_filter(
                $filter_definitions,
                function ($field_definition) {
                    return $field_definition instanceof FieldConfigInterface;
                }
            );
        }

        return $fields;
    }
}
