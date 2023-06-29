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

namespace Drupal\store_locator\Service;


class XmlToCsvService
{

  protected function convert($xmlFile, $xPath)
  {
      $csvData = "";
    // Load the XML file
      $xml = simplexml_load_file($xmlFile);

      // Jump to the specified xpath
      $path = $xml->xpath($xPath);

      // Loop through the specified xpath
      foreach($path as $item) {

          // Loop through the elements in this xpath
          foreach($item as $key => $value) {

              $csvData .= '"' . trim($value) . '"' . ',';

          }

          // Trim off the extra comma
          $csvData = trim($csvData, ',');

          // Add an LF
          $csvData .= "\n";

      }

      // Return the CSV data
      return $csvData;

  }
}