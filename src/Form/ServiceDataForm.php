<?php

namespace Drupal\dgi_actions\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\State\StateInterface;
use Drupal\dgi_actions\Plugin\ServiceDataTypeManager;
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
   * The Service Data Type manager.
   *
   * @var \Drupal\dgi_actions\Plugin\ServiceDataTypeManager
   */
  protected $serviceDataTypeManager;

  /**
   * The service data type plugin.
   *
   * @var \Drupal\dgi_actions\Plugin\ServiceDataTypeInterface
   */
  protected $plugin;

  /**
   * Constructs a new class instance.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The drupal state.
   * @param \Drupal\dgi_actions\Plugin\ServiceDataTypeManager $service_data_type_manager
   *   The Service Data Type plugin manager.
   */
  public function __construct(StateInterface $state, ServiceDataTypeManager $service_data_type_manager) {
    $this->state = $state;
    $this->serviceDataTypeManager = $service_data_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): ServiceDataForm {
    return new static(
      $container->get('state'),
      $container->get('plugin.manager.service_data_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    if ($this->getOperation() === 'edit') {
      $this->plugin = $this->serviceDataTypeManager->createInstance($this->entity->getServiceDataType(), $this->entity->getData());
    }
    // Grab the list of available service data types.
    $definitions = $this->serviceDataTypeManager->getDefinitions();
    $options = [];

    foreach ($definitions as $service => $definition) {
      $options[$service] = $definition['label'];
    }
    $triggering_element = $form_state->getTriggeringElement();

    if (isset($triggering_element['#parents']) && $triggering_element['#parents'] === ['service_data_type']) {
      $this->plugin = !empty($form_state->getValue('service_data_type')) ? $this->serviceDataTypeManager->createInstance($form_state->getValue('service_data_type')) : NULL;
    }

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->label(),
      '#description' => $this->t("Label for the Identifier setting."),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\dgi_actions\Entity\ServiceData::load',
      ],
    ];
    $form['service_data_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Service Data'),
    ];
    $form['service_data_fieldset']['service_data_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Service Data Type'),
      '#options' => $options,
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $this->entity->getServiceDataType(),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::serviceDataTypeSelectionCallback',
        'wrapper' => 'servicedata-fieldset-container',
      ],
    ];
    $form['service_data_fieldset']['container'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'servicedata-fieldset-container'],
    ];
    // @TODO: No AJAX?
    // If a service data type has been selected do things.
    if ($this->plugin) {
      $form['service_data_fieldset']['container']['fieldset'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Configuration'),
      ];
      $form['service_data_fieldset']['container']['fieldset']['data'] = [];
      $subform_state = SubformState::createForSubform($form['service_data_fieldset']['container']['fieldset']['data'], $form, $form_state);
      $form['service_data_fieldset']['container']['fieldset']['data'] = $this->plugin->buildConfigurationForm($form['service_data_fieldset']['container']['fieldset']['data'], $subform_state);
      $form['service_data_fieldset']['container']['fieldset']['data']['#tree'] = TRUE;
    }
    return $form;
  }

  /**
   * Service Data AJAX Callback function.
   */
  public function serviceDataTypeSelectionCallback(array $form, FormStateInterface $form_state) {
    return $form['service_data_fieldset']['container'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Only validate if there's a plugin and the submit is triggering this.
    if ($this->plugin && $form_state->getTriggeringElement()['#parents'] === ['submit']) {
      // Validate the settings of the plugin.
      $this->plugin->validateConfigurationForm($form['service_data_fieldset']['container']['fieldset']['data'], SubformState::createForSubform($form['service_data_fieldset']['container']['fieldset']['data'], $form, $form_state));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $subform_state = SubformState::createForSubform($form['service_data_fieldset']['container']['fieldset']['data'], $form, $form_state);
    $this->plugin->submitConfigurationForm($form['service_data_fieldset']['container']['fieldset']['data'], $subform_state);

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = $this->entity->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('Created the %label service data type settings.', [
          '%label' => $this->entity->label(),
        ]));
        break;

      default:
        $this->messenger()->addStatus($this->t('Saved the %label service data type settings.', [
          '%label' => $this->entity->label(),
        ]));
    }
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
  }

}
