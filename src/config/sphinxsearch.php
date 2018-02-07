<?php
return [
    'host'    => '127.0.0.1',
    'port'    => 9312,
    'timeout' => 30,
    'indexes' => [
        'name' => 'my_index_name',
       	'table' => 'keywords',
	    'column' => 'id'
    ],
    'mysql_server' => [
        'host' => '127.0.0.1',
        'port' => 9306
    ]
];
