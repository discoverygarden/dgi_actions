<?php

namespace Drupal\dgi_actions\Form;

use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\dgi_actions\Plugin\DataProfileManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Data profile entity form.
 */
class DataProfileForm extends EntityBundleSelectionForm {

  /**
   * The Data Profile manager.
   *
   * @var \Drupal\dgi_actions\Plugin\DataProfileManager
   */
  protected $dataProfileManager;

  /**
   * The data profile plugin.
   *
   * @var \Drupal\dgi_actions\Plugin\DataProfileInterface
   */
  protected $plugin;

  /**
   * Constructs a new class instance.
   *
   * @param \Drupal\Core\Entity\EntityFieldManager $entityFieldManager
   *   The drupal core entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfo $entityTypeBundleInfo
   *   The drupal core entity type bundle info.
   * @param \Drupal\dgi_actions\Plugin\DataProfileManager $data_profile_manager
   *   The Data Profile plugin manager.
   */
  public function __construct(EntityFieldManager $entityFieldManager, EntityTypeBundleInfo $entityTypeBundleInfo, DataProfileManager $data_profile_manager) {
    parent::__construct($entityFieldManager, $entityTypeBundleInfo);
    $this->dataProfileManager = $data_profile_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_field.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('plugin.manager.data_profile')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    if ($this->getOperation() === 'edit') {
      $this->plugin = $this->plugin ?? $this->entity->getDataProfilePlugin();
      $this->targetEntity = $this->targetEntity ?? $this->entity->get('entity');
      $this->targetBundle = $this->targetBundle ?? $this->entity->get('bundle');
    }

    // Grab the list of available service data types.
    $definitions = $this->dataProfileManager->getDefinitions();
    $options = [];

    foreach ($definitions as $service => $definition) {
      $options[$service] = $definition['label'];
    }

    $triggering_element = $form_state->getTriggeringElement();

    if (isset($triggering_element['#parents'])) {
      if ($triggering_element['#parents'] === ['entity']) {
        $this->targetEntity = !empty($form_state->getValue('entity')) ? $form_state->getValue('entity') : NULL;
        unset($this->targetBundle);
        unset($this->plugin);
      }
      if ($triggering_element['#parents'] === ['bundle']) {
        $this->targetBundle = !empty($form_state->getValue('bundle')) ? $form_state->getValue('bundle') : NULL;
        unset($this->plugin);
      }
      if ($triggering_element['#parents'] === ['data_profile']) {
        $this->plugin = !empty($form_state->getValue('data_profile')) ? $this->dataProfileManager->createInstance($form_state->getValue('data_profile')) : NULL;
      }
    }
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->label(),
      '#description' => $this->t('Label for the Data Profile entity.'),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\dgi_actions\Entity\DataProfile::load',
      ],
    ];

    $form['entity_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Entity Selection'),
    ];

    // Setup containers for AJAX.
    $form['entity_fieldset']['bundle_fieldset_container'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'bundle-fieldset-container'],
      '#weight' => 10,
    ];
    $form['entity_fieldset']['bundle_fieldset_container']['bundle_fieldset']['dataprofile_fieldset_container'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'dataprofile-fieldset-container'],
      '#weight' => 10,
    ];
    $form['entity_fieldset']['bundle_fieldset_container']['bundle_fieldset']['dataprofile_fieldset_container']['dataprofile_fieldset']['dataprofile_fields_fieldset_container'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'dataprofile-fields-fieldset-container'],
      '#weight' => 10,
    ];

    $entity_fieldset =& $form['entity_fieldset'];
    $entity_fieldset['entity'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity'),
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $this->targetEntity,
      '#options' => $this->getEntityOptionsForDropdown(),
      '#description' => $this->t('The entity from which the data will be captured.'),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => [$this, 'entityDropdownCallback'],
        'wrapper' => 'bundle-fieldset-container',
      ],
    ];
    if ($this->targetEntity) {
      $form['entity_fieldset']['bundle_fieldset_container']['bundle_fieldset'] += [
        '#type' => 'fieldset',
        '#title' => $this->t('Bundle Selection'),
      ];

      $bundle_fieldset =& $form['entity_fieldset']['bundle_fieldset_container']['bundle_fieldset'];
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
          'wrapper' => 'dataprofile-fieldset-container',
        ],
      ];

      if ($this->targetBundle) {
        $bundle_fieldset['dataprofile_fieldset_container']['dataprofile_fieldset'] += [
          '#type' => 'fieldset',
          '#title' => $this->t('Data Profile Selection'),
        ];
        $dataprofile_fieldset =& $bundle_fieldset['dataprofile_fieldset_container']['dataprofile_fieldset'];

        $dataprofile_fieldset['data_profile'] = [
          '#type' => 'select',
          '#title' => $this->t('Data Profile Type'),
          '#empty_option' => $this->t('- None -'),
          '#default_value' => isset($this->plugin) ? $this->plugin->getPluginId() : NULL,
          '#options' => $options,
          '#description' => $this->t('The Data Profile type to be used.'),
          '#required' => TRUE,
          '#ajax' => [
            'callback' => [$this, 'dataProfileFieldsDropdownCallback'],
            'wrapper' => 'dataprofile-fields-fieldset-container',
          ],
        ];
        // Data profile fields.
        if ($this->plugin) {
          // Store the available fields for the selected entity and bundle for
          // reference in the implementing plugins.
          $form_state->setTemporaryValue('available_fields', $this->getFieldsForDropdown($this->targetEntity, $this->targetBundle));
          $dataprofile_fieldset['dataprofile_fields_fieldset_container']['fields_fieldset'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Field Configuration'),
          ];
          $dataprofile_fieldset['dataprofile_fields_fieldset_container']['fields_fieldset']['data'] = [];
          $subform_state = SubformState::createForSubform($dataprofile_fieldset['dataprofile_fields_fieldset_container']['fields_fieldset']['data'], $form, $form_state);
          $dataprofile_fieldset['dataprofile_fields_fieldset_container']['fields_fieldset']['data'] = $this->plugin->buildConfigurationForm($dataprofile_fieldset['dataprofile_fields_fieldset_container']['fields_fieldset']['data'], $subform_state);
          $dataprofile_fieldset['dataprofile_fields_fieldset_container']['fields_fieldset']['data']['#tree'] = TRUE;
        }
      }
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
    return $form['entity_fieldset']['bundle_fieldset_container']['bundle_fieldset']['dataprofile_fieldset_container'];
  }

  /**
   * Data Profile Dropdown AJAX Callback function.
   */
  public function dataprofileFieldsDropdownCallback(array $form, FormStateInterface $form_state) {
    return $form['entity_fieldset']['bundle_fieldset_container']['bundle_fieldset']['dataprofile_fieldset_container']['dataprofile_fieldset']['dataprofile_fields_fieldset_container'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $subform_state = SubformState::createForSubform($form['entity_fieldset']['bundle_fieldset_container']['bundle_fieldset']['dataprofile_fieldset_container']['dataprofile_fieldset']['dataprofile_fields_fieldset_container']['fields_fieldset']['data'], $form, $form_state);
    $this->plugin->submitConfigurationForm($form['entity_fieldset']['bundle_fieldset_container']['bundle_fieldset']['dataprofile_fieldset_container']['dataprofile_fieldset']['dataprofile_fields_fieldset_container']['fields_fieldset']['data'], $subform_state);

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = $this->entity->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('Created the %label Data Profile setting.', [
          '%label' => $this->entity->label(),
        ]));
        break;

      default:
        $this->messenger()->addStatus($this->t('Saved the %label Data Profile setting.', [
          '%label' => $this->entity->label(),
        ]));
    }
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
  }

}
