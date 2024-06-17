<?php

namespace KvaPay\Services;

/**
 * Service factory class for API resources in the root namespace.
 *
 * @property PaymentsService $service
 */

class ServiceFactory extends AbstractServiceFactory
{
    /**
     * @var array<string, string>
     */
    private static $classMap = [
        'payment' => PaymentsService::class,
        'project' => ProjectsService::class
    ];

    /**
     * @param  string $name
     * @return string|null
     */
    protected function getServiceClass( $name)
    {
        return self::$classMap[$name] ?: null;
    }
}
