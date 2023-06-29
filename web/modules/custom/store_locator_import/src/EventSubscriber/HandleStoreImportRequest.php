<?php
/**
 * Created by PhpStorm.
 * User: fcavallo
 * Date: 07/05/18
 * Time: 16.13
 */

namespace Drupal\store_locator_import\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class HandleStoreImportRequest implements EventSubscriberInterface {

  public function onRequest(RequestEvent $event) {
    /** @var Request $request */
    $request = $event->getRequest();
    if ($request->getMethod() == 'GET' && strpos($request->getRequestUri(), $request->getBaseUrl() . '/importXML/') === 0) {
        /*
      $segment = explode('/', $request->getPathInfo());
      $nid = array_pop($segment);
      $map_config = $this->getMapConfiguration($nid);
      if (empty($map_config)) {
        throw new NotFoundHttpException();
      }
      $request->query->add([
        'is_store_locator' => $nid,
      ]);

      $request->initialize(
        $request->query->all(),
        $request->request->all(),
        $request->attributes->all(),
        $request->cookies->all(),
        $request->files->all(),
        $request->server->all(),
        json_encode($map_config)
      );*/


        $request->query->add([
          'is_store_import' => true,
        ]);

    }
  }


  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {

    $events[KernelEvents::REQUEST][] = ['onRequest', 50];

    return $events;
  }
}
