<?php

namespace Drupal\dgi_actions\Plugin\Action;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\dgi_actions\Entity\IdentifierInterface;
use Drupal\dgi_actions\Utility\IdentifierUtils;
use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Base class for Identifier Actions.
 */
abstract class IdentifierAction extends ConfigurableActionBase implements ContainerFactoryPluginInterface {

  /**
   * Identifier config.
   *
   * @var \Drupal\dgi_actions\Entity\IdentifierInterface
   */
  protected $identifier;

  /**
   * Current actioned Entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Http Client connection.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;

  /**
   * Identifier Utils.
   *
   * @var \Drupal\dgi_actions\Utility\IdentifierUtils
   */
  protected $utils;

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
   * @param \Drupal\dgi_actions\Utility\IdentifierUtils $utils
   *   Identifier utils.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration,
    $plugin_id,
    $plugin_definition,
    ClientInterface $client,
    LoggerInterface $logger,
    IdentifierUtils $utils,
    EntityTypeManagerInterface $entity_type_manager
  ) {

    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->client = $client;
    $this->logger = $logger;
    $this->utils = $utils;
    $this->identifier = $entity_type_manager->getStorage('dgiactions_identifier')->load($this->configuration['identifier_entity']);

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
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $object->access('read', $account, $return_as_object);
  }

  /**
   * Gets the identifier entity.
   *
   * @return \Drupal\dgi_actions\Entity\IdentifierInterface
   *   The identifier entity being used to execute the action.
   */
  public function getIdentifier(): IdentifierInterface {
    return $this->identifier;
  }

  /**
   * Gets the External URL of the Entity.
   *
   * @return string
   *   Entity's external URL as a string.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Drupal\Core\Entity\Exception\UndefinedLinkTemplateException
   */
  public function getExternalUrl(): string {
    return $this->entity->toUrl('canonical', ['absolute' => TRUE])->toString(TRUE)->getGeneratedUrl();
  }

  /**
   * Sets the entity value.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Sets the object's $entity value.
   */
  public function setEntity(EntityInterface $entity) {
    $this->entity = $entity;
  }

  /**
   * Gets the entity value.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Returns the EntityInterface value of entity.
   */
  public function getEntity(): EntityInterface {
    return $this->entity;
  }

  /**
   * Gets the request type.
   *
   * @return string
   *   Request type. (IE. POST, GET, DELETE, etc).
   */
  abstract protected function getRequestType(): string;

  /**
   * Gets the URI end-point for the request.
   *
   * @return string
   *   URI end-point for the request.
   */
  abstract protected function getUri(): string;

  /**
   * Builds the Guzzle HTTP Request.
   *
   * @throws \GuzzleHttp\Exception\RequestException
   *   Thrown by Guzzle when creating an invalid Request.
   *
   * @return \GuzzleHttp\Psr7\Request
   *   The Guzzle HTTP Request Object.
   */
  protected function buildRequest(): Request {
    $requestType = $this->getRequestType();
    $uri = $this->getUri();
    return new Request($requestType, $uri);
  }

  /**
   * Returns the request param array.
   *
   * @return array
   *   Required params for the applicable service.
   */
  abstract protected function getRequestParams(): array;

  /**
   * Sends the Request and Request Body.
   *
   * @param \GuzzleHttp\Psr7\Request $request
   *   The Guzzle HTTP Request Object.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The Guzzle HTTP Response Object.
   *
   * @throws \GuzzleHttp\Exception\BadResponseException
   *   Thrown when receiving 4XX or 5XX error.
   */
  protected function sendRequest(Request $request): ResponseInterface {
    $requestParams = $this->getRequestParams();
    return $this->client->send($request, $requestParams);
  }

  /**
   * {@inheritdoc}
   */
  abstract public function execute($entity = NULL): void;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'identifier_entity' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['identifier_entity'] = [
      '#type' => 'select',
      '#title' => $this->t('Identifier Entity'),
      '#empty_option' => $this->t('- None -'),
      '#default_value' => ($this->configuration['identifier_entity']) ?: $this->t('- None -'),
      '#options' => $this->utils->getIdentifiers(),
      '#description' => $this->t('The persistent identifier configuration to be used.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['identifier_entity'] = $form_state->getValue('identifier_entity');
  }

}
