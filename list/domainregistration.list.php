<?php

$liste = [
    'name' => 'domainregistration',
    'table' => 'domainregistration',
    'table_idx' => 'id',
    'search_prefix' => 'search_',
    'records_per_page' => 15,
    'file' => 'domainregistration_list.php',
    'edit_file' => 'domainregistration_edit.php',
    'delete_file' => 'domainregistration_del.php',
    'paging_tpl' => 'templates/paging.tpl.htm',
    'auth' => 'yes',
    'item' => [
        'id' => [
            'field' => 'id',
            'datatype' => 'INTEGER',
            'formtype' => 'TEXT',
            'op'  => '=',
        ],
        'domain' => [
            'field' => 'domain',
            'datatype' => 'VARCHAR',
            'formtype' => 'TEXT',
            'filters' => [
                0 => ['event' => 'SHOW', 'type' => 'IDNTOUTF8'],
            ],
            'op' => 'LIKE',
            'prefix' => '%',
            'suffix' => '%',
        ],
        'registered_at' => [
            'field' => 'registered_at',
            'datatype' => 'DATETIME',
            'formtype' => 'TEXT',
            'op' => 'LIKE',
            'prefix' => '%',
            'suffix' => '%',
        ],
        'cancelled_at' => [
            'field' => 'cancelled_at',
            'datatype' => 'DATETIME',
            'formtype' => 'TEXT',
            'op' => 'LIKE',
            'prefix' => '%',
            'suffix' => '%',
        ],
    ],
];

if ('admin' === $_SESSION['s']['user']['typ']) {
    $liste['item']['sys_group_id'] = [
        'field' => 'sys_groupid',
        'datatype' => 'INTEGER',
        'formtype' => 'SELECT',
        'op' => '=',
        'datasource' => [
            'type' => 'SQL',
            'keyfield' => 'groupid',
            'valuefield' => 'name',
            'querystring' => "
                SELECT
                    sys_group.groupid,
                    CONCAT(
                        IF(client.company_name != '', CONCAT(client.company_name, ' :: '), ''),
                        IF(client.contact_firstname != '', CONCAT(client.contact_firstname, ' '),''),
                        client.contact_name,
                        ' (', client.username, IF(client.customer_no != '', CONCAT(', ', client.customer_no), ''), ')'
                    ) AS name
                FROM
                    sys_group, client
                WHERE
                    sys_group.groupid != 1
                    AND sys_group.client_id = client.client_id
                ORDER BY
                    client.company_name, client.contact_name
            ",
        ],
    ];
}
