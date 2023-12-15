<?php
/**
 * This is example configuration of SimpleSAMLphp Perun interface and additional features.
 * Copy this file to default config directory and edit the properties.
 *
 * @author Pavel Vyskočil <vyskocilpavel@muni.cz>
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

    /*
     * Fill config for SimpleSAML\Database.
     * If not set, the global config is used.
     * @see SimpleSAML\Database
     */
    'store' => [
        'database.dsn' => 'mysql:host=localhost;port=3306;dbname=STATS;charset=utf8',
        'database.username' => 'stats',
        'database.password' => 'stats',

        /*
         * (Optional) Table prefix
         */
        'database.prefix' => '',

        /**
         * Configuration for SSL
         * If you want to use SSL you must filled this value and uncomment block of code
         */
        /*
        'database.driver_options' => [
            // Path for the ssl key file
            PDO::MYSQL_ATTR_SSL_KEY => '',
            // Path for the ssl cert file
            PDO::MYSQL_ATTR_SSL_CERT => '',
            // Path for the ssl ca file
            PDO::MYSQL_ATTR_SSL_CA => '',
            // Path for the ssl ca dir
            PDO::MYSQL_ATTR_SSL_CAPATH => '',
        ],
        */

        /*
         * True or false if you would like a persistent database connection
         */
        'database.persistent' => false,

        /*
         * Database slave configuration is optional as well. If you are only
         * running a single database server, leave this blank. If you have
         * a master/slave configuration, you can define as many slave servers
         * as you want here. Slaves will be picked at random to be queried from.
         *
         * Configuration options in the slave array are exactly the same as the
         * options for the master (shown above) with the exception of the table
         * prefix and driver options.
         */
        'database.slaves' => [
            /*
            [
                'dsn' => 'mysql:host=myslave;dbname=saml',
                'username' => 'simplesamlphp',
                'password' => 'secret',
                'persistent' => false,
            ],
            */
        ],
    ],

    /*
     * For how many days should detailed statistics (per user) be kept.
     * @default 0
     */
    'detailedDays' => 0,

    /**
     * Which attribute should be used as user ID.
     * @default uid
     */
    'userIdAttribute' => null,

    /*
     * Which users should be blacklisted
     */
    'userIdBlacklist' => array(),

    /*
     * Fill the table name for statistics
     */
    'statisticsTableName' => 'statistics',

    /*
     * Fill the table name for detailed statistics
     * @default
     */
    'detailedStatisticsTableName' => 'statistics_detail',

    /*
     * Fill the table name for ip statistics
     * @default
     */
    'ipStatisticsTableName' => 'statistics_ip',

    /*
     * Fill the table name for identityProvidersMap
     */
    'identityProvidersMapTableName' => 'identityProvidersMap',

    /*
     * Fill the table name for serviceProviders
     */
    'serviceProvidersMapTableName' => 'serviceProvidersMap',

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
