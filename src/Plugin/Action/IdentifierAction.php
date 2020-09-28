<?php

namespace Drupal\dgi_actions\Plugin\Action;

use Drupal\dgi_actions\Utility\IdentifierUtils;
use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Log\LoggerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Base class for Identifier Actions.
 */
abstract class IdentifierAction extends ConfigurableActionBase implements ContainerFactoryPluginInterface {

  /**
   * Configured Identifier config values.
   *
   * @var array
   */
  protected $configs;

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
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * Identifier Utils.
   *
   * @var \Drupal\dgi_actions\Utilities\IdentifierUtils
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
   * @param \GuzzleHttp\Client $client
   *   Http Client connection.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger.
   * @param \Drupal\dgi_actions\Utilities\IdentifierUtils $utils
   *   Identifier utils.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    Client $client,
    LoggerInterface $logger,
    IdentifierUtils $utils
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->client = $client;
    $this->logger = $logger;
    $this->utils = $utils;
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
      $container->get('dgi_actions.utils')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = $object->access('read', $account, $return_as_object);
    return $result;
  }

  /**
   * Gets the External URL of the Entity.
   *
   * @throws UndefinedLinkTemplateException
   *
   * @return string
   *   Entitiy's external URL as a string.
   */
  public function getExternalUrl() {
    return $this->entity->toUrl('canonical', ['absolute' => TRUE])->toString(TRUE)->getGeneratedUrl();
  }

  /**
   * Sets the config value.
   *
   * @param array $configs
   *   Sets the objects $configs value.
   */
  public function setConfigs(array $configs) {
    $this->configs = $configs;
  }

  /**
   * Gets the configs value.
   *
   * @return array
   *   Returns the array of configs.
   */
  public function getConfigs() {
    return $this->configs;
  }

  /**
   * Sets the entity value.
   *
   * @param Drupal\Core\Entity\EntityInterface $entity
   *   Sets the object's $entity value.
   */
  public function setEntity(EntityInterface $entity) {
    $this->entity = $entity;
  }

  /**
   * Gets the entity value.
   *
   * @return Drupal\Core\Entity\EntityInterface
   *   Returns the EntityInterface value of entity.
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * Gets the request type.
   *
   * @return string
   *   Request type. (IE. POST, GET, DELETE, etc).
   */
  abstract protected function getRequestType();

  /**
   * Gets the URI end-point for the request.
   *
   * @return string
   *   URI end-point for the request.
   */
  abstract protected function getUri();

  /**
   * Builds the Guzzle HTTP Request.
   *
   * @throws GuzzleHttp\Exception\RequestException
   *   Thrown by Guzzle when creating an invalid Request.
   *
   * @return GuzzleHttp\Psr7\Request
   *   The Guzzle HTTP Request Object.
   */
  protected function buildRequest() {
    $requestType = $this->getRequestType();
    $uri = $this->getUri();
    $request = new Request($requestType, $uri);

    return $request;
  }

  /**
   * Returns the request param array.
   *
   * @return array
   *   Required params for the applicable service.
   */
  abstract protected function getRequestParams();

  /**
   * Sends the Request and Request Body.
   *
   * @param GuzzleHttp\Psr7\Request $request
   *   The Guzzle HTTP Request Object.
   *
   * @throws GuzzleHttp\Exception\BadResponseException
   *   Thrown when receiving 4XX or 5XX error.
   *
   * @return GuzzleHttp\Psr7\Response
   *   The Guzzle HTTP Response Object.
   */
  protected function sendRequest(Request $request) {
    $requestParams = $this->getRequestParams();
    $response = $this->client->send($request, $requestParams);

    return $response;
  }

  /**
   * Handles the Response.
   *
   * @param GuzzleHttp\Psr7\Response $response
   *   Handles the Guzzle Response as needed.
   */
  abstract protected function handleResponse(Response $response);

  /**
   * {@inheritdoc}
   */
  abstract public function execute($entity = NULL);

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'identifier_type' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['identifier_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Identifier Type'),
      '#empty_option' => $this->t('- None -'),
      '#default_value' => ($this->configuration['identifier_type']) ?: $this->t('- None -'),
      '#options' => $this->utils->getIdentifiers(),
      '#description' => $this->t('The persistent identifier configuration to be used.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration = $form_state->getValues();
  }

}
