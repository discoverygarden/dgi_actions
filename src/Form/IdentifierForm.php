<?php

namespace Drupal\dgi_actions\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ConfigSplitEntityForm.
 *
 * @package Drupal\dgi_actions\Form
 */
class IdentifierForm extends EntityForm {

  /**
   * The drupal state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Drupal\Core\Extension\ThemeHandler definition.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * The drupal config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The drupal Entity Field Manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * The Drupal Entity Type Bundle Info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfo
   */
  protected $entityTypeBundleInfo;

  /**
   * Constructs a new class instance.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The drupal state.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $themeHandler
   *   The theme handler.
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   The drupal core config factory.
   * @param \Drupal\Core\Entity\EntityFieldManager $entityFieldManager
   *   The drupal core entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfo $entityTypeBundleInfo
   *   The drupal core entity type bundle info.
   */
  public function __construct(StateInterface $state, ThemeHandlerInterface $themeHandler, ConfigFactory $configFactory, EntityFieldManager $entityFieldManager, EntityTypeBundleInfo $entityTypeBundleInfo) {
    $this->state = $state;
    $this->themeHandler = $themeHandler;
    $this->configFactory = $configFactory;
    $this->entityFieldManager = $entityFieldManager;
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state'),
      $container->get('theme_handler'),
      $container->get('config.factory'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\dgi_actions\Entity\IdentifierInterface $config */
    $config = $this->entity;

    $data_profile_list = $this->configFactory->listAll('dgi_actions.data_profile.');
    $service_data_list = $this->configFactory->listAll('dgi_actions.service_data.');

    $data_profile_options = self::listOptionsBuilder($data_profile_list);
    $service_data_options = self::listOptionsBuilder($service_data_list);

    $entity_array = self::entityDropdownList();
    $entity_bundles = $entity_array['entity_bundles'];
    $entity_options = $entity_array['entity_options'];

    $entity_bundle_array = self::bundleDropdownList($entity_bundles);
    $entity_bundle_fields = $entity_bundle_array['entity_bundle_fields'];
    $bundle_options = $entity_bundle_array['bundle_options'];

    $selected = [
      'entity' => '',
      'bundle' => '',
      'field' => '',
      'data_profile' => '',
    ];

    foreach ($selected as $selected_key => $selected_value) {
      // Check if the previous/currently set Entity value is a valid selection
      // If not, unset and make the user re-select.
      if (empty($form_state->getValue($selected_key))) {
        if ($config->get($selected_key)) {
          $selected[$selected_key] = $config->get($selected_key);
        }
        else {
          $selected[$selected_key] = '';
        }
      }
      else {
        if ($selected_key == 'bundle') {
          $bundle_value = (string) $form_state->getValue('bundle');
          $selected['bundle'] = (string) ($bundle_value && isset($bundle_options[$selected['entity']][$bundle_value])) ? $bundle_value : '';
        }
        else {
          $selected[$selected_key] = (string) $form_state->getValue($selected_key);
        }
      }
    }

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $config->label(),
      '#description' => $this->t("Label for the Identifier setting."),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $config->id(),
      '#machine_name' => [
        'exists' => '\Drupal\dgi_actions\Entity\Identifier::load',
      ],
    ];

    // Entity Fieldset.
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
      '#default_value' => ($selected['entity']) ?: $this->t('- None -'),
      '#options' => $entity_options,
      '#description' => $this->t('The entity type that the Identifier will be minted.'),
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

    // Bundle Fieldset.
    $entity_fieldset['bundle_fieldset_container'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'bundle-fieldset-container'],
    ];
    $entity_fieldset['bundle_fieldset_container']['bundle_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Bundle Selection'),
    ];

    // Bundle Fieldset Reference.
    $bundle_fieldset =& $entity_fieldset['bundle_fieldset_container']['bundle_fieldset'];
    $bundle_fieldset['bundle'] = [
      '#type' => 'select',
      '#title' => $this->t('Bundle'),
      '#empty_option' => $this->t('- None -'),
      '#default_value' => ($selected['bundle']) ?: $this->t('- None -'),
      '#options' => (isset($bundle_options[$selected['entity']])) ? $bundle_options[$selected['entity']] : [],
      '#description' => $this->t('The Bundle of the selected Entity Type.'),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::bundleDropdownCallback',
        'wrapper' => 'fields-fieldset-container',
      ],
    ];
    $bundle_fieldset['choose_bundle'] = [
      '#type' => 'submit',
      '#value' => $this->t('Choose Bundle'),
      '#states' => [
        'visible' => [':input[name="bundle"]' => ['value' => TRUE]],
      ],
    ];

    // Fields Fieldset.
    $bundle_fieldset['fields_fieldset_container'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'fields-fieldset-container'],
    ];
    $bundle_fieldset['fields_fieldset_container']['fields_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Field Selection'),
    ];

    // Fields Fieldset Reference.
    $fields_fieldset =& $bundle_fieldset['fields_fieldset_container']['fields_fieldset'];
    $fields_fieldset['field'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity Field'),
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $selected['field'] ?: $this->t('- None -'),
      '#options' => ($selected['entity'] && $selected['bundle']) ? $entity_bundle_fields[$selected['entity']][$selected['bundle']] : [],
      '#description' => $this->t('The entity field that the identifier will be minted into.'),
      '#required' => TRUE,
    ];
    $form['service_data'] = [
      '#type' => 'select',
      '#title' => $this->t('Service Data Profile'),
      '#empty_option' => $this->t('- None -'),
      '#options' => ($service_data_options) ?: [],
      '#default_value' => ($config->get('service_data')) ?: $this->t('- None -'),
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
      '#options' => ($data_profile_options) ?: [],
      '#default_value' => ($selected['data_profile']) ?: $this->t('- None -'),
      '#description' => $this->t('The Data Profile to be used with this Identifier. (IE. Controls what Data is sent to the Identifier service.)'),
      '#ajax' => [
        'callback' => '::dataprofileDropdownCallback',
        'wrapper' => 'dataprofile-fieldset-container',
      ],
    ];

    if ($selected['data_profile']) {
      $selected_dataprofile_config = $this->configFactory->get($selected['data_profile']);
      $form['dataprofile_fieldset_container']['dataprofile_fieldset'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Data Profile Configuration'),
      ];
      $form['dataprofile_fieldset_container']['dataprofile_fieldset']['dataprofile_entity_type'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Entity Type'),
        '#maxlength' => 255,
        '#default_value' => ($selected_dataprofile_config->get('entity')) ?: '',
        '#description' => $this->t('The Entity Type configured in the selected Data Profile config.'),
        '#disabled' => TRUE,
      ];
      $form['dataprofile_fieldset_container']['dataprofile_fieldset']['dataprofile_bundle_type'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Bundle Type'),
        '#maxlength' => 255,
        '#default_value' => ($selected_dataprofile_config->get('bundle')) ?: '',
        '#description' => $this->t('The Bundle Type configured in the selected Data Profile config.'),
        '#disabled' => TRUE,
      ];
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
      $fields_fieldset['#access'] = FALSE;
      $fields_fieldset['#disabled'] = TRUE;
    }

    if (!$selected['data_profile']) {
      // Change the field title to provide user with some feedback on why the
      // field is disabled.
      $fields_fieldset['#access'] = FALSE;
      $fields_fieldset['#disabled'] = TRUE;
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
   * Helper function to build Entity Lists.
   *
   * @return array
   *   Returns Entity bundles and options.
   */
  public function entityDropdownList() {
    $field_map = $this->entityFieldManager->getFieldMap();

    // Building Entity Bundle List and Options.
    $returns = [];
    foreach (array_keys($field_map) as $entity_key) {
      $returns['entity_bundles'][$entity_key] = $this->entityTypeBundleInfo->getBundleInfo($entity_key);
      $returns['entity_options'][$entity_key] = $entity_key;
    }

    return $returns;
  }

  /**
   * Helper function to build Bundle Lists.
   *
   * @return array
   *   Returns bundle fields and options.
   */
  public function bundleDropdownList($entity_bundles = []) {
    $returns = [];
    foreach ($entity_bundles as $entity => $bundles) {
      foreach ($bundles as $bundle => $bundle_data) {
        $fields = $this->entityFieldManager->getFieldDefinitions($entity, $bundle);
        $returns['entity_bundle_fields'][$entity][$bundle] = array_combine(array_keys($fields), array_keys($fields));
        $returns['bundle_options'][$entity][$bundle] = $bundle_data['label'];
      }
    }

    return $returns;
  }

  /**
   * Helper function to build options lists.
   *
   * @param array $configs
   *   An array of available config entity keys.
   *
   * @return array
   *   An key value list of options.
   */
  protected function listOptionsBuilder(array $configs) {
    $options = [];
    foreach ($configs as $config_id) {
      $config = $this->configFactory->get($config_id);
      $options[$config_id] = $config->get('label');
    }

    return $options;
  }

  /**
   * Filter text input for valid configuration names (including wildcards).
   *
   * @param string|string[] $text
   *   The configuration names, one name per line.
   *
   * @return string[]
   *   The array of configuration names.
   */
  protected function filterConfigNames($text) {
    if (!is_array($text)) {
      $text = explode("\n", $text);
    }

    foreach ($text as &$config_entry) {
      $config_entry = strtolower($config_entry);
    }

    // Filter out illegal characters.
    return array_filter(preg_replace('/[^a-z0-9_\.\-\*]+/', '', $text));
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $identifier = $this->entity;
    $status = $identifier->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('Created the %label Identifier setting.', [
          '%label' => $identifier->label(),
        ]));
        break;

      default:
        $this->messenger()->addStatus($this->t('Saved the %label Identifier setting.', [
          '%label' => $identifier->label(),
        ]));
    }
    $form_state->setRedirectUrl($identifier->toUrl('collection'));
  }

}
