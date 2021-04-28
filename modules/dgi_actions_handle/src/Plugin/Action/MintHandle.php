<?php

namespace Drupal\dgi_actions_handle\Plugin\Action;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\dgi_actions\Plugin\Action\MintIdentifier;
use Drupal\dgi_actions\Utility\IdentifierUtils;
use Drupal\dgi_actions_handle\Utility\HandleTrait;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
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
   * @param \GuzzleHttp\ClientInterface $client
   *   Http Client connection.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config Factory.
   * @param \Drupal\dgi_actions\Utility\IdentifierUtils $utils
   *   Identifier utils.
   * @param \Drupal\Core\State\StateInterface $state
   *   State API.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ClientInterface $client,
    LoggerInterface $logger,
    ConfigFactoryInterface $config_factory,
    IdentifierUtils $utils,
    StateInterface $state
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $client, $logger, $config_factory, $utils);
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client'),
      $container->get('logger.channel.dgi_actions'),
      $container->get('config.factory'),
      $container->get('dgi_actions.utils'),
      $container->get('state')
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
  protected function mint() {
    return $this->handleRequest();
  }

  /**
   * {@inheritdoc}
   */
  protected function getIdentifierFromResponse(Response $response): string {
    $body = json_decode($response->getBody(), TRUE);
    return "https://hdl.handle.net/{$body['handle']}";
  }

}
