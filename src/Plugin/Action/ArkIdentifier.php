<?php

namespace Drupal\dgi_actions\Plugin\ArkIdentifier;

use Drupal\dgi_actions\Plugin\AbstractIdentifier;

/**
 * Creates an ARK Record on CDL EZID.
 *
 * @Action(
 *   id = "ark_identifier_mint_record",
 *   label = @Translation("ARK Identifier"),
 *   type = "ark identifier"
 * )
 */
class ArkIdentifier extends AbstractIdentifier {

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
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
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
