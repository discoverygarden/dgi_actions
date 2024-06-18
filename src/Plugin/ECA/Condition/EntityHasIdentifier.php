<?php

namespace Drupal\dgi_actions\Plugin\ECA\Condition;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\dgi_actions\Utility\IdentifierUtils;
use Drupal\eca\EcaState;
use Drupal\eca\Plugin\ECA\Condition\ConditionBase;
use Drupal\eca\Token\TokenInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * ECA condition plugin for comparing two scalar values.
 *
 * @EcaCondition(
 *   id = "dgi_actions_eca_entity_persistent_identifier_populated",
 *   label = @Translation("Entity has persistent identifier"),
 *   description = @Translation("Determines where an entity already has a persistent identifier."),
 *   context_definitions = {
 *     "entity" = @ContextDefinition("entity", label = @Translation("Entity"))
 *   }
 * )
 */
class EntityHasIdentifier extends ConditionBase {

  protected IdentifierUtils $utils;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, RequestStack $request_stack, TokenInterface $token_services, AccountProxyInterface $current_user, TimeInterface $time, EcaState $state, IdentifierUtils $utils) {
    $this->utils = $utils;
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $entity_type_bundle_info, $request_stack, $token_services, $current_user, $time, $state);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): ConditionBase {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('request_stack'),
      $container->get('eca.token_services'),
      $container->get('current_user'),
      $container->get('datetime.time'),
      $container->get('eca.state'),
      $container->get('dgi_actions.utils')
    );
  }

  public function evaluate(): bool {
    if (empty($this->configuration['identifier'])) {
      // XXX: We are not configured... should have no influence on things;
      // however, they have not implemented 3-value logic, so we have to return
      // TRUE to stay out of the way.
      return TRUE;
    }

    $entity = $this->getValueFromContext('entity');
    if ($entity instanceof FieldableEntityInterface) {
      $identifier = $this->entityTypeManager->getStorage('dgiactions_identifier')->load($this->configuration['identifier']);
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
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    // Get the value from the form_state if AJAX has triggered this, default
    // to what is stored on the entity otherwise.
    $triggering_element = $form_state->getTriggeringElement();
    $identifier_parents = [
      'condition',
      'identifier',
    ];
    if (!empty($triggering_element) && $triggering_element['#parents'] === $identifier_parents) {
      $selected_identifier = $form_state->getCompleteFormState()->getValue('condition')['identifier'];
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
    return $form['condition']['configuration']['identifier_container'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
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

}
