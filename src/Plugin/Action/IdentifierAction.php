<?php

namespace Drupal\dgi_actions\Plugin\Action;

use Drupal\dgi_actions\Utility\IdentifierUtils;
use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Base class for Identifier Actions.
 */
abstract class IdentifierAction extends ConfigurableActionBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * Configured Identifier config values.
   *
   * @var array
   */
  protected $configs;

  /**
   * Logger.
   *
   * @var Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Http Client connection.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Entity Field Manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * Config Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

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
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   Entity type manager.
   * @param Psr\Log\LoggerInterface $logger
   *   Logger.
   * @param Drupal\Core\Entity\EntityFieldManager $entity_field_manager
   *   Entity field manager.
   * @param Drupal\Core\Config\ConfigFactory $config_factory
   *   Config factory.
   * @param Drupal\dgi_actions\Utilities\IdentifierUtils $utils
   *   Identifier utils.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    Client $client,
    EntityTypeManager $entity_type_manager,
    LoggerInterface $logger,
    EntityFieldManager $entity_field_manager,
    ConfigFactory $config_factory,
    IdentifierUtils $utils
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->client = $client;
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
    $this->entityFieldManager = $entity_field_manager;
    $this->configFactory = $config_factory;
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
      $container->get('entity_type.manager'),
      $container->get('logger.factory')->get('dgi_actions'),
      $container->get('entity_field.manager'),
      $container->get('config.factory'),
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
   * @param EntityInterface $entity
   *   The entity.
   *
   * @throws UndefinedLinkTemplateException
   *
   * @return string
   *   Entitiy's external URL as a string.
   */
  protected function getExternalUrl(EntityInterface $entity) {
    if ($entity) {
      return $entity->toUrl('canonical', ['absolute' => TRUE])->toString();
    }
  }

  /**
   * {@inheritdoc}
   */
  abstract public function execute();

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
      '#default_value' => $this->configuration['identifier_type'],
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

    $this->configs = $this->utils->getAssociatedConfigs($this->configuration['identifier_type']);
  }

}
