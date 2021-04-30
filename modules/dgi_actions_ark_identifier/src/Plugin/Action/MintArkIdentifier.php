<?php

namespace Drupal\dgi_actions_ark_identifier\Plugin\Action;

use Drupal\dgi_actions_ark_identifier\Utility\EzidTextParser;
use Drupal\dgi_actions\Plugin\Action\MintIdentifier;
use Drupal\dgi_actions\Utility\IdentifierUtils;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Drupal\Core\Config\ConfigFactory;
use Psr\Log\LoggerInterface;
use Drupal\Core\State\StateInterface;

/**
 * Mints an ARK Identifier Record on CDL EZID.
 *
 * @Action(
 *   id = "dgi_actions_mint_ark_identifier",
 *   label = @Translation("Mint ARK EZID Identifier"),
 *   type = "entity"
 * )
 */
class MintArkIdentifier extends MintIdentifier {

  // @codingStandardsIgnoreStart

  /**
   * CDL EZID Text Parser.
   *
   * @var \Drupal\dgi_actions_ark_identifier\Utility\EzidTextParser
   */
  protected $ezidParser;

  /**
   * State API.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

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
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   Config Factory.
   * @param \Drupal\dgi_actions\Utility\IdentifierUtils $utils
   *   Identifier utils.
   * @param \Drupal\dgi_actions_ark_identifier\Utility\EzidTextParser $ezid_parser
   *   CDL EZID Text parser.
   * @param \Drupal\Core\State\StateInterface $state
   *   State API.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    Client $client,
    LoggerInterface $logger,
    ConfigFactory $config_factory,
    IdentifierUtils $utils,
    EzidTextParser $ezid_parser,
    StateInterface $state
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $client, $logger, $config_factory, $utils);
    $this->ezidParser = $ezid_parser;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): MintArkIdentifier {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client'),
      $container->get('logger.channel.dgi_actions'),
      $container->get('config.factory'),
      $container->get('dgi_actions.utils'),
      $container->get('dgi_actions.ezidtextparser'),
      $container->get('state')
    );
  }
  // @codingStandardsIgnoreEnd

  /**
   * Builds the Request Body.
   *
   * Constructs the Metadata into a colon separated value
   * string for the CDL EZID service.
   *
   * @param array $data
   *   The Entity data that's to be built for the service.
   *
   * @return string
   *   Returns the stringified version of the key-value
   *   pairs else returns an empty string if $data is empty or null.
   */
  protected function buildRequestBody(array $data): string {
    // Setting custom values for the identifiers internal metadata.
    // Adding External URL to the Data Array under the EZID _target key.
    // Also setting _status as reserved. Else identifier cannot be deleted.
    // For more info: https://ezid.cdlib.org/doc/apidoc.html#internal-metadata.
    $data = array_merge(
      [
        '_target' => $this->getExternalUrl(),
        '_status' => 'reserved',
      ], $data
    );

    return $this->ezidParser->buildEzidRequestBody($data);
  }

  /**
   * {@inheritdoc}
   */
  protected function getIdentifierFromResponse(Response $response): string {
    $contents = $response->getBody()->getContents();
    $responseArray = $this->ezidParser->parseEzidResponse($contents);
    if (array_key_exists('success', $responseArray)) {
      $this->logger->info('ARK Identifier Minted: @contents', ['@contents' => $contents]);
      return $this->serviceDataConfig->get('data.data.host.data') . '/id/' . $responseArray['success'];
    }

    $this->logger->error('There was an issue minting the ARK Identifier: @contents', ['@contents' => $contents]);
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function getRequestType(): string {
    return 'POST';
  }

  /**
   * {@inheritdoc}
   */
  protected function getUri(): string {
    return $this->serviceDataConfig->get('data.data.host.data') . '/shoulder/' . $this->serviceDataConfig->get('data.data.namespace.data');
  }

  /**
   * {@inheritdoc}
   */
  protected function getRequestParams(): array {
    $fieldData = $this->getFieldData();
    $requestBody = $this->buildRequestBody($fieldData);
    $creds = $this->state->get($this->serviceDataConfig->get('data.state_key'));

    return [
      'auth' => [
        $creds['username'],
        $creds['password'],
      ],
      'headers' => [
        'Content-Type' => 'text/plain; charset=UTF-8',
        'Content-Length' => strlen($requestBody),
      ],
      'body' => $requestBody,
    ];
  }

}
