<?php

namespace Domainregistration\Util;

use Domainregistration\Exception\AbstractException;
use Domainregistration\Util\Sentry;
use Exception;

final class ExceptionHandler
{
    const LOG_PREFIX = 'Domainregistration exception';

    /**
     * @var \app
     */
    private $app;

    /**
     * @var Sentry
     */
    private $sentry;

    /**
     * @param \app $app
     * @param Sentry $sentry
     * @return void
     */
    public function __construct($app, $sentry)
    {
        $this->app = $app;
        $this->sentry = $sentry;
    }

    /**
     * The \Throwable type hint in the docheader is too new (PHP7), but leaving it out
     * makes phpstan generate an error. So we don't import it to keep the file compatible
     * with PHP 5.4, but we add the type hint in the docheader to satisfy phpstan.
     *
     * @param AbstractException|\Throwable $exception
     * @return void
     * @throws Exception
     */
    public function handle($exception)
    {
        if (!$exception instanceof AbstractException) {
            throw $exception;
        }

        $this->logError($exception);
        $this->notifySentry($exception);
        $this->triggerInterfaceErrorAndHalt($exception);
    }

    /**
    * @param AbstractException $exception
    * @return void
    */
    private function logError($exception)
    {
        $this->app->log(sprintf(
            '%s: %s. Stack trace: %s',
            self::LOG_PREFIX,
            $exception->getMessage(),
            $exception->getTraceAsString()
        ));
    }

    /**
     * @param AbstractException $exception
     * @return void
     */
    private function notifySentry($exception)
    {
        $this->sentry->notify($exception);
    }

    /**
    * @param AbstractException $exception
    * @return void
    */
    private function triggerInterfaceErrorAndHalt($exception)
    {
        $this->app->error(sprintf(
            '%s %s',
            $exception->getFriendlyMessage(),
            'Please try again later or contact us if the problem persists.'
        ));
    }
}
