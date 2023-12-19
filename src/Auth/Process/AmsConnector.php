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
    $url = self::AMS_BASE_URL . "/projects/{$this->project_name}/topics/{$this->topic_name}:publish";
    Logger::debug(__METHOD__ . '::raw data: ' . var_export($data, true));

    $formattedData = [
      "voPersonId" => $data['login']['user'],
      "entityId" => $data['idp']['entityId'],
      "idpName" => $data['idp']['idpName'] ?? $data['idp']['idpName2'],
      "identifier" => $data['sp']['identifier'],
      "ipAddress" => $data['login_ip']['ip'],
      "date" => time(),
      "failedLogin" => "false",
      "eventIdentifier" => md5(time() . ( $data['login']['user'] ?? $data['login_ip']['ip'] ) ),
      "type" => "login", // Other types like 'registration' and ''membership' exists, todo: make configuration
      "source" => "simplesamlphp", // todo: make configuration
      "tenenvId" => "7" // todo: get from the configuration
    ];

    if(!empty($data['sp']['spName']) || !empty($data['sp']['spName2'])) {
      $formattedData["spName"] = $data['sp']['spName'] ?? $data['sp']['spName2'];
    }

    Logger::debug(__METHOD__ . '::formattedData: ' . var_export($formattedData, true));
    Logger::debug(__METHOD__ . '::url: ' . var_export($url, true));


    $cURLConnection = curl_init();

    curl_setopt($cURLConnection, CURLOPT_URL, $url);
    curl_setopt($cURLConnection, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($cURLConnection, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($cURLConnection, CURLOPT_HTTPHEADER, array(
      "Accept: application/json",
      "Content-Type: application/json",
      "x-api-key: " . self::AMS_USER_TOKEN,
    ));
    $jsonFormattedData = base64_encode(json_encode($formattedData));
    $pdata = "{\"messages\":[{\"data\":\"{$jsonFormattedData}\"}]}";
    Logger::debug(__METHOD__ . '::message: ' . var_export($pdata, true));

    curl_setopt($cURLConnection, CURLOPT_POSTFIELDS, $pdata);

    $amd_response = curl_exec($cURLConnection);

    // Check if any error occurred
    if (!curl_errno($cURLConnection)) {
      $info = curl_getinfo($cURLConnection);
      Logger::debug(__METHOD__ . '::ams post info: ' . var_export($info, true));
      $jsonArrayResponse = json_decode($amd_response);
      Logger::debug(__METHOD__ . '::ams response: ' . var_export($jsonArrayResponse, true));
    } else {
      Logger::error(__METHOD__ . '::ams post error ' . var_export(curl_error($cURLConnection), true));
    }

    curl_close($cURLConnection);
  }

}