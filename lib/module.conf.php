<?php

// If we are loading the top nav, load the icon style
if (false !== strpos($_SERVER['SCRIPT_NAME'], '/nav.php') && false !== strpos($_SERVER['QUERY_STRING'], 'nav=top')) {
    echo '<style>.icon-domainregistration:before { content: "\e610" }</style>';
}

$module = [
    'name' => 'domainregistration',
    'title' => 'Domainregistration',
    'template' => 'module.tpl.htm',
    'startpage' => 'domainregistration/domainregistration_list.php',
    'order' => 5,
    'nav' => [
        [
            'title' => 'Domainregistration',
            'open' => 1,
            'items' => [
                [
                    'title' => 'Domains',
                    'target' => 'content',
                    'link' => 'domainregistration/domainregistration_list.php',
                ]
            ]
        ]
    ]
];
