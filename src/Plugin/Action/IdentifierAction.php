<?php

namespace Drupal\dgi_actions\Plugin\Action;

use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Entity\DependencyTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\dgi_actions\Entity\IdentifierInterface;
use Drupal\dgi_actions\Utility\IdentifierUtils;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Identifier Actions.
 */
abstract class IdentifierAction extends ConfigurableActionBase implements ContainerFactoryPluginInterface {

  use DependencyTrait;

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
    LoggerInterface $logger,
    IdentifierUtils $utils,
    EntityTypeManagerInterface $entity_type_manager
  ) {

    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
    return $this->entity->toUrl()->setAbsolute()->setOption('alias', TRUE)->toString(TRUE)->getGeneratedUrl();
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
   * Gets the Identifier from the entity's field.
   *
   * @throws \InvalidArgumentException
   *   If the Entity doesn't have the configured identifier field.
   *
   * @return string
   *   Returns the value stored in the identifier field as a string.
   */
  public function getIdentifierFromEntity(): string {
    $field = $this->identifier->get('field');
    $identifier = $this->entity->get($field)->getString();
    if (empty($identifier)) {
      $this->logger->error('Identifier field @field is empty.', ['@field' => $field]);
    }

    return $identifier;
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

  /**
   * {@inheritDoc}
   */
  public function calculateDependencies() : array {
    $this->addDependencies(parent::calculateDependencies());
    if ($this->identifier) {
      $this->addDependency($this->identifier->getConfigDependencyKey(), $this->identifier->getConfigDependencyName());
    }
    return $this->dependencies;
  }

}
