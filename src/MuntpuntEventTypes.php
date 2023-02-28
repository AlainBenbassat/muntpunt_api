<?php

namespace Drupal\muntpunt_api;

class MuntpuntEventTypes {

  static public function get() {
    \Drupal::service('civicrm')->initialize();

    $eventTypes = self::getEventTypes();
    return $eventTypes;
  }

  static private function getEventTypes() {
    $eventTypeArray = [];

    $eventTypes = \Civi\Api4\Event::get()
      ->addSelect('event_type_id', 'event_type_id:label')
      ->addGroupBy('event_type_id')
      ->addWhere('start_date', '>=', '2023-01-01')
      ->execute();

    foreach ($eventTypes as $eventType) {
      $eventTypeArray[] = self::convertEventTypeToArray($eventType);
    }

    return $eventTypeArray;
  }

  static private function convertEventTypeToArray($eventType) {
    $e = [];

    // add straightforward fields
    foreach (['event_type_id', 'event_type_id:label'] as $field) {
      $e[$field] = $eventType[$field];
    }

    return $e;
  }

}
