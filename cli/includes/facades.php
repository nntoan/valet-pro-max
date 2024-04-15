<?php

use Illuminate\Container\Container;

class ValetProMaxFacade
{
    /**
     * The key for the binding in the container.
     */
    public static function containerKey(): string
    {
        return 'Lotus\\ValetProMax\\'.basename(str_replace('\\', '/', get_called_class()));
    }

    /**
     * Call a non-static method on the facade.
     */
    public static function __callStatic(string $method, array $parameters): mixed
    {
        $resolvedInstance = Container::getInstance()->make(static::containerKey());

        return call_user_func_array([$resolvedInstance, $method], $parameters);
    }
}

/**
 * Valet Pro Max classes
 */
class PhpExtension extends ValetProMaxFacade
{
}
class Mysql extends ValetProMaxFacade
{
}
class Mailhog extends ValetProMaxFacade
{
}
class Elasticsearch extends ValetProMaxFacade
{
}
class Varnish extends ValetProMaxFacade
{
}
class RedisService extends ValetProMaxFacade
{
}
class Rabbitmq extends ValetProMaxFacade
{
}
class RedisPhpExtension extends ValetProMaxFacade
{
}
class Memcache extends ValetProMaxFacade
{
}
class Xdebug extends ValetProMaxFacade
{
}
class Binary extends ValetProMaxFacade
{
}
class DriverConfigurator extends ValetProMaxFacade
{
}
class Docker extends ValetProMaxFacade
{
}
class PeclCustom extends ValetProMaxFacade
{
}
class Architecture extends ValetProMaxFacade
{
}
