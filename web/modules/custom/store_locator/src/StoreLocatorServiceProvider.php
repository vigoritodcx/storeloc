<?php

namespace Drupal\store_locator;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\Core\Site\Settings;
use Symfony\Component\DependencyInjection\Reference;

class StoreLocatorServiceProvider extends ServiceProviderBase
{

  public function alter(ContainerBuilder $container)
  {
    $definition = $container->getDefinition('http_client');

    $result = Settings::get('http_client_cacert', true);
    $definition->addArgument(['verify' => $result]);
  }
}
