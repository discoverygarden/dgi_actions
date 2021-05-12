<?php

namespace Drupal\dgi_actions\Form;

use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Form\FormStateInterface;
use Drupal\dgi_actions\Plugin\DataProfileManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ConfigSplitEntityForm.
 *
 * @package Drupal\dgi_actions\Form
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
    $this->entityFieldManager = $entityFieldManager;
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
    $this->dataProfileManager = $data_profile_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): DataProfileForm {
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

    $entity_bundle_array = $this->bundleDropdownList($entity_bundles);
    $entity_bundle_fields = $entity_bundle_array['entity_bundle_fields'];
    $bundle_options = $entity_bundle_array['bundle_options'];


    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->label(),
      '#description' => $this->t("Label for the Data Profile entity."),
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

    // Entity Fieldset Reference.
    $entity_fieldset =& $form['entity_fieldset'];
    $entity_fieldset['entity'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity'),
      '#empty_option' => $this->t('- None -'),
      '#default_value' => ($selected['entity']) ?: NULL,
      '#options' => $entity_options,
      '#description' => $this->t('The entity that the data will be captured.'),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::entityDropdownCallback',
        'wrapper' => 'bundle-fieldset-container',
      ],
    ];
    $entity_fieldset['choose_entity'] = [
      '#type' => 'submit',
      '#value' => $this->t('Choose Entity'),
      '#states' => [
        'visible' => ['body' => ['value' => TRUE]],
      ],
    ];
    $form['bundle_fieldset_container'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'bundle-fieldset-container'],
    ];
    $form['bundle_fieldset_container']['bundle_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Bundle Selection'),
    ];

    // Bundle Fieldset Reference.
    $bundle_fieldset =& $form['bundle_fieldset_container']['bundle_fieldset'];
    $bundle_fieldset['bundle'] = [
      '#type' => 'select',
      '#title' => $this->t('Bundle'),
      '#empty_option' => $this->t('- None -'),
      '#default_value' => ($selected['bundle']) ?: NULL,
      '#options' => (isset($bundle_options[$selected['entity']])) ? $bundle_options[$selected['entity']] : [],
      '#description' => $this->t('The Bundle of the selected Entity Type.'),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::bundleDropdownCallback',
        'wrapper' => 'dataprofile-fieldset-container',
      ],
    ];
    $bundle_fieldset['choose_bundle'] = [
      '#type' => 'submit',
      '#value' => $this->t('Choose Bundle'),
      '#states' => [
        'visible' => [':input[name="bundle"]' => ['value' => TRUE]],
      ],
    ];
    $form['bundle_fieldset_container']['dataprofile_fieldset_container'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'dataprofile-fieldset-container'],
    ];
    $form['bundle_fieldset_container']['dataprofile_fieldset_container']['dataprofile_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Data Profile Selection'),
    ];

    // Data Profile Fieldset Reference.
    $dataprofile_fieldset =& $form['bundle_fieldset_container']['dataprofile_fieldset_container']['dataprofile_fieldset'];
    $dataprofile_fieldset['dataprofile'] = [
      '#type' => 'select',
      '#title' => $this->t('Data Profile Type'),
      '#empty_option' => $this->t('- None -'),
      '#default_value' => ($selected['dataprofile']) ?: NULL,
      '#options' => ($selected['entity'] && $selected['bundle']) ? $data_profile_options : [],
      '#description' => $this->t('The Data Profile type to be used for the Data Profile Config'),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::dataprofileFieldsDropdownCallback',
        'wrapper' => 'dataprofile-fields-fieldset-container',
      ],
    ];
    $dataprofile_fieldset['choose_dataprofile'] = [
      '#type' => 'submit',
      '#value' => $this->t('Choose Data Profile'),
      '#states' => [
        'visible' => [':input[name="dataprofile"]' => ['value' => TRUE]],
      ],
    ];
    $dataprofile_fieldset['dataprofile_fields_fieldset_container'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'dataprofile-fields-fieldset-container'],
    ];

    if (isset($data_profile_configs[$selected['dataprofile']])) {
      $dataprofile_fieldset['dataprofile_fields_fieldset_container']['dataprofile_fields_fieldset'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Data Profile Fields'),
      ];

      // Data Profile Fields Fieldset Reference.
      $dataprofile_fields_fieldset =& $dataprofile_fieldset['dataprofile_fields_fieldset_container']['dataprofile_fields_fieldset'];
      $fields = $data_profile_configs[$selected['dataprofile']]->get('fields');
      foreach ($fields as $field) {
        $field_key = str_replace('.', '_', $field['key']);
        $dataprofile_fields_fieldset[$field_key] = [
          '#type' => 'select',
          '#title' => $field['label'],
          '#empty_option' => $this->t('- None -'),
          '#default_value' => ($config->get('data')[$field_key]) ?: NULL,
          '#options' => ($selected['entity'] && $selected['bundle']) ? $entity_bundle_fields[$selected['entity']][$selected['bundle']] : [],
          '#description' => $field['description'],
        ];
      }
    }

    if (!$selected['entity']) {
      // Change the field title to provide user with some feedback on why the
      // field is disabled.
      $bundle_fieldset['#access'] = FALSE;
      $bundle_fieldset['#disabled'] = TRUE;
      $bundle_fieldset['bundle']['#title'] = $this->t('You must choose an Entity first.');
      $bundle_fieldset['bundle']['#disabled'] = TRUE;
      $bundle_fieldset['choose_bundle']['#access'] = FALSE;
      $bundle_fieldset['choose_bundle']['#disabled'] = TRUE;
    }

    if (!$selected['bundle']) {
      // Change the field title to provide user with some feedback on why the
      // field is disabled.
      $dataprofile_fieldset['#access'] = FALSE;
      $dataprofile_fieldset['#disabled'] = TRUE;
      $dataprofile_fieldset['dataprofile']['#title'] = $this->t('You must choose a Bundle first.');
      $dataprofile_fieldset['dataprofile']['#disabled'] = TRUE;
      $dataprofile_fieldset['choose_dataprofile']['#disabled'] = TRUE;
    }

    if (!$selected['dataprofile']) {
      $dataprofile_fields_fieldset['#access'] = FALSE;
      $dataprofile_fields_fieldset['#disabled'] = TRUE;
    }

    return $form;
  }

  /**
   * Entity Dropdown AJAX Callback function.
   */
  public function entityDropdownCallback(array $form, FormStateInterface $form_state) {
    return $form['bundle_fieldset_container'];
  }

  /**
   * Bundle Dropdown AJAX Callback function.
   */
  public function bundleDropdownCallback(array $form, FormStateInterface $form_state) {
    return $form['bundle_fieldset_container']['dataprofile_fieldset_container'];
  }

  /**
   * Data Profile Dropdown AJAX Callback function.
   */
  public function dataprofileFieldsDropdownCallback(array $form, FormStateInterface $form_state) {
    return $form['bundle_fieldset_container']['dataprofile_fieldset_container']['dataprofile_fieldset']['dataprofile_fields_fieldset_container'];
  }

   /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $trigger = (string) $form_state->getTriggeringElement()['#value'];
    if (($trigger) == 'Save') {
      $this->setDataprofileDataFields($form_state);
    }
    else {
      $form_state->setRebuild();
    }
  }

  /**
   * A helper function to set the Data Profile fields.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The FormState entity.
   */
  public function setDataprofileDataFields(FormStateInterface $form_state) {
    $data_profile_data = self::dataprofileLists();
    $config =& $this->entity;

    $fields = $data_profile_data['data_profile_configs'][$config->getDataprofile()]->get('fields');
    $data = [];
    foreach ($fields as $field) {
      $form_key = str_replace('.', '_', $field['key']);
      if (!empty($form_state->getValue($form_key))) {
        $data[$form_key] = [
          'key' => $field['key'],
          'source_field' => $form_state->getValue($form_key),
        ];
      }
    }

    // Clearing the data in case there was a different
    // Data Profile with data set previously.
    $config->setData([]);
    $config->setData($data);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $dataprofile = $this->entity;
    $status = $dataprofile->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('Created the %label Data Profile setting.', [
          '%label' => $dataprofile->label(),
        ]));
        break;

      default:
        $this->messenger()->addStatus($this->t('Saved the %label Data Profile setting.', [
          '%label' => $dataprofile->label(),
        ]));
    }
    $form_state->setRedirectUrl($dataprofile->toUrl('collection'));
  }

}
