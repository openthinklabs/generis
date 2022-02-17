<?php

/**
 * The persistence configuration contains a list of persistences
 * identified by name.
 *
 * See common_persistence_Manager for a list of drivers
 * provided by  generis. Aditional drivers can be used by setting
 * the drivers full class name
 *
 * @author Open Assessment Technologies SA
 * @package generis
 * @license GPLv2  http://www.opensource.org/licenses/gpl-2.0.php
 * @see common_persistence_Manager
 */
<?php
/**
 * Default config header created during install
 */

return new oat\generis\persistence\PersistenceManager(array(
    'persistences' => array(
        'default' => array(
            'driver' => 'dbal',
            'connection' => array(
                'driver' => 'pdo_mysql',
                'driverClass' => null,
                'instance' => null,
                'host' => 'localhost',
                'dbname' => 'cbt_dbname',
                'user' => 'cbt_user',
                'password' => 'cbt_db_password',
                'driverOptions' => array(
                ),
                'charset' => 'utf8'
            )
        ),
        /** 
        'default_kv' => array(
            'driver' => 'common_persistence_SqlKvDriver',
            'sqlPersistence' => 'default'
        ),
        **/
        'default_kv' => array(
            'driver' => 'phpredis',
            'host' => '127.0.0.1',
            'port' => 6379
        ),
        /**                                                
        'serviceState' => array(
            'driver' => 'phpfile'
        ),
        */
        'serviceState' => array(
            'driver' => 'phpredis',
            'host' => '127.0.0.1',
            'port' => 6379
        ),        
        'deliveryExecution' => array(
            'driver' => 'phpredis',
            'host' => '127.0.0.1',
            'port' => 6379
        ),        
        'cache' => array(
            'driver' => 'phpfile'
        ),
        'uriProvider' => array(
            'driver' => 'phpredis',
            'host' => '127.0.0.1',
            'port' => 6379
        ),
        'session' => array(
            'driver' => 'phpredis',
            'host' => '127.0.0.1',
            'port' => 6379
        ),
        'authKeyValue' => array(
            'driver' => 'phpredis',
            'host' => '127.0.0.1',
            'port' => 6379
        ),
        'keyValueResult' => array(
            'driver' => 'phpredis',
            'host' => '127.0.0.1',
            'port' => 6379
        ),
        'maintenance' => array(
            'driver' => 'phpfile'
        )        
    )
));