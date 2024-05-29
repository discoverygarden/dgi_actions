<?php

namespace Drupal\dgi_actions_ark_identifier\Plugin\Action;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\dgi_actions\Plugin\Action\DeleteIdentifier;
use Drupal\dgi_actions\Plugin\Action\HttpActionDeleteTrait;
use Drupal\dgi_actions\Utility\IdentifierUtils;
use Drupal\dgi_actions_ezid\Utility\EzidTrait;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Deletes an ARK Identifier Record on CDL EZID.
 *
 * @Action(
 *   id = "dgi_actions_delete_ark_identifier",
 *   label = @Translation("Delete ARK EZID Identifier"),
 *   type = "entity"
 * )
 */
class DeleteArkIdentifier extends DeleteIdentifier {

  use HttpActionDeleteTrait;
  use EzidTrait;

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
  protected function getRequestType(): string {
    return 'DELETE';
  }

  /**
   * {@inheritdoc}
   */
  protected function getUri(): string {
    // XXX: Grab the existing ARK value as it contains the end-point URL to
    // delete.
    return $this->getIdentifierFromEntity();
  }

  /**
   * {@inheritdoc}
   */
  protected function getRequestParams(): array {
    return [
      RequestOptions::AUTH => $this->getAuthorizationParams(),
      RequestOptions::HTTP_ERRORS => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function handleDeleteResponse(ResponseInterface $response): void {
    $contents = $response->getBody()->getContents();
    $filteredResponse = $this->parseEzidResponse($contents);

    if (array_key_exists('success', $filteredResponse)) {
      $this->logger->info('ARK Identifier Deleted: @contents', ['@contents' => $contents]);
    }
    else {
      $this->logger->error('There was an issue deleting the ARK Identifier: @contents', ['@contents' => $contents]);
    }
  }

}
