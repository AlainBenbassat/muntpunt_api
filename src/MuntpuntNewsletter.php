<?php

namespace Drupal\muntpunt_api;
use CRM_Mailing_Event_BAO_Subscribe;

class MuntpuntNewsletter {
  private const newsletterGroupId = 2101;

  static public function subscribe($email) {
    \Drupal::service('civicrm')->initialize();

    $contactId = self::getOrCreateContactByEmail($email);
    self::addContactToNewsletterGroup($contactId);
  }

  static private function getOrCreateContactByEmail($email) {
    $contactId = self::getContactByEmail($email);
    if (!$contactId) {
      $contactId = self::createContact($email);
      self::createEmail($contactId, $email);
    }

    return $contactId;
  }

  static private function getContactByEmail($email) {
    $emails = \Civi\Api4\Email::get(FALSE)
      ->addWhere('contact_id.is_deleted', '=', FALSE)
      ->addWhere('email', '=', $email)
      ->execute();
    if (count($emails) > 0) {
      return $emails[0]['contact_id'];
    }
    else {
      return FALSE;
    }
  }

  static private function createContact($email) {
    $results = \Civi\Api4\Contact::create(FALSE)
      ->addValue('contact_type', 'Individual')
      ->addValue('first_name', $email)
      ->execute();

    return $results[0]['id'];
  }

  static private function createEmail($contactId, $email) {
    \Civi\Api4\Email::create(FALSE)
      ->addValue('location_type_id', 1)
      ->addValue('email', $email)
      ->addValue('contact_id', $contactId)
      ->execute();
  }

  static private function addContactToNewsletterGroup($contactId) {
    $groupContactStatus = self::getNewsletterGroupContactStatus($contactId);
    if ($groupContactStatus == 'Added') {
      // do nothing, already subscribed
    }
    elseif ($groupContactStatus == 'Removed') {
      self::updateNewsletterGroupContactStatus($contactId);
    }
    else {
      self::createNewsletterGroupContact($contactId);
    }
  }

  static private function getNewsletterGroupContactStatus ($contactId) {
    $groupContacts = \Civi\Api4\GroupContact::get(FALSE)
      ->addWhere('group_id', '=', self::newsletterGroupId)
      ->addWhere('contact_id', '=', $contactId)
      ->setLimit(25)
      ->execute();
    if (count($groupContacts) > 0) {
      return $groupContacts[0]['status'];
    }
    else {
      return '';
    }
  }

  static private function updateNewsletterGroupContactStatus($contactId) {
    $results = \Civi\Api4\GroupContact::update(FALSE)
      ->addValue('status', 'Added')
      ->addWhere('group_id', '=', self::newsletterGroupId)
      ->addWhere('contact_id', '=', $contactId)
      ->execute();
  }

  static private function createNewsletterGroupContact($contactId) {
    $results = \Civi\Api4\GroupContact::create(FALSE)
      ->addValue('group_id', self::newsletterGroupId)
      ->addValue('contact_id', $contactId)
      ->addValue('status', 'Added')
      ->execute();
  }
}
