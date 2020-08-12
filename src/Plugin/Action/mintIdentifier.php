<?php

namespace Drupal\dgi_actions\Plugin\Action;

use Drupal\dgi_actions\Plugin\Action\IdentifierAction;
use Drupal\Core\Form\FormStateInterface;

/**
 * Creates an ARK Record on CDL EZID.
 *
 * @Action(
 *   id = "mint_identifier_record",
 *   label = @Translation("Mint Identifier"),
 *   type = "entity"
 * )
 */
class MintIdentifier extends IdentifierAction {

  /**
   * {@inheritdoc}
   */
  public function mint() {
    $body = $this->buildMetadataString();

    try {
      $response = $this->client->request('POST', $this->configuration['host'] . '/shoulder/' . $this->confinguration['namespace_shoulder'], [
        'auth' => [$this->configuration['username'], $this->configuration['password']],
        'headers' => [
          'Content-Type' => 'text/plain; charset=UTF-8',
          'Content-Length' => strlen($body)
        ],
        'body' => $body
      ]);
    }
    catch (Exception $e) {
      $this->logger->warning('Issue occurred while minting an identifier: @response', [
        '@response' => $response,
      ]);
      return $e;
    }

    return $this->responseArray($response->getBody()->getContents());
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = null) {
    // run $this->mint();
    // Verify the returned response is success
    // Write the minted ARK Identifier to the applicable field
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'content_type' => 'content',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['content_type'] = [
      '#type' => 'textfield', //select
      '#title' => t('Content Type'),
      '#default_value' => $this->configuration['content_type'],
      //'#options' => $contentTypes,
      '#description' => t('Content Type and bundle affected by this configuration.'),
    ];
    return $form;
  }
}
