<?php

class tform
{
    public $wordbook = [
        'domain_error_already_taken' => 'domain already taken',
        'editing_disabled_txt' => 'editing is disabled',
        'limit_domainregistration_txt' => 'limit reached',
    ];

    public $formDef = [
        'list_default' => 'foo.php',
    ];

    public function getSQL($dataRecord, $tab, $operaton, $id) {}
    public function getCurrentTab() {}
}
