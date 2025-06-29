<?php
/**
 * Use this file to override global defaults.
 *
 * See the individual environment DB configs for specific config information.
 */

return array(
     'default' => array(
        'type'        => 'mysqli', 
            'hostname'   => 'db', 
            'port'       => '3306',
            'database'   => 'equipment_db', 
            'username'   => 'root',
            'password'   => 'root',
            'persistent' => false,
            'compress'   => false,
        ),
    );
