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
      ->addSelect('*', 'custom.*', 'event_type_id:name')
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

    // add straightforward fields
    foreach (['id', 'title', 'start_date', 'end_date', 'summary', 'description'] as $field) {
      $e->$field = $event[$field];
    }

    $e->event_type = $event['event_type_id:name'];
    $e->registration_url = \CRM_Utils_System::baseURL() . 'civicrm/event/register?reset=1&id=' . $event['id'];
    $e->registration_label = $event['registration_link_text'];
    $e->location = self::getEventLocation($event);
    $e->related_events  = self::getRelatedEvents($event['id']);

    $e->targetAudiences = $event['extra_evenement_info.doelgroep'];
    $e->languages = $event['extra_evenement_info.taal'];
    $e->languageLevels = $event['extra_evenement_info.taalniveau_42'];
    $e->ages = $event['extra_evenement_info.leeftijd_41'];
    $e->prices = self::getEventPrices($event['id']);

    return $e;
  }

  static private function getEventPrices($eventId) {
    $prices = [];

    $sql = "
      select
        h.entity_id,
        j.label pricelabel,
        round(j.amount, 2)  amount
      from
        civicrm_price_set_entity h
      LEFT JOIN civicrm_price_field i on
        h.price_set_id = i.price_set_id
      LEFT JOIN civicrm_price_field_value j on
        j.price_field_id = i.id
      where
        h.entity_id = $eventId
    ";

    $dao = \CRM_Core_DAO::executeQuery($sql);
    while ($dao->fetch()) {
      $p = new \stdClass();
      $p->pricelabel = $dao->pricelabel;
      $p->amount = $dao->amount;
      $prices[] = $p;
    }

    return $prices;
  }

  /**
   * @param $locBlockId
   *
   * @return \stdClass with 7 address lines (addressLine1, addressLine2...) and without blank lines between filled in lines
   */
  static private function getEventLocation($event) {
    $a = new \stdClass();

    $addressDao = self::getLocBlockAddress($event['loc_block_id']);
    if ($addressDao) {
      self::fillAddressLines($a, $addressDao);
      self::fillEventRooms($a, $event);
    }
    else {
      self::fillEmptyAddressLines($a);
    }

    return $a;
  }

  static private function fillEventRooms(&$a, $event)  {
    if (count($event['extra_evenement_info.muntpunt_zalen']) > 0) {
      $a->addressLine1 .= ' (' . implode(', ', $event['extra_evenement_info.muntpunt_zalen']) . ')';
    }
  }

  static private function fillAddressLines(&$a, $addressDao) {
    $i = 1;

    if (!empty($addressDao->addressee)) {
      $propertyName = "addressLine$i";
      $a->$propertyName = $addressDao->addressee;
      $i++;
    }

    if (!empty($addressDao->street_address)) {
      $propertyName = "addressLine$i";
      $a->$propertyName = $addressDao->street_address;
      $i++;
    }

    if (!empty($addressDao->supplemental_address_1)) {
      $propertyName = "addressLine$i";
      $a->$propertyName = $addressDao->supplemental_address_1;
      $i++;
    }

    if (!empty($addressDao->supplemental_address_2)) {
      $propertyName = "addressLine$i";
      $a->$propertyName = $addressDao->supplemental_address_2;
      $i++;
    }

    if (!empty($addressDao->supplemental_address_3)) {
      $propertyName = "addressLine$i";
      $a->$propertyName = $addressDao->supplemental_address_3;
      $i++;
    }

    if (!empty($addressDao->city)) {
      $propertyName = "addressLine$i";
      if ($addressDao->postal_code) {
        $a->$propertyName = $addressDao->postal_code . ' ' . $addressDao->city;
      }
      else {
        $a->$propertyName = $addressDao->city;
      }

      $i++;
    }

    if (!empty($addressDao->county_id)) {
      $propertyName = "addressLine$i";
      $a->$propertyName = \CRM_Core_PseudoConstant::country($addressDao->county_id);
      $i++;
    }

    while ($i <= 7) {
      $propertyName = "addressLine$i";
      $a->$propertyName = '';
      $i++;
    }
  }

  static function fillEmptyAddressLines(&$a) {
    for ($i = 1; $i <= 7; $i++) {
      $propertyName = "addressLine$i";
      $a->$propertyName = '';
    }
  }

  static private function getLocBlockAddress($locBlockId) {
    if (empty($locBlockId)) {
      return FALSE;
    }

    $sql = "
      select
        a.*
      from
        civicrm_loc_block lb
      inner join
        civicrm_address a on lb.address_id = a.id
      where
        lb.id = $locBlockId
    ";
    $dao = \CRM_Core_DAO::executeQuery($sql);
    if ($dao->fetch()) {
      return $dao;
    }
    else {
      return FALSE;
    }
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
