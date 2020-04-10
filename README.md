# ISPConfig3 Domainregistration

This ISPConfig3 module allows your clients to register a domainname.


## Features

- checks domain availability with a registrar (currently only Openprovider)
- registers a domainname
- automatically adds www and non-www DNS A records pointing to the client's first website, if they have one
- automatically creates a domain alias in ISPConfig for www and non-www
- extends ISPConfig remoting to expose two API endpoints:
    - `domainregistration_get`
    - `domainregistration_cancel`


## Requirements

- ISPConfig 3.1.15p3 or newer
- PHP 5.4 or newer to run the module
- PHP 7.4 or newer to run the development tools and test suite
- See `CONTRIBUTING.md` for more information as to why there is a difference in PHP version requirements


## Installation

Download the tarball for the latest release and extract it to `/usr/local/ispconfig/interface/web/domainregistration`. You *must* name the module directory `domainregistration`. Alternatively, check out the
source code with `git`:

```
$ git clone https://github.com/MediacollegeAmsterdam/ispconfig3_domainregistration /usr/local/ispconfig/interface/web/domainregistration
```

ISPConfig does not hook into modules to create their database tables, so you will need to do that manually
with a command like to the following:

```
$ mysql -u <database user> -p <ispconfig database name> < data/sql/domainregistration.sql
```

That's it, the module is installed! Make sure to grant your CP users access to the module, otherwise they
won't be able to use it.


## Configuration

Create the file `/usr/local/ispconfig/interface/lib/config.inc.local.php` if it does not
yet exist. This gets included from ISPConfig's `config.inc.php`. Add the following content to the file:

```
$conf['domainregistration'] = [
    'allowed_tlds' => 'nl|com', // Pipe-separated list of allowed domain extensions
    'max_active_domains_per_client' => 1,
    'sentry_dsn' => '', // optional
    'warning_txt' => 'Registration of abusive and/or offensive domainnames will not be tolerated.', // optional
    'openprovider' => [
        'endpoint' => 'https://api.cte.openprovider.eu/v1beta', // test environment
        // 'endpoint' => 'https://api.openprovider.eu/v1beta', // production environment
        'username' => 'y',
        'password' => 'x',
        'ownerHandle' => '123-NL',
        'adminHandle' => '123-NL',
        'techHandle' => '123-NL',
        'billingHandle' => '123-NL',
    ],
];
```

Most configuration parameters should be self-explanatory. You need to add a Contact in your account
which has API access. For the username and password, use the actual username and password. The legacy Openprovider XML API advised you to use their password hash, but that won't work with the new REST API
which this module uses.

The handles can be retrieved from the 'Customers' menu in the Openprovider control panel. Note that the
owner must be one of those pre-defined customers. This means we do not send WHOIS information from
ISPConfig clients to Openprovider.

If you use Sentry, you can optionally add the DSN to receive notifications of exceptions. This is optional;
leave the parameter as an empty string if you do not want to use it.


## Registrars


### Openprovider

At the moment the only supported registrar is Openprovider. If you want more, feel free to send a PR ;)


### Troubleshooting

It is feasible to configure Sentry so you get informed of any problems. Otherwise, there are logs written
to the `sys_log` database table. The ISPConfig interface does not appear to log to files.


### See also

- https://support.openprovider.eu/hc/en-us/articles/216644928-API-Error-Codes
- https://docs.openprovider.com/doc/all
