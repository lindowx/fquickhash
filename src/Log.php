<?php

namespace Lindowx\FQuickHash;

use Psr\Log\LoggerInterface;

class Log
{
    /**
     * @var LoggerInterface
     */
    protected static $logger;

    public static function setLogger(LoggerInterface $logger)
    {
        self::$logger = $logger;
    }

    public static function __callStatic($name, $arguments)
    {
        if (! empty(self::$logger)) {
            return call_user_func_array([self::$logger, $name], $arguments);
        }
    }
}
