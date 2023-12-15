<?php

namespace SimpleSAML\Module\proxystatistics\Auth\Process;

use SimpleSAML\Error\Exception;
use SimpleSAML\Logger;
use SimpleSAML\Metadata\MetaDataStorageHandler;
use SimpleSAML\Module\proxystatistics\Utils;
use PDO;

/**
 * @author Pavel Vyskočil <vyskocilpavel@muni.cz>
 */
class AmsCommand
{
    private $amsConnector;

    public function __construct()
    {
        $this->amsConnector = new AmsConnector();
    }
    private function writeLoginIp($sourceIdp, $service, $user, $ip, $date): void
    {
        $data = [
            'ip' => $ip,
            'user' => $user,
            'sourceIdp' => $sourceIdp,
            'service' => $service,
            'accessed' => $date,
            'ipVersion' => (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? 'ipv4' : 'ipv6')
        ];

        $this->amsConnector->sendToAms($data);
    }
    private function writeLogin($year, $month, $day, $sourceIdp, $service, $user = null)
    {
        $data = [
            'year' => $year,
            'month' => $month,
            'day' => $day,
            'sourceIdp' => $sourceIdp,
            'service' => $service,
            'count' => 1,
        ];

        if ($user && $this->amsConnector->getDetailedDays() > 0) {
          $data['user'] = $user;
        }

      $this->amsConnector->sendToAms($data);
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

        if (empty($idpEntityID) || empty($spEntityId)) {
            Logger::error(
                "'idpEntityId' or 'spEntityId'" .
                    " is empty and login log wasn't inserted into the database."
            );
        } else {
            if ($this->writeLogin($year, $month, $day, $idpEntityID, $spEntityId, $userId) === false) {
                Logger::error("The login log wasn't inserted into table: " . $this->statisticsTableName . ".");
            }
            if ($this->writeLoginIp($idpEntityID, $spEntityId, $userId, $ip, $dateTimestamp) === false) {
                Logger::error("The login log for ip wasn't inserted into table: " . $this->ipStatisticsTableName . ".");
            }
            if (!empty($idpName)) {
                $data = ['entityId' => $idpEntityID, 'idpName' => $idpName, 'idpName2' => $idpName];

                $this->amsConnector->sendToAms($data);
            }

            if (!empty($spName)) {
              $data = [
                'identifier' => $spEntityId,
                'spName' => $spName,
                'spName2' => $spName
              ];
              $this->amsConnector->sendToAms($data);

            }
        }
    }

}
