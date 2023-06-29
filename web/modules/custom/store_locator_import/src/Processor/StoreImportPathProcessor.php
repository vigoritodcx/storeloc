<?php
/**
 * Created by PhpStorm.
 * User: fcavallo
 * Date: 27/04/18
 * Time: 12.51
 */

namespace Drupal\store_locator_import\Processor;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Symfony\Component\HttpFoundation\Request;

class StoreImportPathProcessor implements InboundPathProcessorInterface {

  public function processInbound($path, Request $request) {

    if ($request->get('is_store_import')) {

        die('asdaaa');

        return '/view/stores';
    }

    return $path;
  }
}