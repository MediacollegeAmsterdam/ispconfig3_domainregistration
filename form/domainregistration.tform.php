<?php

$form = [
    'title' => 'Register domainname',
    'description' => '',
    'name' => 'domainregistration',
    'db_table' => 'domainregistration',
    'db_table_idx' => 'id',
    'db_history' => 'yes',
    'tab_default' => 'domainregistration',
    'action' => 'domainregistration_edit.php',
    'list_default' => 'domainregistration_list.php',
    'auth' => 'yes',
    'auth_preset' => [
        'userid' => 0,
        'groupid' => 0,
        'perm_user' => 'ruid',
        'perm_group' => 'ruid',
        'perm_other' => '',
    ],
    'tabs' => [
        'domainregistration' => [
            'title' => 'Register domainname',
            'width' => 100,
            'template' => 'templates/domainregistration_edit.htm',
            'fields' => [
                'domain' => [
                    'datatype' => 'VARCHAR',
                    'formtype' => 'TEXT',
                    'filters' => [
                        0 => ['event' => 'SAVE', 'type' => 'IDNTOASCII'],
                        1 => ['event' => 'SHOW', 'type' => 'IDNTOUTF8'],
                        2 => ['event' => 'SAVE', 'type' => 'TOLOWER'],
                    ],
                    'validators' => [
                        0 => ['type' => 'NOTEMPTY', 'errmsg' => 'domain_error_empty'],
                        1 => ['type' => 'UNIQUE', 'errmsg' => 'domain_error_unique'],
                        2 => [
                            'type' => 'REGEX',
                            'regex' => sprintf('/^[\w\-]{2,255}\.%s$/', $conf['domainregistration']['allowed_tlds']),
                            'errmsg' => 'domain_error_regex'
                        ],
                    ],
                    'default' => '',
                    'value' => '',
                    'width' => '30',
                    'maxlength' => '255',
                ],
                'registrar_identifier' => [
                    'datatype' => 'VARCHAR',
                    'formtype' => 'TEXT',
                    'default' => '',
                    'value' => '',
                ],
                'registered_at' => [
                    'datatype' => 'VARCHAR',
                    'formtype' => 'TEXT',
                    'default' => '',
                    'value' => '',
                ],
            ],
        ],
    ],
];
