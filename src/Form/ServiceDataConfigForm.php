<?php

namespace Drupal\dgi_actions\Form;

use Drupal\Core\State\State;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Controls the Service Data Config form.
 */
class ServiceDataConfigForm extends ConfigFormBase {

  /**
   * State.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\State\State $state
   *   The state.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    State $state
  ) {
    parent::__construct($config_factory);
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'dgi_actions.service_data.ark_ezid',
    ];
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
    $config = $this->config('dgi_actions.service_data.ark_ezid');
    $creds = $this->state->get('dgi_actions_ark_ezid');

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#description' => $this->t('Label for the service data.'),
      '#default_value' => $config->get('label'),
    ];
    $form['id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Service Data Id'),
      '#description' => $this->t('Reference id for the service data.'),
      '#default_value' => $config->get('id'),
    ];
    $form['host'] = [
      '#type' => 'url',
      '#title' => $this->t('Host URL'),
      '#description' => $this->t('Please enter the Host URL for the service. e.g. http://www.example.org'),
      '#default_value' => $config->get('data.host'),
    ];
    $form['shoulder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Service Shoulder'),
      '#description' => $this->t('Indicate the shoulder for the service, if one is applicable.'),
      '#default_value' => $config->get('data.shoulder'),
    ];
    $form['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#description' => $this->t('Username for the service.'),
      '#default_value' => ($creds['username']) ?: '',
    ];
    $form['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#description' => $this->t('Password for the service'),
      '#default_value' => '',
      '#attributes' => ['value' => ($creds['password']) ?: ''],
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

    $creds = $this->state->get('dgi_actions_ark_ezid');
    $creds['username'] = $form_state->getValue('username');
    $creds['password'] = $form_state->getValue('password');
    $this->state->set('dgi_actions_ark_ezid', $creds);

    $config = $this->config('dgi_actions.service_data.ark_ezid');
    $config->set('label', $form_state->getValue('label'));
    $config->set('id', $form_state->getValue('id'));
    $config->set('data.host', $form_state->getValue('host'));
    $config->set('data.shoulder', $form_state->getValue('shoulder'));
    $config->save();
  }

}
