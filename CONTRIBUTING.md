# Contributing

Thank you for taking the time to contribute to this project! Please take the requirements and guidelines in this document into account when you create a contribution, in order to streamline the process.

## The big picture

- Configuration is done in `config.inc.local.php` in ISPConfig
- ISPConfig has certain 'entry points' into a module, which we cannot always influence. We try as much as possible to use those entry points as a sort of wrapper around our namespaced, unit tested code in `src/`.
- For domainname registration we have a `RegistrarFactory` which can instantiate a class implementing the `RegistrarInterface`. At the time of writing only Openprovider is supported. The class which implements the `RegistrarInterface` uses a class which implements the `AbstractApi` in turn, to communicate with the actual API.

## Requirements

- Coding style MUST adhere to PSR-1 and PSR-12 standards
- PHP syntax MUST be compatible with PHP 5.4 or newer
- New PHP code in `src/` MUST be covered by unit tests
- Test suite (which you can run with the command `composer ci`) MUST pass
- Vendor libraries pulled in using Composer MUST only used for `require-dev`
- Test suite syntax MAY be newer than PHP 5.4

## About the PHP version requirement and composer setup

Ideally we would have made the entire project PHP 7.x and used Guzzle for the API communication, but we are limited by the PHP versions which are available on the operating system. At the time of writing, the default PHP version on a CentOS 6 installation is PHP 5.4. This means we cannot use any PHP syntax that isn't supported by that version, nor can we pull in any third party libraries which use newer PHP syntax.

## Database changes

The initial database structure can be found in `data/sql/domainregistration.sql`. If your contribution contains database changes:

- Add your changes to `data/sql/domainregistration.sql`
- Create a corresponding UPDATE/ALTER statement in `data/sql/incremental/upd_NNNN.sql`, where 0000 is a four digit incrementing number starting with `0001`, and make sure that the statement can be safely executed more than once so the end user does not need to keep track of their latest database migration

This is how the ISPConfig project handles their database changes too, so we do it the same way in order to keep things familiar to ISPConfig users.

## How to contribute

Once you have ensure that your contribution adheres to all the above requirements, please fork the project and send a pull request. Refer to this GitHub documentation to learn how to do this: https://help.github.com/en/github/collaborating-with-issues-and-pull-requests/creating-a-pull-request-from-a-fork

## About the Openprovider API

Note that Openprovider has two API's: XML and REST. This module uses their REST API - even if the endpoint name still contains `beta`, Openprovider has ensured me that it is production-ready.
