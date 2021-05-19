<?php

namespace Drupal\dgi_actions_handle\Plugin\Action;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\dgi_actions\Plugin\Action\HttpActionMintTrait;
use Drupal\dgi_actions\Plugin\Action\MintIdentifier;
use Drupal\dgi_actions\Utility\IdentifierUtils;
use Drupal\dgi_actions_handle\Utility\HandleTrait;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Mints a Handle.
 *
 * @Action(
 *   id = "dgi_actions_mint_handle",
 *   label = @Translation("Mint a Handle"),
 *   type = "entity"
 * )
 */
class MintHandle extends MintIdentifier {

  use HttpActionMintTrait;
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
  protected function getRequestType(): string {
    return 'PUT';
  }

  /**
   * {@inheritdoc}
   */
  protected function getRequestParams(): array {
    return [
      'auth' => $this->getAuthorizationParams(),
      'headers' => [
        'Content-Type' => 'application/json;charset=UTF-8',
      ],
      'json' => [
        [
          'index' => 1,
          'type' => 'URL',
          'data' => $this->entity->toUrl()->setAbsolute()->toString(TRUE)->getGeneratedUrl(),
        ],
      ],
      'query' => [
        'overwrite' => 'false',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function mint(): string {
    return $this->getIdentifierFromResponse($this->handleRequest());
  }

  /**
   * {@inheritdoc}
   */
  protected function getIdentifierFromResponse(ResponseInterface $response): string {
    $body = json_decode($response->getBody(), TRUE);
    $this->logger->info('Handle minted for @type/@id: @handle.', [
      '@type' => $this->getEntity()->getEntityTypeId(),
      '@id' => $this->getEntity()->id(),
      '@handle' => $body['handle'],
    ]);
    return "https://hdl.handle.net/{$body['handle']}";
  }

}
