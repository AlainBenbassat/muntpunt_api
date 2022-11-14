<?php

namespace Drupal\muntpunt_api;

class MuntpuntApiHelper {

  public static function isMuntpuntApiUser($currentUser) {
    if ($currentUser->getAccountName() == 'muntpuntapiuser') {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }
}
