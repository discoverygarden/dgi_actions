<?php

namespace Drupal\dgi_actions_handle\Plugin\Action;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\dgi_actions\Plugin\Action\DeleteIdentifier;
use Drupal\dgi_actions\Plugin\Action\HttpActionDeleteTrait;
use Drupal\dgi_actions\Utility\IdentifierUtils;
use Drupal\dgi_actions_handle\Utility\HandleTrait;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Deletes a Handle.
 *
 * @Action(
 *   id = "dgi_actions_delete_handle",
 *   label = @Translation("Delete a Handle"),
 *   type = "entity"
 * )
 */
class DeleteHandle extends DeleteIdentifier {

  use HttpActionDeleteTrait;
  use HandleTrait;

  /**
   * Constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger.
   * @param \Drupal\dgi_actions\Utility\IdentifierUtils $utils
   *   Identifier utils.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \GuzzleHttp\ClientInterface $client
   *   The HTTP client to be used for the request.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger, IdentifierUtils $utils, EntityTypeManagerInterface $entity_type_manager, ClientInterface $client) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger, $utils, $entity_type_manager);
    $this->client = $client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.channel.dgi_actions'),
      $container->get('dgi_actions.utils'),
      $container->get('entity_type.manager'),
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function handleDeleteResponse(ResponseInterface $response): void {
    $this->logger->info('Handle @prefix/@suffix was deleted from @type/@id.', [
      '@prefix' => $this->getPrefix(),
      '@suffix' => $this->getSuffix(),
      '@type' => $this->getEntity()->getEntityTypeId(),
      '@id' => $this->getEntity()->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function delete(): void {
    $this->handleDeleteResponse($this->handleRequest());
  }

  /**
   * {@inheritdoc}
   */
  protected function getRequestType(): string {
    return 'DELETE';
  }

  /**
   * {@inheritdoc}
   */
  protected function getRequestParams(): array {
    return [
      'auth' => $this->getAuthorizationParams(),
    ];
  }

}
