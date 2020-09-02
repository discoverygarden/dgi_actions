<?php

namespace Drupal\dgi_actions\Plugin\Action;

use GuzzleHttp\Psr7\Request;

/**
 * Deletes an ARK Identifier Record on CDL EZID..
 *
 * @Action(
 *   id = "delete_ark_identifier_record",
 *   label = @Translation("Delete ARK Identifier"),
 *   type = "entity"
 * )
 */
class DeleteArkIdentifier extends DeleteIdentifier {

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
  public function buildRequest($identifier) {
    try {
      $request = new Request('DELETE', $identifier);

      return $request;
    }
    catch (RequestException $re) {
      throw $re;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function sendRequest($request) {
    try {
      $response = $this->client->send($request, [
        'auth' => [$this->configs['credentials']->get('username'), $this->configs['credentials']->get('password')],
      ]);

      $bodyContents = $response->getBody()->getContents();
      $filteredResponse = $this->responseArray($bodyContents);

      // If not success, Guzzle will throw a BadResponseException.
      if (array_key_exists('success', $filteredResponse)) {
        $this->logger->info('ARK Identifier Deleted: @contents', ['@contents' => $bodyContents]);
      }
    }
    catch (BadResponseException $bre) {
      throw $bre;
    }
  }

}
