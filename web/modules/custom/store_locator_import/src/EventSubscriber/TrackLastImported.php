<?php

namespace Drupal\store_locator_import\EventSubscriber;

use Drupal\migrate\Plugin\Migration;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;
use Drupal\migrate\Event\MigrateImportEvent;

/**
 * Class TrackLastImported.
 */
class TrackLastImported implements EventSubscriberInterface {


  /**
   * Constructs a new TrackLastImported object.
   */
  public function __construct() {

  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events['migrate.pre_import'] = ['init'];

    return $events;
  }

  /**
   * This method is called whenever the migrate.pre_import event is
   * dispatched.
   *
   * @param MigrateImportEvent $event
   */
  public function init(MigrateImportEvent $event) {
      ini_set('max_execution_time', 400);
      /** @var Migration $migration */
      $migration = $event->getMigration();
      /* set the migration to track to last migration */
      $migration->setTrackLastImported(true);
  }

}
