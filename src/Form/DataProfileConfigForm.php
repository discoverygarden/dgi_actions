<?php

namespace Drupal\dgi_actions\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Controls the Data Profile Config form.
 */
class DataProfileConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'dgi_actions.data_profile.erc',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dgi_actions_data_profile_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('dgi_actions.data_profile.erc');

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#description' => $this->t('Label for the service data.'),
      '#default_value' => $config->get('label'),
    ];
    $form['id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Data Profile Id'),
      '#description' => $this->t('Reference id for the data profile.'),
      '#default_value' => $config->get('id'),
    ];

    if ($config->get('data')) {
      $form = array_merge($form, $this->generateDynamicForm($config->get('data')));
    }
    else {
      $keys = ['erc.who', 'erc.when', 'erc.who'];
      $default_erc_data_profile = [];
      foreach ($keys as $key) {
        $default_erc_data_profile[] = ['key' => $key, 'source_field' => ''];
      }

      $form = array_merge($form, $this->generateDynamicForm($default_erc_data_profile));
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * Generate a dynamic form.
   */
  protected function generateDynamicForm($configData) {
    $form = [];
    foreach ($configData as $data) {
      $form[$data['key']] = [
        '#type' => 'textfield',
        '#title' => $this->t('@key Field', ['@key' => $data['key']]),
        '#description' => $this->t('The field that the @key data will be captured from.', ['@key' => $data['key']]),
        '#default_value' => $data['source_field'],
      ];
    }

    return $form;
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

    $config = $this->config('dgi_actions.data_profile.erc');
    $config->set('label', $form_state->getValue('label'));
    $config->set('id', $form_state->getValue('id'));
    $config->set('data.0.key', 'erc.who');
    $config->set('data.0.source_field', $form_state->getValue('erc.who'));
    $config->set('data.1.key', 'erc.what');
    $config->set('data.1.source_field', $form_state->getValue('erc.what'));
    $config->set('data.2.key', 'erc.when');
    $config->set('data.2.source_field', $form_state->getValue('erc.when'));
    $config->save();
  }

}
