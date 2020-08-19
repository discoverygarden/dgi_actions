<?php

namespace Drupal\dgi_actions\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a condition to check an Entity for an existing persistent identifier.
 *
 * @Condition(
 *   id = "dgi_actions_entity_has_persistent_identifier",
 *   label = @Translation("Entity has a persistent identifier"),
 *   context_definitions = {
 *     "node" = @ContextDefinition("entity:node", required = FALSE, label = @Translation("Node")),
 *     "media" = @ContextDefinition("entity:media", required = FALSE, label = @Translation("Media")),
 *     "taxonomy_term" = @ContextDefinition("entity:taxonomy_term", required = FALSE, label = @Translation("Term"))
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
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    ConfigFactory $config_factory,
    EntityFieldManager $entity_field_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->entityFieldManager = $entity_field_manager;
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
      $container->get('entity_field.manager')
    );
  }

  /**
   * Returns list of Identifier Configs.
   */
  public static function getIdentifiers() {
    $configs = $this->configFactory->listAll('dgi_actions.identifier');
    if (!empty($configs)) {
      $config_options = [];
      foreach ($configs as $config_id) {
        $config_options[$config_id] = $this->configFactory->get($config_id)->get('label');
      }
      return $config_options;
    }

    return 'No Identifiers Configured';
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
      // hasField will reference the configured Field $this->configuration['field']
        // That field value will be pulled from the specified config, or ALL dgi_actions.identifier.* configs (former is probably safer).
      if ($node->hasField('field_ark_identifier')) {
        // Check if the field is populated
        // If it is, return FALSE, ELSE return TRUE
        return TRUE;
      }
      return TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    if (!empty($this->configuration['negate'])) {
      return $this->t('The node is not.');
    }
    else {
      return $this->t('The node is.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $options = [];
    foreach (['node', 'media', 'taxonomy_term'] as $content_entity) {
      $bundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo($content_entity);
      foreach ($bundles as $bundle => $bundle_properties) {
        $bundle_fields = $this->entityFieldManager->getFieldDefinitions($content_entity, $bundle);
        if (isset($bundle_fields['field_ark_identifier'])) { // Need to reference the config value for the field name, instead of this hardcoded one.
          $options[$bundle] = $this->t('@bundle (@type)', [
            '@bundle' => $bundle_properties['label'],
            '@type' => $content_entity,
          ]);
        }
      }
    }

    $form['bundles'] = [
      '#title' => $this->t('Bundles'),
      '#type' => 'checkboxes',
      '#options' => $options,
      '#default_value' => $this->configuration['bundles'],
    ];

    return parent::buildConfigurationForm($form, $form_state);;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['bundles'] = array_filter($form_state->getValue('bundles'));
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array_merge(
      ['bundles' => []],
      parent::defaultConfiguration()
    );
  }

}
