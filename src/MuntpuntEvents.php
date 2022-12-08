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
      ->setLimit(1)
      ->execute();
    foreach ($events as $event) {
      $eventArray[] = self::convertEventToObject($event);
    }

    return $eventArray;
  }

  static private function convertEventToObject($event) {
    $e = new \stdClass();

    foreach (['id', 'title', 'start_date', 'end_date', 'summary', 'description'] as $field) {
      $e->$field = $event[$field];
    }

    $e->registration_url = \CRM_Utils_System::baseURL() . 'civicrm/event/register?reset=1&id=' . $event['id'];
    $e->registration_label = $event['registration_link_text'];
    $e->location = self::getEventLocation($event['id']);

    $e->related_events  = self::getRelatedEvents($event['id']);
var_dump($event);exit;
    return $e;
  }

  static private function getEventLocation($eventId) {

  }

  static private function getRelatedEvents($eventId) {
    $relatedEvents = [];

    $parentEventId = \CRM_Core_DAO::singleValueQuery("select parent_id from civicrm_recurring_entity where entity_table = 'civicrm_event' and entity_id = $eventId");
    if ($parentEventId) {
      $sql = "
        select
          re.entity_id
        from
          civicrm_recurring_entity re
        inner join
          civicrm_event e on e.id = re.entity_id and re.entity_table = 'civicrm_event'
        where
          re.entity_table = 'civicrm_event'
        and
          re.parent_id = $parentEventId
        and
          re.entity_id <> $eventId
        and
          e.start_date >= now()
        and
          e.is_active = 1
      ";

      $dao = \CRM_Core_DAO::executeQuery($sql);
      while ($dao->fetch()) {
        $relatedEvents[] = $dao->entity_id;
      }
    }

    return $relatedEvents;
  }
}
