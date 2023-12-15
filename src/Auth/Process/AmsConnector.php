<?php

namespace SimpleSAML\Module\proxystatistics\Auth\Process;

use SimpleSAML\Configuration;
use SimpleSAML\Database;
use SimpleSAML\Logger;

class AmsConnector
{
  private $mode;
  private $idpEntityId;
  private $idpName;
  private $spEntityId;
  private $spName;
  private $detailedDays;
  private $userIdAttribute;
  private $conn = null;
  private $oidcIss;
  private $keycloakSp;

  const CONFIG_FILE_NAME = 'module_statisticsproxy.php';

  /** @deprecated */
  const ENCRYPTION = 'encryption';
  const STORE = 'store';
  /** @deprecated */
  const SSL_CA = 'ssl_ca';
  /** @deprecated */
  const SSL_CERT = 'ssl_cert_path';
  /** @deprecated */
  const SSL_KEY = 'ssl_key_path';
  /** @deprecated */
  const SSL_CA_PATH = 'ssl_ca_path';
  const MODE = 'mode';
  const IDP_ENTITY_ID = 'idpEntityId';
  const IDP_NAME = 'idpName';
  const SP_ENTITY_ID = 'spEntityId';
  const SP_NAME = 'spName';
  const DETAILED_DAYS = 'detailedDays';
  const USER_ID_ATTRIBUTE = 'userIdAttribute';
  const OIDC_ISS = 'oidcIssuer';
  const KEYCLOAK_SP = 'keycloakSp';
  const TABLE_PREFIX = 'database.prefix';
  const  AMS_INJEST_ENDPOINT = '/ams/ingest';

  const AMS_BASE_URL="https://msg-devel.argo.grnet.gr/v1";
  const AMS_USER_TOKEN="24e2b1fa7e367a6722e16b94765264082fca56022ac68dcc8728c26376d65dd6";
  const AMS_ADMIN_TOKEN="25bbd90ba4bb38df217bf02c5369dff30bb524a0ad0c4a5666bba602ee01d794";
  private $topic_name = "metrics";
  private $project_name = "AAIMETRICS";

  public function __construct()
  {
    $conf = Configuration::getConfig(self::CONFIG_FILE_NAME);
    $this->storeConfig = $conf->getArray(self::STORE, null);

    $this->storeConfig = Configuration::loadFromArray($this->storeConfig);
    $this->databaseDsn = $this->storeConfig->getString('database.dsn');

    $this->mode = $conf->getString(self::MODE, 'PROXY');
    $this->idpEntityId = $conf->getString(self::IDP_ENTITY_ID, '');
    $this->idpName = $conf->getString(self::IDP_NAME, '');
    $this->spEntityId = $conf->getString(self::SP_ENTITY_ID, '');
    $this->spName = $conf->getString(self::SP_NAME, '');
    $this->detailedDays = $conf->getInteger(self::DETAILED_DAYS, 0);
    $this->userIdAttribute = $conf->getString(self::USER_ID_ATTRIBUTE, null);
    $this->oidcIss = $conf->getString(self::OIDC_ISS, null);
    $this->keycloakSp = $conf->getString(self::KEYCLOAK_SP, null);
  }

  public function getMode()
  {
    return $this->mode;
  }

  public function getIdpEntityId()
  {
    return $this->idpEntityId;
  }

  public function getIdpName()
  {
    return $this->idpName;
  }

  public function getSpEntityId()
  {
    return $this->spEntityId;
  }

  public function getSpName()
  {
    return $this->spName;
  }

  public function getDetailedDays()
  {
    return $this->detailedDays;
  }

  public function getUserIdAttribute()
  {
    return $this->userIdAttribute;
  }

  public function getOidcIssuer()
  {
    return $this->oidcIss;
  }

  public function getKeycloakSp()
  {
    return $this->keycloakSp;
  }

  public function sendToAms($data) {
    $url = self::AMS_BASE_URL . "/projects/{$this->poject_name}/topics/{$this->topic_name}:publish";

    $cURLConnection = curl_init();

    curl_setopt($cURLConnection, CURLOPT_URL, $url);
    curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($cURLConnection, CURLOPT_HTTPHEADER, array(
      "Content-Type: application/json",
      "x-api-key: " . self::AMS_ADMIN_TOKEN,
    ));
    curl_setopt($cURLConnection, CURLOPT_HTTPHEADER, array(
      "Content-Type: application/json",
      "x-api-key: " . self::AMS_USER_TOKEN,
    ));
    curl_setopt($cURLConnection, CURLOPT_POSTFIELDS, json_encode($data));

    $amd_response = curl_exec($cURLConnection);
    curl_close($cURLConnection);

    $jsonArrayResponse = json_decode($amd_response);

  }

}