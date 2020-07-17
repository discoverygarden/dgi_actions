<?php

namespace Drupal\dgi_actions\Plugin\Action;

use Drupal\dgi_actions\Plugin\Action\AbstractIdentifier;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Creates an ARK Record on CDL EZID.
 *
 * @Action(
 *   id = "ark_identifier_mint_record",
 *   label = @Translation("ARK Identifier"),
 *   type = "entity"
 * )
 */
class ArkIdentifier extends AbstractIdentifier {

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = $object->access('read', $account, $return_as_object);
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function mint() {
    // Gather the applicable URL, login information, etc from configs
    // Make a request via $client->request() call.
    // Verify the request returns properly.
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    // Hit the ARK html endpoint using configured values for the applicable content/bundle type
    // Verify the returned response is whats expected
    // Write the ark identifier to the custom field
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'credentials' => '',
      'namespace_shoulder' => '',
      'mapped_field' => '',
      'content_type' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Need to capture all available content types, and provide them as a dropdown or a multi-select?
      // A Single policy may be applicable to 1 to N ContentTypes, but certain ones may need special configuration.
    dsm('Does DSM exist in this?');
    $form['content_type'] = [
      '#type' => 'textfield',
      '#title' => t('Content Type'),
      '#default_value' => $this->configuration['content_type'],
      '#description' => t('Content Types affected by this configuration.'),
    ];
    $form['credentials'] = [
      '#type' => 'textfield',
      '#title' => t('Credentials'),
      '#default_value' => $this->configuration['credentials'],
      '#description' => t('Credentials for the ARK.'),
    ];
    $form['namespace_shoulder'] = [
      '#type' => 'textfield',
      '#title' => t('Namespace/Shoulder'),
      '#default_value' => $this->configuration['namespace_shoulder'],
      '#description' => t('Namespace/Shoulder for the ARK Record.'),
    ];
    $form['mapped_field'] = [
      '#type' => 'textfield',
      '#title' => t('ARK Field Mapping'),
      '#default_value' => $this->configuration['mapped_field'],
      '#description' => t('Field Mapping for the ARK identifier.'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration = $form_state->getValues();
  }

}
