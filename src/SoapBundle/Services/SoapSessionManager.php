<?php

namespace Drupal\commerce_quickbooks_enterprise\SoapBundle\Services;

use Drupal\Core\Database\Connection;

class SoapSessionManager {

  /**
   * The service providing database connection functionality.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $databaseService;

  /**
   * The table name storing session information.
   *
   * @var string
   */
  protected $storageTable = 'commerce_quickbooks_enterprise_soap_session';

  /**
   * The UUID given by a SOAP client.
   *
   * @var string
   */
  protected $uuid;

  /**
   * The UID of the connected User.
   *
   * @var int
   */
  protected $uid;

  /**
   * Whether or not the current session is valid.
   *
   * @var bool
   */
  protected $isValid = FALSE;

  /**
   * A list of allowed service calls after the previous call.
   *
   * The key is the previous call made to the server, with the value being an
   * array of calls the client is being expected from the client.  If we receive
   * an unexpected call it's probably because the connection went down in the
   * middle.  In this case, we either return empty responses, or a negative
   * number during 'receiveResponseXML'.
   *
   * @var array
   */
  protected $nextStep = [
    'serverVersion' => ['clientVersion'],
    'clientVersion' => ['authenticate'],
    'authenticate' => ['sendRequestXML', 'closeConnection'],
    'sendRequestXML' => ['getLastError', 'receiveResponseXML'],
    'receiveResponseXML' => ['getLastError', 'sendRequestXML', 'closeConnection'],
    'getLastError' => ['closeConnection', 'sendRequestXML'],
    'closeConnection' => [],
  ];

  /**
   * SoapSessionManager constructor.
   *
   * @param \Drupal\Core\Database\Connection $databaseService
   */
  public function __construct(Connection $databaseService) {
    $this->databaseService = $databaseService;
  }

  /**
   * Stores a new session into the database.
   *
   * @param $uuid
   *   The GUID given to a client that will act as a validation token.
   * @param $uid
   *   The User ID that corresponds to the User the client logs in with.
   */
  public function startSession($uuid, $uid) {
    $this->uuid = $uuid;
    $this->uid = $uid;

    $uuid = md5($uuid);

    // Naively delete an old session that belongs to this user
    $query = $this->databaseService->delete($this->storageTable);
    $query->condition('uid', $uid);
    $query->execute();

    // Start a new session.
    $query = $this->databaseService
      ->insert($this->storageTable)
      ->fields(['uuid', 'uid']);
    $query->values([$uuid, $uid]);
    $query->execute();
  }

  /**
   * Validates the current session and request.
   *
   * UUID and/or UID should be set before calling this function.  An effort will
   * be made to set one or the other if either is missing, but will return
   * invalid if neither are supplied.
   *
   * @param string $request
   *   The current call being made to the server. ex. sendRequestXML
   *
   * @return boolean
   *   TRUE if the session and request are valid, FALSE otherwise.
   */
  public function validateSession($request) {
    // We can't validate if we have no information to query with.
    if (empty($this->uuid) && empty($this->uid)) {
      return FALSE;
    }

    $uuid = md5($this->uuid);

    $query = $this->databaseService
      ->select($this->storageTable, 'ss')
      ->fields('ss', ['uuid', 'uid', 'stage']);
    $or_condition = $query->orConditionGroup()
      ->condition('uuid', $uuid)
      ->condition('uid', $this->uid);
    $query->condition($or_condition);
    $session = $query->execute()->fetchAssoc();

    // If no match found, the session is invalid or the user doesn't have a session.
    if (empty($session)) {
      return FALSE;
    }

    $this->uid = $session['uid'];

    // Now we must check to see if a valid request was made.  A request is valid
    // if it lies in the array of nextSteps according to this session's last
    // step.  Either way, we update the session info and send back the result.
    $this->isValid = in_array($request, $this->nextStep[$session['stage']]);
    $this->updateSession($request);

    return $this->isValid;
  }

  /**
   * Removes the current session from the quickbooks session table.
   */
  public function closeSession() {
    // Can't do anything if we don't have a UUID.
    if (empty($this->uuid)) {
      return;
    }

    $uuid = md5($this->uuid);

    $query = $this->databaseService->delete($this->storageTable);
    $query->condition('uuid', $uuid);
    $query->execute();
  }

  /**
   * Update the session in the session in teh database.
   *
   * If the current session and request are valid, then we just update the
   * 'stage' column with the current incomming request.  Otherwise, we delete
   * the session from the database since it is no longer valid.
   *
   * @param $request
   */
  private function updateSession($request) {
    if ($this->isValid) {
      $query = $this->databaseService
        ->update($this->storageTable)
        ->fields(['stage' => $request]);
      $query->condition('uid', $this->uid);
      $query->execute();
    }
    else {
      $query = $this->databaseService->delete($this->storageTable);
      $query->condition('uid', $this->uid);
      $query->execute();
    }
  }

  /**
   * Set the uuid for the session.
   *
   * @param $uuid
   * @return $this
   */
  public function setUUID($uuid) {
    $this->uuid = $uuid;
    return $this;
  }

  /**
   * Set the UID of the User connected by the client.
   *
   * @param $uid
   * @return $this
   */
  public function setUID($uid) {
    $this->uid = $uid;
    return $this;
  }

  /**
   * Get the UID of the User associated with the current session token.
   *
   * @return int
   */
  public function getUID() {
    return $this->uid;
  }

}