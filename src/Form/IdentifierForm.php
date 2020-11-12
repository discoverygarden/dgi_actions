<?php

namespace Drupal\dgi_actions\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Config\ConfigFactory;
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
   * Constructs a new class instance.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The drupal state.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $themeHandler
   *   The theme handler.
   */
  public function __construct(StateInterface $state, ThemeHandlerInterface $themeHandler, ConfigFactory $configFactory, EntityFieldManager $entityFieldManager) {
    $this->state = $state;
    $this->themeHandler = $themeHandler;
    $this->configFactory = $configFactory;
    $this->entityFieldManager = $entityFieldManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state'),
      $container->get('theme_handler'),
      $container->get('config.factory'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $field_map = $this->entityFieldManager->getfieldMap();
    $config = $this->entity;

    $options_map = [];
    foreach (array_keys($field_map) as $entity_type) {
      $options_map = array_merge($options_map, $field_map[$entity_type]);
    }

    $pruned_options = [];
    foreach ($options_map as $key => $value) {
      if (strpos($key, 'field_') === 0) {
        $pruned_options[$key] = $key;
      }
    }

    $data_profile_list = $this->configFactory->listAll('dgi_actions.data_profile.');
    $service_data_list = $this->configFactory->listAll('dgi_actions.service_data.');

    $data_profile_options = self::listOptionsBuilder($data_profile_list);
    $service_data_options = self::listOptionsBuilder($service_data_list);

    /** @var \Drupal\dgi_actions\Entity\IdentifierInterface $config */
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
    $form['field'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity Field'),
      '#empty_option' => $this->t('- None -'),
      '#default_value' => ($config->get('field')) ?: $this->t('- None -'),
      '#options' => $pruned_options,
      '#description' => $this->t('The entity field that the identifier will be minted into.'),
      '#required' => TRUE,
    ];
    $form['service_data'] = [
      '#type' => 'select',
      '#title' => $this->t('Service Data Profile'),
      '#empty_option' => $this->t('- None -'),
      '#options' => ($service_data_options) ?: [],
      '#default_value' => ($config->get('service_data')) ?: $this->t('- None -'),
      '#description' => $this->t('The Service Data service to be used with this Identifier. (IE. Controls what Service to perform CRUD operations with.'),
    ];
    $form['data_profile'] = [
      '#type' => 'select',
      '#title' => $this->t('Data Profile'),
      '#empty_option' => $this->t('- None -'),
      '#options' => ($data_profile_options) ?: [],
      '#default_value' => ($config->get('data_profile')) ?: $this->t('- None -'),
      '#description' => $this->t('The Data Profile to be used with this Identifier. (IE. Controls what Data is sent to the Identifier service.)'),
    ];

    return $form;
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
