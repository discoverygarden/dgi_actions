<?php

namespace Drupal\dgi_actions\Plugin\Action;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\dgi_actions\Utility\IdentifierUtils;
use Drupal\dgi_actions\Utility\EzidTextParser;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

/**
 * Mints an ARK Identifier Record on CDL EZID.
 *
 * @Action(
 *   id = "mint_ark_identifier_record",
 *   label = @Translation("Mint ARK EZID Identifier"),
 *   type = "entity"
 * )
 */
class MintArkIdentifier extends MintIdentifier {

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
   * Builds the Request Body.
   *
   * Constructs the Metadata into a colon separated value
   * string for the CDL EZID service.
   *
   * @param mixed $data
   *   The Entity data that's to be built for the service.
   *
   * @return string
   *   Returns the stringified version of the key-value
   *   pairs else returns an empty string if $data is empty or null.
   */
  protected function buildRequestBody($data = NULL) {
    if (!$data) {
      $this->logger->warning('buildRequestBody - Data is missing or malformed.');
      $data = [];
    }

    // Adding External URL to the Data Array under the EZID _target key.
    // Also setting _status as reserved. Else identifier cannot be deleted.
    $data = array_merge(['_target' => $this->getExternalURL(), '_status' => 'reserved'], $data);
    $output = $this->ezidParser->buildEzidRequestBody($data);

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  protected function getIdentifierFromResponse($response) {
    $contents = $response->getBody()->getContents();
    $responseArray = $this->ezidParser->parseEzidResponse($contents);
    if (array_key_exists('success', $responseArray)) {
      $this->logger->info('ARK Identifier Minted: @contents', ['@contents' => $contents]);
      return $this->getConfigs()['service_data']->get('data.host') . '/id/' . $responseArray['success'];
    }
    else {
      $this->logger->error('There was an issue minting the ARK Identifier: @contents', ['@contents' => $contents]);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getRequestType() {
    return 'POST';
  }

  /**
   * {@inheritdoc}
   */
  protected function getUri() {
    $uri = $this->getConfigs()['service_data']->get('data.host') . '/shoulder/' . $this->getConfigs()['service_data']->get('data.shoulder');

    return $uri;
  }

  /**
   * {@inheritdoc}
   */
  protected function getRequestParams() {
    $fieldData = $this->getFieldData();
    $requestBody = $this->buildRequestBody($fieldData);
    $requestParams = [
      'auth' => [$this->getConfigs()['service_data']->get('data.username'), $this->getConfigs()['service_data']->get('data.password')],
      'headers' => [
        'Content-Type' => 'text/plain; charset=UTF-8',
        'Content-Length' => strlen($requestBody),
      ],
      'body' => $requestBody,
    ];

    return $requestParams;
  }

  /**
   * {@inheritdoc}
   */
  protected function handleResponse($response) {
    $identifier = $this->getIdentifierFromResponse($response);
    $this->setIdentifierField($identifier);
  }

}
