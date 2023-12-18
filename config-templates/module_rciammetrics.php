<?php
/**
 * This is an example configuration file for the Metrics module for the RCIAM Metrics framework.
 * Copy this file to the SimpleSAMLphp config directory and edit the properties according to your specific deployment environment.
 *
 */

$config = [

    /*
     * Choose one from the following modes: PROXY, IDP, SP
     */
    'mode' => '',

    /*
     * EntityId of IdP
     * REQUIRED FOR IDP MODE
     */
    'idpEntityId' => '',

    /*
     * Name of IdP
     * REQUIRED FOR IDP MODE
     */
    'idpName' => '',

    /*
     * EntityId of SP
     * REQUIRED FOR SP MODE
     */
    'spEntityId' => '',

    /*
     * Name of SP
     * REQUIRED FOR SP MODE
     */
    'spName' => '',

    /**
     * Which attribute should be used as user ID.
     * @default uid
     */
    'userIdAttribute' => null,

    /*
     * Which users should be blacklisted
     */
    'userIdExcludelist' => array(),

    /*
     * Fill the entityID of OpenID Connect Provider
     */
    'oidcIssuer' => 'http://example.org/openidconnect/sp',

    /*
     * Fill the entityID of Keycloak Provider
     */
    'keycloakSp' => 'http://example.org/keykloak/sp',

    /**
     * Authentication source name if authentication should be required.
     * Defaults to empty string.
     */
    //'requireAuth.source' => 'default-sp',
];
