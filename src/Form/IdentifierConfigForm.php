<?php

namespace Drupal\dgi_actions\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\dgi_actions\Utility\IdentifierUtils;

/**
 * Class IslandoraIIIFConfigForm.
 */
class IdentifierConfigForm extends ConfigFormBase {

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
   * @param \Drupal\dgi_actions\Utility\IdentifierUtils $utils
   *   Identifier utils.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    IdentifierUtils $utils
  ) {
    parent::__construct($config_factory);
    $this->utils = $utils;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('dgi_actions.utils')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    $editable_configs = $this->configFactory->listAll('dgi_actions.service_data');

    return $editable_configs;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dgi_actions_identifier_service_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $editable_configs = $this->getEditableConfigNames();
    dpm($editable_configs);
    $identifier_options = $this->utils->getIdentifiers();
    $form['identifier_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Identifier Type'),
      '#empty_option' => $this->t('- None -'),
      '#default_value' => (reset($identifier_options)) ? reset($identifier_options) : '',
      '#options' => $identifier_options,
      '#description' => $this->t('The persistent identifier to configure.'),
    ];
    // get the currently selected identifier
      // get the identifier config
      // get the service id from the config
      // get the service config based on that id 

    //$config = $this->config('dgi_actions.service_data.ark_ezid');
    $form['host'] = [
      '#type' => 'url',
      '#title' => $this->t('Host URL'),
      '#description' => $this->t('Please enter the Host URL for the service. e.g. http://www.example.org'),
      '#default_value' => '',
/*
      '#states' => [
        'disabled' => [
          ':input[name="identifier_type"]' => ['checked' => FALSE],
        ],
        'visible' => [
          ':input[name="identifier_type"]' => ['checked' => TRUE],
        ],
      ],*/
    ];
    $form['shoulder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Service Shoulder'),
      '#description' => $this->t('Indicate the shoulder for the service, if one is applicable.'),
      '#default_value' => /*$config->get('shoulder')*/ '',
    ];
    $form['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#description' => $this->t('Username for the service.'),
      '#default_value' => '', // pull this out of the state instead
    ];
    $form['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#description' => $this->t('Password for the service'),
      '#default_value' => '', // pull this out of the state as well
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('dgi_actions.service_data.*')
      ->set('host', $form_state->getValue('host'))
      ->save();
  }

}
