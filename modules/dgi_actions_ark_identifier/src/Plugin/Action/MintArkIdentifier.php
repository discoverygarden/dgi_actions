<?php

namespace Drupal\dgi_actions_ark_identifier\Plugin\Action;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\dgi_actions\Plugin\Action\HttpActionMintTrait;
use Drupal\dgi_actions\Plugin\Action\MintIdentifier;
use Drupal\dgi_actions\Utility\IdentifierUtils;
use Drupal\dgi_actions_ezid\Utility\EzidTrait;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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

  use HttpActionMintTrait;
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
        '_status' => $this->configuration['status'],
      ], $data
    );
    return $this->buildEzidRequestBody($data);
  }

  /**
   * {@inheritdoc}
   */
  protected function getIdentifierFromResponse(ResponseInterface $response): string {
    $contents = $response->getBody()->getContents();
    $response = $this->parseEzidResponse($contents);
    if (array_key_exists('success', $response)) {
      $this->logger->info('ARK Identifier Minted for @type/@id: @contents', [
        '@type' => $this->getEntity()->getEntityTypeId(),
        '@id' => $this->getEntity()->id(),
        '@contents' => $contents,
      ]);
      $ark = $response['success'];
      $service_data = $this->getIdentifier()->getServiceData()->getData();
      $resolver = (array_key_exists('resolver', $service_data) && !empty($service_data['resolver'])) ? $service_data['resolver'] : "{$service_data['host']}/id";
      return "{$resolver}/{$ark}";
    }
    throw new \Exception('There was an issue minting the ARK Identifier for @type/@id: @contents', [
      '@type' => $this->getEntity()->getEntityTypeId(),
      '@id' => $this->getEntity()->id(),
      '@contents' => $contents,
    ]);
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
    return "{$this->getIdentifier()->getServiceData()->getData()['host']}/shoulder/{$this->getIdentifier()->getServiceData()->getData()['namespace']}";
  }

  /**
   * {@inheritdoc}
   */
  protected function getRequestParams(): array {
    $fieldData = $this->getFieldData();
    $body = $this->buildRequestBody($fieldData);

    return [
      'auth' => $this->getAuthorizationParams(),
      'headers' => [
        'Content-Type' => 'text/plain; charset=UTF-8',
        'Content-Length' => strlen($body),
      ],
      'body' => $body,
    ];
  }

  /**
   * Adds the `save_entity` configuration option.
   *
   * This option, when true, will save the entity as part of the action.
   *
   * The default FALSE option is for when the action is triggered
   * as a context reaction.
   */
  public function defaultConfiguration(): array {
    return parent::defaultConfiguration() + [
      'status' => 'reserved',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $status_options = [
      'public' => 'public',
      'reserved' => 'reserved',
      'unavailable' => 'unavailable',
    ];
    $form['status'] = [
      '#type' => 'select',
      '#title' => $this->t('Status'),
      '#default_value' => $this->configuration['status'],
      '#options' => $status_options,
      '#description' => $this->t("Set the identifier's status. This impacts the ARK's resolvability. See the EZID API documentation (https://ezid.cdlib.org/doc/apidoc.html#identifier-status)."),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['status'] = $form_state->getValue('status');
  }

}
