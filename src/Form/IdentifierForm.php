<?php

namespace Drupal\dgi_actions\Form;

use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Constructs the form for Identifier entities.
 */
class IdentifierForm extends EntityBundleSelectionForm {

  /**
   * The drupal config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The targeted field.
   *
   * @var string
   */
  protected $targetField;

  /**
   * The data profile ID.
   *
   * @var string
   */
  protected $dataProfile;

  /**
   * The service data id.
   *
   * @var string
   */
  protected $serviceData;

  /**
   * Constructs a new class instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManager $entityFieldManager
   *   The drupal core entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfo $entityTypeBundleInfo
   *   The drupal core entity type bundle info.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFieldManager $entityFieldManager, EntityTypeBundleInfo $entityTypeBundleInfo) {
    parent::__construct($entityFieldManager, $entityTypeBundleInfo);
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entityFieldManager;
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    if ($this->getOperation() === 'edit') {
      // Set all the properties here.
      $this->targetField = $this->entity->get('field');
      $this->targetBundle = $this->entity->get('bundle');
      $this->targetEntity = $this->entity->get('entity');
      $this->dataProfile = $this->entity->get('data_profile');
      $this->serviceData = $this->entity->get('service_data');
    }

    $triggering_element = $form_state->getTriggeringElement();
    if (isset($triggering_element['#parents'])) {
      if ($triggering_element['#parents'] === ['entity']) {
        $this->targetEntity = !empty($form_state->getValue('entity')) ? $form_state->getValue('entity') : NULL;
        unset($this->targetBundle);
      }
      if ($triggering_element['#parents'] === ['bundle']) {
        $this->targetBundle = !empty($form_state->getValue('bundle')) ? $form_state->getValue('bundle') : NULL;
        unset($this->targetField);
      }
      if ($triggering_element['#parents'] === ['data_profile']) {
        $this->dataProfile = !empty($form_state->getValue('data_profile')) ? $form_state->getValue('data_profile') : NULL;
      }

    }

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->label(),
      '#description' => $this->t('Label for the Identifier entity.'),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\dgi_actions\Entity\Identifier::load',
      ],
      '#disabled' => !$this->entity->isNew(),
    ];

    $form['entity_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Entity Selection'),
    ];

    $form['entity_fieldset']['entity'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity'),
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $this->targetEntity,
      '#options' => $this->getEntityOptionsForDropdown(),
      '#description' => $this->t('The entity type of the Identifier will be minted.'),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => [$this, 'entityDropdownCallback'],
        'wrapper' => 'bundle-fieldset-container',
      ],
    ];
    $entity_fieldset =& $form['entity_fieldset'];

    // Setup containers for AJAX.
    $entity_fieldset['bundle_fieldset_container'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'bundle-fieldset-container'],
    ];
    $entity_fieldset['bundle_fieldset_container']['bundle_fieldset']['fields_fieldset_container'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'fields-fieldset-container'],
      '#weight' => 10,
    ];

    if ($this->targetEntity) {
      $entity_fieldset['bundle_fieldset_container']['bundle_fieldset'] += [
        '#type' => 'fieldset',
        '#title' => $this->t('Bundle Selection'),
      ];

      $bundle_fieldset =& $entity_fieldset['bundle_fieldset_container']['bundle_fieldset'];
      $bundle_fieldset['bundle'] = [
        '#type' => 'select',
        '#title' => $this->t('Bundle'),
        '#empty_option' => $this->t('- None -'),
        '#default_value' => $this->targetBundle,
        '#options' => $this->getEntityBundlesForDropdown(),
        '#description' => $this->t('The Bundle of the selected Entity Type.'),
        '#required' => TRUE,
        '#ajax' => [
          'callback' => [$this, 'bundleDropdownCallback'],
          'wrapper' => 'fields-fieldset-container',
        ],
      ];

      if ($this->targetBundle) {
        $bundle_fieldset['fields_fieldset_container']['fields_fieldset'] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Field Selection'),
        ];

        $fields_fieldset =& $bundle_fieldset['fields_fieldset_container']['fields_fieldset'];
        $fields_fieldset['field'] = [
          '#type' => 'select',
          '#title' => $this->t('Entity Field'),
          '#empty_option' => $this->t('- None -'),
          '#default_value' => $this->targetField,
          '#options' => $this->getFieldsForDropdown($this->targetEntity, $this->targetBundle),
          '#description' => $this->t('The entity field that the identifier will be minted into.'),
          '#required' => TRUE,
        ];
      }
    }
    $form['service_data'] = [
      '#type' => 'select',
      '#title' => $this->t('Service Data Profile'),
      '#empty_option' => $this->t('- None -'),
      '#options' => $this->getServiceDataOptionsForDropdown(),
      '#default_value' => $this->serviceData,
      '#description' => $this->t('The Service Data service to be used with this Identifier. (IE. Controls what Service to perform CRUD operations with.)'),
    ];

    $form['dataprofile_fieldset_container'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'dataprofile-fieldset-container'],
    ];
    $form['dataprofile_fieldset_container']['data_profile'] = [
      '#type' => 'select',
      '#title' => $this->t('Data Profile'),
      '#empty_option' => $this->t('- None -'),
      '#options' => $this->getDataProfileOptionsForDropdown(),
      '#default_value' => $this->dataProfile,
      '#description' => $this->t('The Data Profile to be used with this Identifier. (IE. Controls what Data is sent to the Identifier service.)'),
      '#ajax' => [
        'callback' => [$this, 'dataprofileDropdownCallback'],
        'wrapper' => 'dataprofile-fieldset-container',
      ],
    ];
    if ($this->dataProfile) {
      $form['dataprofile_fieldset_container']['dataprofile_fieldset'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Data Profile Configuration'),
      ];
      $form['dataprofile_fieldset_container']['dataprofile_fieldset']['dataprofile_entity_type'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Entity Type'),
        '#maxlength' => 255,
        '#default_value' => $this->entity->getDataProfile()->get('entity'),
        '#description' => $this->t('The Entity Type configured in the selected Data Profile config.'),
        '#disabled' => TRUE,
      ];
      $form['dataprofile_fieldset_container']['dataprofile_fieldset']['dataprofile_bundle_type'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Bundle Type'),
        '#maxlength' => 255,
        '#default_value' => $this->entity->getDataProfile()->get('bundle'),
        '#description' => $this->t('The Bundle Type configured in the selected Data Profile config.'),
        '#disabled' => TRUE,
      ];
    }
    return $form;
  }

  /**
   * Entity Dropdown AJAX Callback function.
   */
  public function entityDropdownCallback(array $form, FormStateInterface $form_state) {
    return $form['entity_fieldset']['bundle_fieldset_container'];
  }

  /**
   * Bundle Dropdown AJAX Callback function.
   */
  public function bundleDropdownCallback(array $form, FormStateInterface $form_state) {
    return $form['entity_fieldset']['bundle_fieldset_container']['bundle_fieldset']['fields_fieldset_container'];
  }

  /**
   * DataProfile Dropdown AJAX Callback function.
   */
  public function dataprofileDropdownCallback(array $form, FormStateInterface $form_state) {
    return $form['dataprofile_fieldset_container'];
  }

  /**
   * Helper that gets all available entities as options for a dropdown.
   *
   * @param string $entity_id
   *   The entity type to be retrieved.
   *
   * @return array
   *   An associative array where the key is the entity ID and the value is the
   *   label of the entity.
   */
  public function getOptionsForDropdown(string $entity_id): array {
    $entities = $this->entityTypeManager->getStorage($entity_id)->loadMultiple();
    $entities_options = [];
    if (!empty($entities)) {
      foreach ($entities as $entity) {
        $entities_options[$entity->id()] = $entity->label();
      }
    }
    return $entities_options;
  }

  /**
   * Gets all available data profile entities as options.
   *
   * @return array
   *   An associative array where the key is the entity ID and the value is the
   *   label of the entity.
   */
  public function getDataProfileOptionsForDropdown(): array {
    return $this->getOptionsForDropdown('dgiactions_dataprofile');
  }

  /**
   * Gets all available service data types as options.
   *
   * @return array
   *   An associative array where the key is the entity ID and the value is the
   *   label of the entity.
   */
  public function getServiceDataOptionsForDropdown(): array {
    return $this->getOptionsForDropdown('dgiactions_servicedata');
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $identifier = $this->entity;
    $status = $identifier->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('Created the %label Identifier entity.', [
          '%label' => $identifier->label(),
        ]));
        break;

      default:
        $this->messenger()->addStatus($this->t('Saved the %label Identifier entity.', [
          '%label' => $identifier->label(),
        ]));
    }
    $form_state->setRedirectUrl($identifier->toUrl('collection'));
  }

}
