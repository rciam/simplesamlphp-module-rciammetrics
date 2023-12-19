<?php

namespace SimpleSAML\Module\rciammetrics\Auth\Process;

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
  private $userIdAttribute;
  private $conn = null;
  private $oidcIss;
  private $keycloakSp;
  // todo: make configuration
  private $tenenvId = 7;

  const CONFIG_FILE_NAME = 'module_rciammetrics.php';

  /** @deprecated */
  const ENCRYPTION = 'encryption';
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
  const USER_ID_ATTRIBUTE = 'userIdAttribute';
  const OIDC_ISS = 'oidcIssuer';
  const KEYCLOAK_SP = 'keycloakSp';
  const  AMS_INJEST_ENDPOINT = '/ams/ingest';

  const AMS_BASE_URL="https://msg-devel.argo.grnet.gr/v1";
  const AMS_USER_TOKEN="af03e134515fd414a8af9a923e2a9862cb770990dce8a8aa5da05f2124e01797";
  // todo: move to config
  private $topic_name = "metrics";
  // todo: move to config
  private $project_name = "AAIMETRICS";

  public function __construct()
  {
    $conf = Configuration::getConfig(self::CONFIG_FILE_NAME);
    $this->mode = $conf->getOptionalValue(self::MODE, 'PROXY');
    $this->idpEntityId = $conf->getOptionalValue(self::IDP_ENTITY_ID, '');
    $this->idpName = $conf->getOptionalValue(self::IDP_NAME, '');
    $this->spEntityId = $conf->getOptionalValue(self::SP_ENTITY_ID, '');
    $this->spName = $conf->getOptionalValue(self::SP_NAME, '');
    $this->userIdAttribute = $conf->getOptionalValue(self::USER_ID_ATTRIBUTE, null);
    $this->oidcIss = $conf->getOptionalValue(self::OIDC_ISS, null);
    $this->keycloakSp = $conf->getOptionalValue(self::KEYCLOAK_SP, null);
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

    $data_tmpl = [
      "voPersonId" => "<USER_ID>",
      "entityId" => "<IDP_ENTITY_ID>",
      "idpName" => "1 'authnAuthority' value,  2.<IDP_DISPLAY_NAME>, else <IDP_ALIA> 3. 'Keycloak' for Keycloak users",
      "identifier" => "<CLIENT_ID>",
      "spName" => "<SP_DISPLAY_NAME>", // OPTIONAL SHOULD BE OMITTED WHEN NOT AVAILABLE
      "ipAddress" => "<IP_ADDRESS>",
      "date" => "<TIMESTAMP>",
      "failedLogin" => "true or false",
      "type" => "login", // Other types like 'registration' and ''membership' exists
      "source" => "Keycloak",
      "tenenvId" => "<TENANT_ID>"
    ];

    Logger::debug('data: ' . var_export($data, true));


    $cURLConnection = curl_init();

    curl_setopt($cURLConnection, CURLOPT_URL, $url);
    curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($cURLConnection, CURLOPT_HTTPHEADER, array(
      "Accept: application/json",
      "Content-Type: application/json",
      "x-api-key: " . self::AMS_USER_TOKEN,
    ));
    curl_setopt($cURLConnection, CURLOPT_POSTFIELDS, json_encode($data));

    $amd_response = curl_exec($cURLConnection);
    curl_close($cURLConnection);

    $jsonArrayResponse = json_decode($amd_response);

  }

}