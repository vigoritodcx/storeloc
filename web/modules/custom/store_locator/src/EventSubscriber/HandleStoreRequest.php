<?php
/**
 * Created by PhpStorm.
 * User: fcavallo
 * Date: 07/05/18
 * Time: 16.13
 */

namespace Drupal\store_locator\EventSubscriber;

use Drupal\store_locator\Service\MapConfigurationService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class HandleStoreRequest implements EventSubscriberInterface {

  private $mapConfigurationService;

  public function __construct(MapConfigurationService $mapConfigurationService) {
    $this->mapConfigurationService = $mapConfigurationService;
  }

  public function onRequest(RequestEvent $event) {
    $request = $event->getRequest();
    $path2test = null;

    if ($request->getMethod() == 'GET' && strpos($request->getRequestUri(), $request->getBaseUrl() . '/locator/') === 0) {
        $path2test = $request->getPathInfo();

    }
    elseif ($request->getMethod() == 'POST' && !empty($request->query->get('locator_config_id'))) {
      if ($request->get('view_name') == 'vista_store') {
            $path2test = $request->query->get('locator_config_id');
        }
    }

    if ($path2test) {
      $segment = explode('/', $path2test);
      $nid = array_pop($segment);
      $map_config = $this->getMapConfiguration($nid);
      if (empty($map_config)) {
        $response = new Response('', Response::HTTP_NOT_FOUND);
        $event->setResponse($response);
      }

      drupal_static('store_locator_map_config', $map_config);
    }
  }


  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {

    $events[KernelEvents::REQUEST][] = ['onRequest', 50];

    return $events;
  }

  /**
   * @param $nid
   * @return array|null
   */
  private function getMapConfiguration($nid) {
    $nid = intval($nid);
    $map_config = $this->mapConfigurationService->get(intval($nid));
    return $map_config ?: null;

  }
}
