<?php

namespace Drupal\dgi_actions\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Provides a condition to check an Entity for an existing persistent identifier.
 *
 * @Condition(
 *   id = "dgi_actions_entity_has_persistent_identifier",
 *   label = @Translation("Entity has a persistent identifier"),
 *   context_definitions = {
 *     "entity" = @ContextDefinition("entity", required = TRUE, label = @Translation("Entity")),
 *   }
 * )
 */
class EntityHasIndentifier extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Entity Type Manager
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entity_type_manager;

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
   * @param \Drupal\Core\Entity\EntityStorageInterface $node_type_storage
   *   The entity storage.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInferface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // $this->configuration = $form_state->getValues(); Do specific values instead of trying to capture all
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    // Get the entity from the context
    // check the field
      // Field differs depending on the identifier - can I get that from a config here?
    // If field is not populated, return TRUE, else FALSE
    dsm($this->getContextValue());
    $node = $this->getContextValue('node');
    if ($node) {
      $entity_identifier = $node->get('field_ark_identifier')->getValue();
      if ($entity_identifier) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
