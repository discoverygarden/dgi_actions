<?php

namespace Drupal\dgi_actions\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\dgi_actions\Utility\IdentifierUtils;
use Psr\Log\LoggerInterface;

/**
 * Provides a condition to check an Entity for an existing persistent identifier.
 *
 * @Condition(
 *   id = "dgi_actions_entity_has_persistent_identifier",
 *   label = @Translation("Identifier field is empty"),
 *   context_definitions = {
 *     "entity" = @ContextDefinition("entity", required = FALSE, label = @Translation("Entity"))
 *   }
 * )
 */
class EntityHasIdentifier extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Config Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $config_factory;

  /**
   * Logger.
   *
   * @var Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Utils.
   *
   * @var Drupal\dgi_actions\Utility\IdentifierUtils
   */
  protected $utils;

  /**
   * Term storage.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
  */
  protected $entityTypeManager;

  /**
   * Constructor.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param Drupal\Core\Config\ConfigFactory
   *   Config factory.
   * @param Psr\Log\LoggerInterface $logger
   *   Logger.
   * @param Drupal\dgi_actions\Utility\IdentifierUtils $utils
   *   Identifier utils.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ConfigFactory $config_factory,
    LoggerInterface $logger,
    IdentifierUtils $utils,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->logger = $logger;
    $this->utils = $utils;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('logger.channel.dgi_actions'),
      $container->get('dgi_actions.utils'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $entity = $this->getContextValue('entity');
    if (!$entity) {
      return FALSE;
    }
    else {
      //$this->logger->info('Context Counts: @counts', ['@counts' => count($this->getContexts())]);
      //$this->logger->info('Context Bundle: @bundle', ['@bundle' => $entity->bundle()]);
      $configs = $this->utils->getAssociatedConfigs($this->configuration['identifier']);
      $field = $configs['credentials']->get('field');
      if ($entity instanceof FieldableEntityInterface && !empty($field)) {
        //$this->logger->info('@title : @type', ['@title' => $entity->getTitle(), '@type' => $entity->getType()]);
        //$this->logger->info('Field: @field', ['@field' => $field]);
        //$this->logger->info('instanceof FieldableEntityInterface: @instance', ['@instance' => ($entity instanceof FieldableEntityInterface) ? 'TRUE' : 'FALSE']);
        //$this->logger->info('Method hasField exists: @method_exists', ['@method_exists' => method_exists($entity, 'hasField')]);
        //$this->logger->info('Entity is instanceof and field is not empty');
        //$this->logger->info('Entity field isEmpty: @isempty', ['@isempty' => ($entity->get($field)->isEmpty()) ? 'TRUE' : 'FALSE']);
        if ($entity->hasField($field) && $entity->get($field)->isEmpty()) {
          //$this->logger->info('EHI - Evaluate TRUE');
          return TRUE;
        }
      }
      else {
        return FALSE;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    if (!empty($this->configuration['negate'])) {
      return $this->t('The identifier field is not empty.');
    }
    else {
      return $this->t('The identifier field is empty.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['identifier'] = [
      '#type' => 'select',
      '#title' => t('Identifier Type'),
      '#default_value' => $this->configuration['identifier'],
      '#options' => $this->utils->getIdentifiers(),
      '#description' => t('The persistent identifier configuration to be used.'),
    ];

    return parent::buildConfigurationForm($form, $form_state);;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['identifier'] = $form_state->getValue('identifier');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array_merge(
      ['identifier' => ''],
      parent::defaultConfiguration()
    );
  }

}
