<?php

namespace Drupal\dgi_actions_ark_identifier\Plugin\Action;

use Drupal\dgi_actions\Plugin\Action\MintIdentifier;
use Drupal\dgi_actions_ezid\Utility\EzidTrait;
use GuzzleHttp\Psr7\Response;

/**
 * Mints an ARK Identifier Record on CDL EZID.
 *
 * @Action(
 *   id = "dgi_actions_mint_ark_identifier",
 *   label = @Translation("Mint ARK EZID Identifier"),
 *   type = "entity"
 * )
 */
class MintArkIdentifier extends MintIdentifier {

  use EzidTrait;

  /**
   * Builds the Request Body.
   *
   * Constructs the Metadata into a colon separated value
   * string for the CDL EZID service.
   *
   * @param array $data
   *   The Entity data that's to be built for the service.
   *
   * @return string
   *   Returns the stringified version of the key-value
   *   pairs else returns an empty string if $data is empty or null.
   */
  protected function buildRequestBody(array $data): string {
    // Setting custom values for the identifiers internal metadata.
    // Adding External URL to the Data Array under the EZID _target key.
    // Also setting _status as reserved. Else identifier cannot be deleted.
    // For more info: https://ezid.cdlib.org/doc/apidoc.html#internal-metadata.
    $data = array_merge(
      [
        '_target' => $this->getExternalUrl(),
        '_status' => 'reserved',
      ], $data
    );
    return $this->buildEzidRequestBody($data);
  }

  /**
   * {@inheritdoc}
   */
  protected function getIdentifierFromResponse(Response $response): string {
    $contents = $response->getBody()->getContents();
    $response = $this->parseEzidResponse($contents);
    if (array_key_exists('success', $response)) {
      $this->logger->info('ARK Identifier Minted: @contents', ['@contents' => $contents]);
      return "{$this->getIdentifier()->getServiceData()->getData()['host']}/id/{$response['success']}";
    }

    $this->logger->error('There was an issue minting the ARK Identifier: @contents', ['@contents' => $contents]);
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function getRequestType(): string {
    return 'POST';
  }

  /**
   * {@inheritdoc}
   */
  protected function getUri(): string {
    return "{$this->getIdentifier()->getServiceData()->getData()['host']}/shoulder/{$this->getIdentifier()->getServiceData()->getData()['namespace']}";
  }

  /**
   * {@inheritdoc}
   */
  protected function getRequestParams(): array {
    $fieldData = $this->getFieldData();
    $body = $this->buildRequestBody($fieldData);

    return [
      'auth' => $this->getAuthorizationParams(),
      'headers' => [
        'Content-Type' => 'text/plain; charset=UTF-8',
        'Content-Length' => strlen($body),
      ],
      'body' => $body,
    ];
  }

}
