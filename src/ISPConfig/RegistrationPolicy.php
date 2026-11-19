<?php

namespace Domainregistration\ISPConfig;

final class RegistrationPolicy
{
    /**
     * New registrations require an explicit opt-in; existing-domain operations
     * remain available while Mediacollege migrates to another provider.
     *
     * @param array $conf
     * @return bool
     */
    public static function isEnabled($conf)
    {
        return isset($conf['domainregistration']['registration_enabled'])
            && $conf['domainregistration']['registration_enabled'] === true;
    }
}
