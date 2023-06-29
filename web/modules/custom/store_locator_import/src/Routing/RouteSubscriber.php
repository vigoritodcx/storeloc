<?php

namespace Drupal\store_locator_import\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber.
 *
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
      if ( $route = $collection->get('migrate_tools.execute') ) {
          $route->setDefault('_form', '\Drupal\store_locator_import\Form\LocatorMigrationExecuteForm');
      }
  }
}
