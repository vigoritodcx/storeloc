<?php

namespace Drupal\store_locator\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class RemoveXFrameOptionsSubscriber implements EventSubscriberInterface {

    public function RemoveXFrameOptions($event) {
        $response = $event->getResponse();
        $response->headers->remove('X-Frame-Options');
    }

    public static function getSubscribedEvents() {
        $events[KernelEvents::RESPONSE][] = array('RemoveXFrameOptions', -10);
        return $events;
    }
}
