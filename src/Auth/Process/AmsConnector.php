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
  private $oidcIss;
  private $keycloakSp;
  private $topicName;
  private $projectName;
  private $amsToken;
  private $rciamMetricsTenantId;
  private $dataSource;
  private $amsBaseUrl;
  private $amsDataType;

  public const CONFIG_FILE_NAME = 'module_rciammetrics.php';
  public const MODE = 'mode';
  public const IDP_ENTITY_ID = 'idpEntityId';
  public const IDP_NAME = 'idpName';
  public const SP_ENTITY_ID = 'spEntityId';
  public const SP_NAME = 'spName';
  public const USER_ID_ATTRIBUTE = 'userIdAttribute';
  public const OIDC_ISS = 'oidcIssuer';
  public const KEYCLOAK_SP = 'keycloakSp';
  public const TOPIC_NAME = "amsTopicName";
  public const PROJECT_NAME = "amsProjectName";
  public const AMS_TOKEN = "amsToken";
  public const RCIAM_METRICS_TENANT_ID = "amsRciamMetricsTenantId";
  public const DATASOURCE = "amsDataSource";
  public const AMS_BASE_URL = "amsBaseUrl";
  public const AMS_DATA_TYPE = "amsDataType";

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

    $this->topicName = $conf->getString(self::TOPIC_NAME);
    $this->projectName = $conf->getString(self::PROJECT_NAME);
    $this->amsToken = $conf->getString(self::AMS_TOKEN);
    $this->rciamMetricsTenantId = $conf->getString(self::RCIAM_METRICS_TENANT_ID);
    $this->dataSource = $conf->getString(self::DATASOURCE);
    $this->amsBaseUrl = $conf->getString(self::AMS_BASE_URL);
    $this->amsDataType = $conf->getOptionalString(self::AMS_DATA_TYPE, 'login');
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

  public function sendToAms($data): void
  {
    $url = $this->amsBaseUrl . "/projects/{$this->projectName}/topics/{$this->topicName}:publish";
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
      "type" => $this->amsDataType,
      "source" => $this->dataSource,
      "tenenvId" => $this->rciamMetricsTenantId
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
      "x-api-key: " . $this->amsToken,
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