<?php

namespace Drupal\dgi_actions\Plugin\Action;

use Drupal\dgi_actions\Plugin\Action\IdentifierAction;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Psr7\Request;
use Exception;

/**
 * Creates an ARK Record on CDL EZID.
 *
 * @Action(
 *   id = "mint_ark_identifier_record",
 *   label = @Translation("Mint Ark Identifier"),
 *   type = "entity"
 * )
 */
class MintArkIdentifier extends MintIdentifier {

  /**
   * {@inheritdoc}
   *
   * Constructs the Metadata into a colon separated value
   * string for the CDL EZID service.
   *
   * @return String $outputString | ""
   *  Returns the stringified version of the key-value
   *  pairs else returns an empty string if $data is empty or null.
   */
  protected function buildRequestBody($entity, $data = null, $configs) {
    if ($data) {
      // Adding the External URL to the Data Array using the
      // CDL EZID _target key.
      $data = array_merge(['_target' => $this->getExternalURL($entity)], $data);
      $outputString = "";
      foreach($data as $key => $val) {
        $outputString .= $key . ": " . $val . "\r\n";
      }

      return $outputString;
    }
    else {
      $this->logger->warning('buildRequestBody - Data is missing or malformed.');

      return "";
    }
  }

  /**
   * Returns the mint request response formatted as a key value pair array.
   *
   * CDL EZID sends back a response as a single string,
   * with response values separated by colons.
   * This function is to separate that out into an associative array
   * breaking the response on colons.
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
  protected function getIdentifier($response, $configs) {
    $responseArray = $this->responseArray($response->getBody()->getContents());
    if (array_key_exists('success', $responseArray)) {
      return $configs['credentials']->get('host') . '/id/' . $responseArray['success'];
    }
    else {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function setIdentifierField($entity, $identifier, $configs) {
    if ($identifier) {
      parent::setIdentifierField($entity, $identifier, $configs);
    }
    else {
      throw new Exception('Identifier was not successfully set.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildRequest($configs) {
    $request = new Request('POST', $configs['credentials']->get('host') . '/shoulder/' . $configs['credentials']->get('shoulder'));

    return $request;
  }

  /**
   * {@inheritdoc}
   */
  public function sendRequest($request, $requestBody, $configs) {
    $response = $this->client->send($request, [
      'auth' => [$configs['credentials']->get('username'), $configs['credentials']->get('password')],
      'headers' => [
        'Content-Type' => 'text/plain; charset=UTF-8',
        'Content-Length' => strlen($requestBody)
      ],
      'body' => $requestBody
    ]);

    return $response;
  }

}
