<?php

namespace Drupal\dgi_actions\Plugin\Action;

/**
 * Mints an ARK Identifier Record on CDL EZID.
 *
 * @Action(
 *   id = "mint_ark_identifier_record",
 *   label = @Translation("Mint Ark Identifier"),
 *   type = "entity"
 * )
 */
class MintArkIdentifier extends MintIdentifier {

  /**
   * Builds the Request Body.
   *
   * Constructs the Metadata into a colon separated value
   * string for the CDL EZID service.
   *
   * @param mixed $data
   *   The Entity data that's to be built for the service.
   *
   * @return string
   *   Returns the stringified version of the key-value
   *   pairs else returns an empty string if $data is empty or null.
   */
  protected function buildRequestBody($data = NULL) {
    if (!$data) {
      $this->logger->warning('buildRequestBody - Data is missing or malformed.');
      $data = [];
    }

    // Adding External URL to the Data Array under the EZID _target key.
    // Also setting _status as reserved. Else identifier cannot be deleted.
    $data = array_merge(['_target' => $this->getExternalURL(), '_status' => 'reserved'], $data);
    $outputString = "";
    foreach ($data as $key => $val) {
      $outputString .= $key . ": " . $val . "\r\n";
    }

    return $outputString;
  }

  /**
   * Formats the CDL EZID response as a key-value pair array.
   *
   * CDL EZID sends back a response body as a single string,
   * with response values separated by colons, this method
   * separates that into a key-value pair array.
   */
  protected function responseArray($contents) {
    $responseArray = preg_split('/\r\n|\r|\n/', trim($contents));
    $assocArray = [];
    foreach ($responseArray as $res_line) {
      $splitRes = explode(':', $res_line, 2);
      $assocArray[trim($splitRes[0])] = trim($splitRes[1]);
    }

    return $assocArray;
  }

  /**
   * {@inheritdoc}
   */
  protected function getIdentifierFromResponse($response) {
    $contents = $response->getBody()->getContents();
    $responseArray = $this->responseArray($contents);
    if (array_key_exists('success', $responseArray)) {
      $this->logger->info('ARK Identifier Minted: @contents', ['@contents' => $contents]);
      return $this->configs['service_data']->get('host') . '/id/' . $responseArray['success'];
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getRequestType() {
    return 'POST';
  }

  /**
   * {@inheritdoc}
   */
  protected function getUri() {
    $uri = $this->configs['service_data']->get('host') . '/shoulder/' . $this->configs['service_data']->get('shoulder');

    return $uri;
  }

  /**
   * {@inheritdoc}
   */
  protected function getRequestParams() {
    $fieldData = $this->getFieldData();
    $requestBody = $this->requestBody($fieldData);
    $requestParams = [
      'auth' => [$this->configs['service_data']->get('username'), $this->configs['service_data']->get('password')],
      'headers' => [
        'Content-Type' => 'text/plain; charset=UTF-8',
        'Content-Length' => strlen($requestBody),
      ],
      'body' => $requestBody,
    ];

    return $requestParams;
  }

  /**
   * {@inheritdoc}
   */
  protected function handleResponse($response) {
    $identifier = $this->getIdentifierFromResponse($response);
    $this->setIdentifierField($identifier);
  }

}
