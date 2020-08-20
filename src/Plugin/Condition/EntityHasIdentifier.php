<?php

namespace Drupal\dgi_actions\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfo;
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
 *   }
 * )
 */
class EntityHasIdentifier extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Term storage.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entity_type_manager;

  /**
   * Entity Field Manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entity_field_manager;

  /**
   * Config Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $config_factory;

  /**
   * Entity Type Bundle Info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfo
   */
  protected $entity_type_bundle_info;

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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param Drupal\Core\Config\ConfigFactory
   *   Config factory.
   * @param Drupal\Core\Entity\EntityFieldManager
   *   Entity field manager.
   * @param Psr\Log\LoggerInterface $logger
   *   Logger.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    ConfigFactory $config_factory,
    EntityFieldManager $entity_field_manager,
    EntityTypeBundleInfo $entity_type_bundle_info,
    LoggerInterface $logger,
    IdentifierUtils $utils
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
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
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('logger.channel.dgi_actions'),
      $container->get('dgi_actions.utils')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $node = $this->getContextValue('node');
    if (!$node && !$this->isNegated()) {
      return FALSE;
    }
    elseif (!$node) {
      return FALSE;
    }
    else {
      $configs = $this->utils->getAssociatedConfigs($this->configuration['identifier']);

      // Check if the Bundle type has the configured identifier's field and that it's empty
        // placeholder - not considered functional
      if ($node->hasField($configs['credentials']->get('field')) && empty($node->getField($configs['credentials']->get('field')))) {
        return TRUE;
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
