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
     * Which users should be excluded
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

     /*
      *  Source that provides data
      * */
    'amsDataSource' => 'simplesamlphp',

      /*
       * Fill the RCIAM Metrics Tenant Environment ID
       * this is a string value
     */
    'amsRciamMetricsTenantId' => null,

    /*
     * AMS authentication token
     * */
    'amsToken' => null,

     /*
      * AMS Topic name
      * */
    'amsTopicName' => 'metrics',

    /*
     * AMS Project Name
     * */
    'amsProjectName' => 'AAIMETRICS',

    /*
     * AMS Base url
     * */
    'amsBaseUrl' => null,

    /*
     * AMS Data type, Types supported are `login`, `registration`, `membership`
     * default value is `login`
     * */
    'amsDataType' => '',
];
