<?php

namespace Drupal\dgi_actions\Plugin\Action;

use Drupal\dgi_actions\Plugin\Action\IdentifierAction;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Psr7\Request;
use Exception;

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
      /// Adding the External URL to the Data Array using the CDL EZID _target key.
      // Also setting _status as reserved. Else identifier cannot be deleted.
      $data = array_merge(['_target' => $this->getExternalURL($entity), '_status' => 'reserved'], $data);
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
  protected function getIdentifier($response, $configs) {
    $contents = $response->getBody()->getContents();
    $responseArray = $this->responseArray($contents);
    if (array_key_exists('success', $responseArray)) {
      $this->logger->info('ARK Identifier Minted: @contents', ['@contents' => $contents]);
      return $configs['credentials']->get('host') . '/id/' . $responseArray['success'];
    }
    else {
      return FALSE;
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
