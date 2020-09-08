<?php

namespace Drupal\dgi_actions\Utility;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Text parser for CDL EZID Requests and Responses.
 */
class EzidTextParser {

  /**
   * Logger.
   *
   * @var Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructor.
   *
   * @param Psr\Log\LoggerInterface $logger
   *   Logger.
   */
  public function __construct(
    LoggerInterface $logger
  ) {
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
       $container->get('logger.channel.dgi_actions')
    );
  }

  /**
   * Parses EZID Response into a key-value array.
   *
   * @param string $response
   *   Response body from EZID.
   *
   * @return array
   *   Response organized into a key-value array.
   */
  public function parseEzidResponse(string $response) {
    $responseArray = preg_split('/\r\n|\r|\n/', trim($response));
    $assocArray = [];
    foreach ($responseArray as $res_line) {
      $splitRes = explode(':', $res_line, 2);
      $assocArray[trim($splitRes[0])] = trim($splitRes[1]);
    }

    return $assocArray;
  }

  /**
   * Builds the request content body for the EZID service.
   *
   * Build the request content body from a key-value supplied
   * array of values.
   *
   * @param array $data
   *   The key-value array of data to be formated.
   *
   * @return string
   *   The quest content body.
   */
  public function buildEzidRequestBody(array $data) {
    $output = "";
    foreach ($data as $key => $val) {
      $output .= $key . ": " . $val . "\r\n";
    }

    return $output;
  }

}
