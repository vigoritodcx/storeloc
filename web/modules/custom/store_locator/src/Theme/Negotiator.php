<?php
/**
 * Created by PhpStorm.
 * User: cgrimoldi
 * Date: 07/08/18
 * Time: 22.37
 */

namespace Drupal\store_locator\Theme;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;

class Negotiator implements ThemeNegotiatorInterface {

    public function applies(RouteMatchInterface $route_match) {
        return  in_array($route_match->getRouteName(), ['user.login', 'entity.node.canonical']);
    }

    /**
     * {@inheritdoc}
     */
    public function determineActiveTheme(RouteMatchInterface $route_match) {
        return 'seven';
    }
}