<?php

namespace Drupal\commerce_quickbooks_enterprise\SoapBundle\Services;

class SoapService implements SoapServiceInterface {

  private $serverVersion = '1';

  public function serverVersion(\stdClass $request) {
    // TODO: Implement serverVersion() method.
  }

  public function clientVersion(\stdClass $request) {
    // TODO: Implement clientVersion() method.
  }

  public function authenticate(\stdClass $request) {
    // TODO: Implement authenticate() method.
  }

  public function sendRequestXML(\stdClass $request) {
    // TODO: Implement sendRequestXML() method.
  }

  public function receiveResponseXML(\stdClass $request) {
    // TODO: Implement receiveResponseXML() method.
  }

  public function getLastError(\stdClass $request) {
    // TODO: Implement getLastError() method.
  }

  public function closeConnection(\stdClass $request) {
    // TODO: Implement closeConnection() method.
  }
}