<?php

namespace Drupal\dgi_actions\Plugin\Condition;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Entity\DependencyTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\dgi_actions\Entity\IdentifierInterface;
use Drupal\dgi_actions\Utility\IdentifierUtils;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Condition to check an Entity for an existing persistent identifier.
 *
 * @Condition(
 *   id = "dgi_actions_entity_persistent_identifier_populated",
 *   label = @Translation("Entity has persistent identifier"),
 *   context_definitions = {
 *     "entity" = @ContextDefinition("entity", required = FALSE, label = @Translation("Entity"))
 *   }
 * )
 */
class EntityHasIdentifier extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  use DependencyTrait;
  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Utils.
   *
   * @var \Drupal\dgi_actions\Utility\IdentifierUtils
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
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\dgi_actions\Utility\IdentifierUtils $utils
   *   Identifier utils.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    LoggerInterface $logger,
    EntityTypeManagerInterface $entity_type_manager,
    IdentifierUtils $utils
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->logger = $logger;
    $this->entityTypeManager = $entity_type_manager;
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
      $container->get('logger.channel.dgi_actions'),
      $container->get('entity_type.manager'),
      $container->get('dgi_actions.utils')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(): bool {
    if (empty($this->configuration['identifier'])) {
      // XXX: We are not configured... should have no influence on things;
      // however, they have not implemented 3-value logic, so we have to return
      // TRUE to stay out of the way.
      return TRUE;
    }

    $entity = $this->getContextValue('entity');
    if ($entity instanceof FieldableEntityInterface && ($identifier = $this->getIdentifier())) {
      $field = $identifier->get('field');
      $entity_type = $identifier->get('entity');
      $bundle = $identifier->get('bundle');
      if (!empty($field) && $entity->hasField($field) && $entity->getEntityTypeId() == $entity_type && $entity->bundle() == $bundle) {
        return !$entity->get($field)->isEmpty();
      }
    }
    return FALSE;
  }

  /**
   * Helper; get the target identifier entity.
   *
   * @return \Drupal\dgi_actions\Entity\IdentifierInterface|null
   *   The loaded entity, or NULL.
   */
  protected function getIdentifier() : ?IdentifierInterface {
    return $this->configuration['identifier'] ?
      $this->entityTypeManager->getStorage('dgiactions_identifier')->load($this->configuration['identifier']) :
      NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function summary(): MarkupInterface {
    if (!empty($this->configuration['negate'])) {
      return $this->t('Entity does not have a persistent identifier.');
    }
    else {
      return $this->t('Entity has a persistent identifier.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    // Get the value from the form_state if AJAX has triggered this, default
    // to what is stored on the entity otherwise.
    $triggering_element = $form_state->getTriggeringElement();
    $identifier_parents = [
      'conditions',
      'dgi_actions_entity_persistent_identifier_populated',
      'identifier',
    ];
    if (!empty($triggering_element) && $triggering_element['#parents'] === $identifier_parents) {
      $selected_identifier = $form_state->getValue('conditions')['dgi_actions_entity_persistent_identifier_populated']['identifier'];
    }
    else {
      $selected_identifier = $this->configuration['identifier'];
    }

    $form['identifier'] = [
      '#type' => 'select',
      '#title' => $this->t('Identifier Type'),
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $selected_identifier,
      '#options' => $this->utils->getIdentifiers(),
      '#description' => $this->t('The persistent identifier configuration to be used.'),
      '#ajax' => [
        'callback' => [$this, 'identifierDropdownCallback'],
        'wrapper' => 'identifier-container',
      ],
    ];

    $form['identifier_container'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'identifier-container'],
    ];

    if ($selected_identifier) {
      $identifier_config = $this->entityTypeManager->getStorage('dgiactions_identifier')->load($selected_identifier);
      $form['identifier_container']['identifier_fieldset'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Identifier Configuration'),
        '#access' => !is_null($selected_identifier),
        '#disabled' => is_null($selected_identifier),
      ];
      $form['identifier_container']['identifier_fieldset']['entity_type'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Entity Type'),
        '#maxlength' => 255,
        '#default_value' => ($identifier_config->get('entity')) ?: '',
        '#description' => $this->t('The Entity type configured in the selected Identifier config.'),
        '#disabled' => TRUE,
      ];
      $form['identifier_container']['identifier_fieldset']['bundle_type'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Bundle Type'),
        '#maxlength' => 255,
        '#default_value' => ($identifier_config->get('bundle')) ?: '',
        '#description' => $this->t('The Bundle type configured in the selected Identifier config.'),
        '#disabled' => TRUE,
      ];
      $form['identifier_container']['identifier_fieldset']['field'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Field'),
        '#default_value' => ($identifier_config->get('field')) ?: '',
        '#description' => $this->t('The field configured to have the identifier placed into.'),
        '#disabled' => TRUE,
      ];
    }

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * Identifier Dropdown AJAX Callback function.
   */
  public function identifierDropdownCallback(array $form, FormStateInterface $form_state) {
    return $form['conditions']['condition-dgi_actions_entity_persistent_identifier_populated']['options']['identifier_container'];
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
  public function defaultConfiguration(): array {
    return array_merge(
      ['identifier' => NULL],
      parent::defaultConfiguration()
    );
  }

  /**
   * {@inheritDoc}
   */
  public function calculateDependencies() : array {
    $this->addDependencies(parent::calculateDependencies());
    if ($identifier = $this->getIdentifier()) {
      $this->addDependency($identifier->getConfigDependencyKey(), $identifier->getConfigDependencyName());
    }

    return $this->dependencies;
  }

}
