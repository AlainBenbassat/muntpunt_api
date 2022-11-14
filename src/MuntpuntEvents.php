<?php

namespace Drupal\muntpunt_api;

class MuntpuntEvents {
  private const eventStatusCommunicatieOK = 5;

  static public function get() {
    \Drupal::service('civicrm')->initialize();

    $upcomingEvents = self::getUpcomingEvents();
    return json_encode($upcomingEvents);
  }

  static private function getUpcomingEvents() {
    $eventArray = [];

    $events = \Civi\Api4\Event::get(FALSE)
      ->addWhere('start_date', '>=', date('Y-m-d'))
      ->addWhere('extra_evenement_info.activiteit_status', '=', self::eventStatusCommunicatieOK)
      ->addWhere('is_active', '=', TRUE)
      ->addOrderBy('start_date', 'ASC')
      ->setLimit(500)
      ->execute();
    foreach ($events as $event) {
      $eventArray[] = self::convertEventToObject($event);
    }

    return $eventArray;
  }

  static private function convertEventToObject($event) {
    $e = new \stdClass();

    foreach (['id', 'title', 'summary', 'start_date', 'end_date'] as $field) {
      $e->$field = $event[$field];
    }

    return $e;
  }
}
