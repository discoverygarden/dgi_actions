<?php

namespace Drupal\dgi_actions\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Identifier Config Form.
 *
 * Contains the form for configuring the Identifier.
 */
class IdentifierConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'dgi_actions.identifier.ark',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dgi_actions_identifier_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('dgi_actions.identifier.ark');

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#description' => $this->t('Label for the service data.'),
      '#default_value' => $config->get('label'),
    ];
    $form['id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('ID'),
      '#description' => $this->t('The ID of the identifier.'),
      '#default_value' => $config->get('id'),
    ];
    $form['field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Field'),
      '#description' => $this->t('The field the identifier is stored.'),
      '#default_value' => $config->get('field'),
    ];

    $form['service_data'] = [
      '#type' => 'select',
      '#title' => $this->t('Service Data Config'),
      '#empty_option' => $this->t('- None -'),
      '#options' => $this->buildSelectOptions($this->configFactory->listAll('dgi_actions.service_data')),
      '#default_value' => ($config->get('service_data.id')) ? 'dgi_actions.service_data.' . $config->get('service_data.id') : $this->t('- None -'),
      '#description' => $this->t('The service data to be used with the identifier.'),
    ];
    $form['data_profile'] = [
      '#type' => 'select',
      '#title' => $this->t('Data Profile Config'),
      '#empty_option' => $this->t('- None -'),
      '#options' => $this->buildSelectOptions($this->configFactory->listAll('dgi_actions.data_profile')),
      '#default_value' => ($config->get('data_profile.id')) ? 'dgi_actions.data_profile.' . $config->get('data_profile.id') : $this->t('- None -'),
      '#description' => $this->t('The data profile to be used with the identifier.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Populates the config select options.
   *
   * Populates the select options with available configs
   * organized by keys and label.
   */
  protected function buildSelectOptions($configsList) {
    $configs_with_labels = [];
    foreach ($configsList as $config) {
      $configs_with_labels[$config] = $this->configFactory->get($config)->get('label');
    }

    return $configs_with_labels;
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

    $config = $this->config('dgi_actions.identifier.ark');
    $config->set('label', $form_state->getValue('label'));
    $config->set('id', $form_state->getValue('id'));
    $config->set('field', $form_state->getValue('field'));
    $config->set('service_data.id', $this->configFactory->get($form_state->getValue('service_data'))->get('id'));
    $config->set('data_profile.id', $this->configFactory->get($form_state->getValue('data_profile'))->get('id'));
    $config->save();
  }

}
