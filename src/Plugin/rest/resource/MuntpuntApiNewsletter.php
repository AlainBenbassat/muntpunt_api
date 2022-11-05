<?php

namespace Drupal\muntpunt_api\Plugin\rest\resource;

use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\muntpunt_api\MuntpuntNewsletter;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "muntpuntapinewsletter",
 *   label = @Translation("Muntpunt api newsletter"),
 *   uri_paths = {
 *     "canonical" = "/muntpunt/api/newsletter",
 *     "create" = "/muntpunt/api/newsletter"
 *   }
 * )
 */
class MuntpuntApiNewsletter extends ResourceBase {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->logger = $container->get('logger.factory')->get('muntpunt_api');
    $instance->currentUser = $container->get('current_user');
    return $instance;
  }

  /**
   * Responds to POST requests.
   *
   * @param string $payload
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The HTTP response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function post($payload = NULL) {
    if (!$this->isMuntpuntApiUser()) {
      return new ModifiedResourceResponse('Unauthorized user', 403);
    }

    $email = $this->extractEmail($payload);
    if ($email) {
      MuntpuntNewsletter::subscribe($email);
      return new ModifiedResourceResponse("$email was successfully added", 201);
    }
    else {
      return new ModifiedResourceResponse('No email found in request', 400);
    }
  }

  public function permissions() {
    return [];
  }

  private function isMuntpuntApiUser() {
    if ($this->currentUser->getAccountName() == 'muntpuntapiuser') {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  private function extractEmail($payload) {
    // the payload is always empty due to a bug in Drupal at this moment
    // read directly from php://input if empty
    if (empty($payload)) {
      $payload = file_get_contents('php://input');
    }

    $decodedPayload = json_decode($payload, TRUE);
    if (!empty($decodedPayload) && array_key_exists('email', $decodedPayload)) {
      return $decodedPayload['email'];
    }
    else {
      return FALSE;
    }
  }
}
