<?php

namespace Drupal\muntpunt_api\Plugin\rest\resource;

use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\muntpunt_api\MuntpuntApiHelper;
use Drupal\muntpunt_api\MuntpuntEvents;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "muntpuntapievents",
 *   label = @Translation("Muntpunt api events"),
 *   uri_paths = {
 *     "canonical" = "/muntpunt/api/events"
 *   }
 * )
 */
class MuntpuntApiEvents extends ResourceBase {
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
   * Responds to GET requests.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The HTTP response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function get() {
    if (!MuntpuntApiHelper::isMuntpuntApiUser($this->currentUser)) {
      return new ModifiedResourceResponse('Unauthorized user', 403);
    }

    return new ModifiedResourceResponse(MuntpuntEvents::get(), 200);
  }

  public function permissions() {
    return [];
  }
}
