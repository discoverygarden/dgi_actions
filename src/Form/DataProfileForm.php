<?php

namespace Drupal\dgi_actions\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ConfigSplitEntityForm.
 *
 * @package Drupal\dgi_actions\Form
 */
class DataProfileForm extends EntityForm {

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
   * Constructs a new class instance.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The drupal state.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $themeHandler
   *   The theme handler.
   */
  public function __construct(StateInterface $state, ThemeHandlerInterface $themeHandler) {
    $this->state = $state;
    $this->themeHandler = $themeHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state'),
      $container->get('theme_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Data Profile Types - Start
    $config_factory = \Drupal::service('config.factory');
    $list = $config_factory->listAll('dgi_actions.data_profile_type');
    $data_profile_configs = [];
    foreach($list as $config) {
      $data_profile_configs[$config] = $config_factory->get($config);
    }

    $data_profile_options = [];
    foreach($data_profile_configs as $config) {
      $data_profile_options[$config->getName()] = $config->get('label');
    }

    $entity_array = static::entityDropdownList();
    $entity_bundles = $entity_array['entity_bundles'];
    $entity_options = $entity_array['entity_options'];

    if (empty($form_state->getValue('entity'))) {
      $selected_entity = '';
    }
    else {
      $selected_entity = $form_state->getValue('entity');
    }

    $entity_bundle_array = static::bundleDropdownList($entity_bundles);
    $entity_bundle_field = $entity_bundle_array['entity_bundle_fields'];
    $bundle_options = $entity_bundle_array['bundle_options'];

    if (empty($form_state->getValue('bundle'))) {
      $selected_bundle = '';
    }
    else {
      $selected_bundle = $form_state->getValue('bundle');
    }

    /** @var \Drupal\dgi_actions\Entity\DataProfileInterface $config */
    $config = $this->entity;
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
        'exists' => '\Drupal\dgi_actions\Entity\DataProfile::load',
      ],
    ];
    $form['entity_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Entity Fieldset'),
    ];
    $form['entity_fieldset']['entity'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity'),
      '#empty_option' => $this->t('- None -'),
      '#default_value' => ($config->get('entity')) ?: $this->t('- None -'),
      '#options' => $entity_options,
      '#description' => $this->t('The entity that the data will be captured.'),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::entityDropdownCallback',
        'wrapper' => 'bundle-fieldset-container',
      ],
    ];
    $form['entity_fieldset']['choose_entity'] = [
      '#type' => 'submit',
      '#value' => $this->t('Choose'),
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
      '#title' => $this->t('Choose a Bundle'),
    ];
    $form['bundle_fieldset_container']['bundle_fieldset']['bundle'] = [
      '#type' => 'select',
      '#title' => $this->t('Bundle'),
      '#empty_option' => $this->t('- None -'),
      '#default_value' => ($config->get('bundle')) ?: $this->t('- None -'),
      '#options' => (isset($bundle_options[$selected_entity])) ? $bundle_options[$selected_entity] : [],
      '#description' => $this->t('The Bundle of the selected Entity Type.'),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::bundleDropdownCallback',
        'wrapper' => 'dataprofile-fieldset-container',
      ],
    ];
    $form['bundle_fieldset_container']['bundle_fieldset']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#states' => [
        'visible' => ['body' => ['value' => TRUE]],
      ],
    ];
    $form['dataprofile_fieldset_container'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'dataprofile-fieldset-container'],
    ];
    $form['dataprofile_fieldset_container']['dataprofile_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Data Profile Fieldset'),
    ];
    $form['dataprofile_fieldset_container']['dataprofile_fieldset']['dataprofile'] = [
      '#type' => 'select',
      '#title' => $this->t('Data Profile Type'),
      '#empty_option' => $this->t('- None -'),
      '#default_value' => ($config->get('bundle')) ?: $this->t('- None -'),
      '#options' => $data_profile_options,
      '#description' => $this->t('The Data Profile type to be used for the Data Profile Config'),
      '#required' => TRUE,
    ];
    $form['dataprofile_fieldset_container']['dataprofile_fieldset']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#states' => [
        'visible' => ['body' => ['value' => TRUE]],
      ],
    ];

    if (!$selected_entity) {
      // Change the field title to provide user with some feedback on why the
      // field is disabled.
      $form['bundle_fieldset_container']['bundle_fieldset']['bundle']['#title'] = $this->t('You must choose an Entity first.');
      $form['bundle_fieldset_container']['bundle_fieldset']['bundle']['#disabled'] = TRUE;
      $form['bundle_fieldset_container']['bundle_fieldset']['submit']['#disabled'] = TRUE;
    }

    if (!$selected_bundle) {
      // Change the field title to provide user with some feedback on why the
      // field is disabled.
      $form['dataprofile_fieldset_container']['dataprofile_fieldset']['dataprofile']['#title'] = $this->t('You must choose a Bundle first.');
      $form['dataprofile_fieldset_container']['dataprofile_fieldset']['dataprofile']['#disabled'] = TRUE;
      $form['dataprofile_fieldset_container']['dataprofile_fieldset']['submit']['#disabled'] = TRUE;
    }

    return $form;
  }

  public function entityDropdownCallback(array $form, FormStateInterface $form_state) {
    return $form['bundle_fieldset_container'];
  }

  public function bundleDropdownCallback(array $form, FormStateInterface $form_state) {
    return $form['dataprofile_fieldset_container'];
  }

  public static function entityDropdownList() {
    $entityFieldManager = \Drupal::service('entity_field.manager');
    $field_map = $entityFieldManager->getFieldMap();
    $bundle_info = \Drupal::service('entity_type.bundle.info');

    // Building Entity Bundle List and Options.
    $entity_bundles = [];
    $entity_options = [];
    foreach (array_keys($field_map) as $entity_key) {
      $entity_bundles[$entity_key] = $bundle_info->getBundleInfo($entity_key);
      $entity_options[$entity_key] = $entity_key;
    }
    $returns['entity_bundles'] = $entity_bundles;
    $returns['entity_options'] = $entity_options;

    return $returns;
  }

  public static function bundleDropdownList($entity_bundles = []) {
    $entityFieldManager = \Drupal::service('entity_field.manager');
    $entity_bundle_fields = [];
    $bundle_options = [];
    foreach ($entity_bundles as $entity => $bundles) {
      foreach ($bundles as $bundle => $bundle_data) {
        $entity_bundle_fields[$entity][$bundle] = $entityFieldManager->getFieldDefinitions($entity, $bundle);
        $bundle_options[$entity][$bundle] = $bundle_data['label'];
      }
    }

    $returns['entity_bundle_fields'] = $entity_bundle_fields;
    $returns['bundle_options'] = $bundle_options;

    return $returns;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $trigger = (string) $form_state->getTriggeringElement()['#value'];
    if (($trigger) == 'Submit') {
      // Process submitted form data.
      $this->messenger->addStatus($this->t('Your values have been submitted. Entity: @entity, Bundle: @bundle', [
        '@entity' => $form_state->getValue('entity'),
        '@bundle' => $form_state->getValue('bundle'),
      ]));
    }
    else {
      $form_state->setRebuild();
    }
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
