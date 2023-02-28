Muntpunt API
============

Allows remote communication.

Requires Drupal modules
==
 * HTTP Basic Authentication
 * Serialization
 * RESTful Web Services

Installation and updates
==
drush pm-uninstall muntpunt_api -y
drush pm-uninstall rest -y
drush cr
drush en muntpunt_api -y
drush en rest -y
drush cr



