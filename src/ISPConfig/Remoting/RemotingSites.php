<?php

namespace Domainregistration\ISPConfig\Remoting;

use remoting_sites;

/**
 * We use ISPConfig's remoting lib to retrieve sites and store new domain aliases.
 * It's not a pretty solution, but it was either this or copy/pasting the form logic
 * or remoting lib logic... This seems like a relatively clean solution compared to that
 * and it makes us less dependent on internal changes in the web module. Also, it will
 * take care of the ISPConfig datalog for us.
 */
final class RemotingSites extends remoting_sites
{
    /**
     * Simulate an active remoting session for the currently logged in control panel user
     *
     * @param string $session_id
     * @return array
     */
    public function getSession($session_id)
    {
        return [
            'remote_session' => md5(uniqid()),
            'remote_userid' => $_SESSION['s']['user']['userid'],
            'remote_functions' => '',
            'client_login' => true,
            'tstamp' => time(),
        ];
    }
}
