<?php
/**
 * Created by PhpStorm.
 * User: fcavallo
 * Date: 27/04/18
 * Time: 12.51
 */

namespace Drupal\store_locator\Processor;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Symfony\Component\HttpFoundation\Request;

class StorePathProcessor implements InboundPathProcessorInterface {

  public function processInbound($path, Request $request) {
    if ($request->getMethod() == 'GET' && drupal_static('store_locator_map_config')) {
      return '/view/stores';
    }

    return $path;
  }
}