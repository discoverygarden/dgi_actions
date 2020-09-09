<?php

namespace Drupal\dgi_actions\Plugin\Action;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\dgi_actions\Utility\IdentifierUtils;
use Drupal\dgi_actions\Utility\EzidTextParser;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Psr\Log\LoggerInterface;

/**
 * Deletes an ARK Identifier Record on CDL EZID..
 *
 * @Action(
 *   id = "delete_ark_identifier_record",
 *   label = @Translation("Delete ARK EZID Identifier"),
 *   type = "entity"
 * )
 */
class DeleteArkIdentifier extends DeleteIdentifier {

  /**
   * CDL EZID Text Parser.
   *
   * @var \Drupal\dgi_actions\Utilities\EzidTextParser
   */
  protected $ezidParser;

  /**
   * Constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \GuzzleHttp\Client $client
   *   Http Client connection.
   * @param Psr\Log\LoggerInterface $logger
   *   Logger.
   * @param Drupal\dgi_actions\Utilities\IdentifierUtils $utils
   *   Identifier utils.
   * @param Drupal\dgi_actions\Utilities\EzidTextParser $ezid_parser
   *   CDL EZID Text parser.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    Client $client,
    LoggerInterface $logger,
    IdentifierUtils $utils,
    EzidTextParser $ezid_parser
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $client, $logger, $utils);
    $this->ezidParser = $ezid_parser;
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
      $container->get('dgi_actions.utils'),
      $container->get('dgi_actions.ezidtextparser')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getRequestType() {
    return 'DELETE';
  }

  /**
   * {@inheritdoc}
   */
  protected function getUri() {
    $identifier = $this->getIdentifierFromEntity();

    return $identifier;
  }

  /**
   * {@inheritdoc}
   */
  protected function getRequestParams() {
    $requestParams = [
      'auth' => [$this->getConfigs()['service_data']->get('data.username'), $this->getConfigs()['service_data']->get('data.password')],
    ];

    return $requestParams;
  }

  /**
   * {@inheritdoc}
   */
  protected function handleResponse(Response $response) {
    $contents = $response->getBody()->getContents();
    $filteredResponse = $this->ezidParser->parseEzidResponse($contents);

    if (array_key_exists('success', $filteredResponse)) {
      $this->logger->info('ARK Identifier Deleted: @contents', ['@contents' => $contents]);
    }
    else {
      $this->logger->error('There was an issue deleting the ARK Identifier: @contents', ['@contents' => $contents]);
    }
  }

}
