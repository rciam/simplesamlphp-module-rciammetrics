<?php

namespace SimpleSAML\Module\rciammetrics\Auth\Process;

use SimpleSAML\Error\Exception;
use SimpleSAML\Logger;
use SimpleSAML\Metadata\MetaDataStorageHandler;
use SimpleSAML\Module\rciammetrics\Utils;

class AmsCommand
{
    private $amsConnector;

    public function __construct()
    {
        $this->amsConnector = new AmsConnector();
    }
    private function getLoginIp($sourceIdp, $service, $user, $ip, $date): array
    {
        $dataLoginIp = [
            'ip' => $ip,
            'user' => $user,
            'sourceIdp' => $sourceIdp,
            'service' => $service,
            'accessed' => $date,
            'ipVersion' => (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? 'ipv4' : 'ipv6')
        ];

        return $dataLoginIp;
    }
    private function getLogin($year, $month, $day, $sourceIdp, $service, $user = null): array
    {
      $loginData = [
            'year' => $year,
            'month' => $month,
            'day' => $day,
            'sourceIdp' => $sourceIdp,
            'service' => $service,
            'count' => 1,
        ];

        if ($user && $this->amsConnector->getDetailedDays() > 0) {
          $loginData['user'] = $user;
        }

      return $loginData;
    }
    public function insertLogin(&$request, &$date, &$userId)
    {
        if (!in_array($this->amsConnector->getMode(), ['PROXY', 'IDP', 'SP'])) {
            throw new Exception('Unknown mode is set. Mode has to be one of the following: PROXY, IDP, SP.');
        }
        if ($this->amsConnector->getMode() !== 'IDP') {
            if (!empty($request['saml:sp:IdP'])) {
                $idpEntityID = $request['saml:sp:IdP'];
                $idpMetadata = MetaDataStorageHandler::getMetadataHandler()->getMetaData($idpEntityID, 'saml20-idp-remote');
            } else {
                $idpEntityID = $request['Source']['entityid'];
                $idpMetadata = $request['Source'];
            }
            $idpName = self::getIdPDisplayName($idpMetadata);
        }
        if ($this->amsConnector->getMode() !== 'SP') {
            if (
                !empty($request['saml:RelayState'])
                && !empty($this->amsConnector->getKeycloakSp())
                && strpos($request['Destination']['entityid'], $this->amsConnector->getKeycloakSp()) !== false
            ) {
                $spEntityId = explode('.', $request['saml:RelayState'], 3)[2];
                $spName = null;
            } elseif (
                !empty($request['saml:RequesterID'])
                && !empty($this->amsConnector->getOidcIssuer())
                && (strpos($request['Destination']['entityid'], $this->amsConnector->getOidcIssuer()) !== false)
            ) {
                $spEntityId = str_replace(
                    $this->amsConnector->getOidcIssuer() . "/",
                    "",
                    $request['saml:RequesterID'][0]
                );
                $spName = null;
            } elseif (
                !empty($request['saml:RelayState'])
                && !empty($this->amsConnector->getOidcIssuer())
                && strpos($request['Destination']['entityid'], $this->amsConnector->getOidcIssuer()) !== false
            ) {
                $spEntityId = $request['saml:RelayState'];
                $spName = null;
            } else {
                $spEntityId = $request['Destination']['entityid'];
                $spName = self::getSPDisplayName($request['Destination']);
            }
        }

        Logger::debug(__METHOD__ . "::mode: " . var_export($this->amsConnector->getMode(), true));

        if ($this->amsConnector->getMode() === 'IDP') {
            $idpName = $this->amsConnector->getIdpName();
            $idpEntityID = $this->amsConnector->getIdpEntityId();
        } elseif ($this->amsConnector->getMode() === 'SP') {
            $spEntityId = $this->amsConnector->getSpEntityId();
            $spName = $this->amsConnector->getSpName();
        }

        $year = $date->format('Y');
        $month = $date->format('m');
        $day = $date->format('d');
        $dateTimestamp = $date->format('Y-m-d H:i:s T');
        $ip = Utils::getClientIpAddress();
        $data = [];

        if (empty($idpEntityID) || empty($spEntityId)) {
            Logger::error(
                "'idpEntityId' or 'spEntityId'" .
                    " is empty and login log wasn't inserted into the database."
            );
        } else {
            $data['login'] = $this->getLogin($year, $month, $day, $idpEntityID, $spEntityId, $userId);
            $data['login_ip'] = $this->getLoginIp($idpEntityID, $spEntityId, $userId, $ip, $dateTimestamp);
            if (!empty($idpName)) {
                $data['idp'] = ['entityId' => $idpEntityID, 'idpName' => $idpName, 'idpName2' => $idpName];
            }

            if (!empty($spName)) {
              $data['sp'] = [
                'identifier' => $spEntityId,
                'spName' => $spName,
                'spName2' => $spName
              ];
            }
        }

        if(!empty($data)) {
          $this->amsConnector->sendToAms($data);
        } else {
          Logger::error("No data were extracted for sending");
        }
    }

    public static function getSPDisplayName($spMetadata): ?string
    {
      if (!empty($spMetadata['name'])) {
        // TODO: Use \SimpleSAML\Locale\Translate::getPreferredTranslation()
        // in SSP 2.0
        if (!empty($spMetadata['name']['en'])) {
          return $spMetadata['name']['en'];
        } else {
          return $spMetadata['name'];
        }
      }

      if (!empty($spMetadata['OrganizationDisplayName'])) {
        // TODO: Use \SimpleSAML\Locale\Translate::getPreferredTranslation()
        // in SSP 2.0
        if (!empty($spMetadata['OrganizationDisplayName']['en'])) {
          return $spMetadata['OrganizationDisplayName']['en'];
        } else {
          return $spMetadata['OrganizationDisplayName'];
        }
      }

      return null;
    }

}
