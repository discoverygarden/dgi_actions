<?php

namespace Drupal\dgi_actions\Form;

use Drupal\Core\Entity\EntityForm;
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
class ServiceDataForm extends EntityForm {

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
   * Constructs a new class instance.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The drupal state.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $themeHandler
   *   The theme handler.
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   The drupal core config factory.
   */
  public function __construct(StateInterface $state, ThemeHandlerInterface $themeHandler, ConfigFactory $configFactory) {
    $this->state = $state;
    $this->themeHandler = $themeHandler;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state'),
      $container->get('theme_handler'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $servicedata = self::servicedataLists();
    $servicedata_configs =& $servicedata['servicedata_configs'];
    $servicedata_options =& $servicedata['servicedata_options'];

    /** @var \Drupal\dgi_actions\Entity\IdentifierInterface $config */
    $config = $this->entity;

    $state_key = 'dgi_actions.service_data.' . $config->id();
    if ($this->state->get($state_key)) {
      $state_creds = $this->state->get($state_key);
    }

    if (empty($form_state->getValue('service_data_type'))) {
      if ($config->getServiceDataType()) {
        $selected_servicedatatype = $config->getServiceDataType();
      }
      else {
        $selected_servicedatatype = '';
      }
    }
    else {
      $selected_servicedatatype = $form_state->getValue('service_data_type');
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
        'exists' => '\Drupal\dgi_actions\Entity\ServiceData::load',
      ],
    ];
    $form['servicedata_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Service Data Fieldset'),
    ];
    $form['servicedata_fieldset']['service_data_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Service Data Type'),
      '#empty_option' => $this->t('- None -'),
      '#default_value' => ($selected_servicedatatype) ?: NULL,
      '#options' => $servicedata_options,
      '#description' => $this->t('The entity that the data will be captured.'),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::serviceDataTypeSelectionCallback',
        'wrapper' => 'servicedata-fieldset-container',
      ],
    ];
    $form['servicedata_fieldset']['choose_servicedata'] = [
      '#type' => 'submit',
      '#value' => $this->t('Choose Service Data'),
      '#states' => [
        'visible' => ['body' => ['value' => TRUE]],
      ],
    ];
    $form['servicedata_fieldset']['servicedata_fieldset_container'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'servicedata-fieldset-container'],
    ];
    $form['servicedata_fieldset']['servicedata_fieldset_container']['servicedata_fields_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Service Data Fieldset'),
    ];

    $fieldset =& $form['servicedata_fieldset']['servicedata_fieldset_container']['servicedata_fields_fieldset'];
    if ($selected_servicedatatype) {
      $fields = $servicedata_configs[$selected_servicedatatype]->get('data');
      foreach ($fields as $field) {
        $fieldset[$field['id']] = [
          '#type' => 'textfield',
          '#title' => $field['label'],
          '#maxlength' => 255,
          '#description' => $field['description'],
          '#required' => TRUE,
        ];

        if ($field['id'] == 'password') {
          $password_element =& $fieldset[$field['id']];
          $password_element['#type'] = 'password';

          if (isset($state_creds) && isset($state_creds['password'])) {
            $password_element['#placeholder'] = '********';
            unset($password_element['#required']);
          }
        }
        elseif (isset($state_creds[$field['id']])) {
          $fieldset[$field['id']]['#default_value'] = $state_creds[$field['id']];
        }
        else {
          $fieldset[$field['id']]['#default_value'] = (isset($config->get('data')[$field['id']])) ? $config->get('data')[$field['id']] : '';
        }
      }
    }

    if (!$selected_servicedatatype) {
      $fieldset['#access'] = FALSE;
      $fieldset['#disabled'] = TRUE;
    }

    return $form;
  }

  /**
   * Service Data AJAX Callback function.
   */
  public function serviceDataTypeSelectionCallback(array $form, FormStateInterface $form_state) {
    return $form['servicedata_fieldset']['servicedata_fieldset_container'];
  }

  /**
   * Helper function to build Service Data Lists.
   *
   * @return array
   *   Returns Service Data configs and options lists.
   */
  public function servicedataLists() {
    $list = $this->configFactory->listAll('dgi_actions.service_data_type');
    $returns = [];
    foreach ($list as $config_id) {
      $config = $this->configFactory->get($config_id);
      $returns['servicedata_configs'][$config_id] = $config;
      $returns['servicedata_options'][$config->getName()] = $config->get('label');
    }
    return $returns;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $trigger = (string) $form_state->getTriggeringElement()['#value'];
    if (($trigger) == 'Save') {
      $this->setServiceData($form_state);
    }
    else {
      $form_state->setRebuild();
    }
  }

  /**
   * A helper function to set the Service Data data.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The FormState entity.
   */
  public function setServiceData(FormStateInterface $form_state) {
    $service_data = $this->servicedataLists();
    $config =& $this->entity;

    $fields = $service_data['servicedata_configs'][$config->getServiceDataType()]->get('data');
    $data = [];
    foreach ($fields as $field) {
      $form_key = $field['id'];
      if (!empty($form_state->getValue($form_key))) {
        if ($form_key == 'username' || $form_key == 'password') {
          $creds[$form_key] = $form_state->getValue($form_key);
        }
        else {
          $data[$form_key] = $form_state->getValue($form_key);
        }
      }
    }

    $state_key = 'dgi_actions.service_data.' . $config->id();
    if ($form_state->getValue('password')) {
      $this->state->set($state_key, $creds);
    }
    $data['state_key'] = $state_key;

    // Clearing the data in case there was a different
    // Data Profile with data set previously.
    $config->setData([]);
    $config->setData($data);
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
    $service_data = $this->entity;
    $status = $service_data->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('Created the %label Identifier setting.', [
          '%label' => $service_data->label(),
        ]));
        break;

      default:
        $this->messenger()->addStatus($this->t('Saved the %label Identifier setting.', [
          '%label' => $service_data->label(),
        ]));
    }
    $form_state->setRedirectUrl($service_data->toUrl('collection'));
  }

}
