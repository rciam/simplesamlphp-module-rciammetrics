<?php

namespace SimpleSAML\Module\proxystatistics\Auth\Process;

use DateTime;
use DateTimeZone;
use SimpleSAML\Auth\ProcessingFilter;
use SimpleSAML\Configuration;
use SimpleSAML\Error\Exception;
use SimpleSAML\Logger;

/**
 *
 * @author Pavel Vyskočil <vyskocilpavel@muni.cz>
 */
class Statistics extends ProcessingFilter
{
    private array $config;
    private string $reserved;
    private string $userIdAttribute;

    public function __construct(array $config, string $reserved)
    {
        parent::__construct($config, $reserved);
        $this->config = Configuration::getConfig(DatabaseConnector::CONFIG_FILE_NAME);
        $this->reserved = (array)$reserved;
    }

    public function process(array &$request): void
    {
        if (empty($this->config->getString('userIdAttribute', null))) {
            if (empty($request['rciamAttributes']['cuid'])) {
                Logger::error("[proxystatistics:proccess] userIdAttribute has not been configured but ['rciamAttributes']['cuid'] is not available: This login cannot be recorded");
                return;
            } else {
                $this->userIdAttribute = $request['rciamAttributes']['cuid'];
            }
        } else {
            if (empty($request['Attributes'][$this->config->getString('userIdAttribute', null)])) {
                Logger::error("[proxystatistics:proccess] userIdAttribute has been configured but ['Attributes']['" . $this->config->getString('userIdAttribute') . "'] is not available: This login cannot be recorded");
                return;
            } else {
                $this->userIdAttribute = $request['Attributes'][$this->config->getString('userIdAttribute', null)];
            }
        }
        // Check if user is in blacklist
        if (
            !empty($this->userIdAttribute)
            && !empty($this->config->getArray('userIdBlacklist'))
            && !empty(array_intersect($this->userIdAttribute, $this->config->getArray('userIdBlacklist')))
        ) {
            Logger::notice("[proxystatistics:proccess] Skipping blacklisted user with id " . var_export($this->userIdAttribute, true));
            return;
        }

        $dateTime = new DateTime('now', new DateTimeZone('UTC'));

        $amsCmd = new AmsCommand();
        $amsCmd->insertLogin($request, $dateTime, $this->userIdAttribute[0]);
        $spEntityId = $request['SPMetadata']['entityid'];

        $userIdentity = '';
        $sourceIdPEppn = '';
        $sourceIdPEntityId = '';

        $userIdentity = $this->userIdAttribute[0];
        if (isset($request['Attributes']['sourceIdPEppn'][0])) {
            $sourceIdPEppn = $request['Attributes']['sourceIdPEppn'][0];
        }
        if (isset($request['Attributes']['sourceIdPEntityID'][0])) {
            $sourceIdPEntityId = $request['Attributes']['sourceIdPEntityID'][0];
        }

        if (isset($request['perun']['user'])) {
            $user = $request['perun']['user'];
            Logger::notice('UserId: ' . $user->getId() . ', identity: ' .  $userIdentity . ', service: '
                . $spEntityId . ', external identity: ' . $sourceIdPEppn . ' from ' . $sourceIdPEntityId);
        } else {
            Logger::notice('User identity: ' .  $userIdentity . ', service: ' . $spEntityId .
                ', external identity: ' . $sourceIdPEppn . ' from ' . $sourceIdPEntityId);
        }
    }
}
