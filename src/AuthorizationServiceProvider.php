<?php

declare(strict_types=1);

namespace Hydra\Authorization;

use Hydra\Auth\Contracts\GuardInterface;
use Hydra\Authorization\Contracts\GateInterface;
use Hydra\Core\Contracts\ContainerInterface;
use Hydra\Core\Providers\ServiceProvider;

/**
 * Wires the authorization package into an application.
 *
 * The package binds exactly one thing: {@see GateInterface} to the shipped
 * {@see Gate}. The gate needs the container (to resolve abilities on demand) and
 * the auth guard (to read the current user), so it cannot be plain autowired
 * from a class-string — this provider supplies that one factory and nothing
 * else.
 *
 * What it deliberately does NOT bind mirrors auth's unbound UserProviderInterface:
 * the abilities themselves. There is no ability registry here because there is
 * nothing to register — an ability is just a class the app writes and references
 * by `::class`, resolved lazily by the gate when it is actually checked. The
 * mechanism ships here; the rules stay app policy.
 *
 * Register it after AuthServiceProvider: the gate depends on the guard binding.
 */
final class AuthorizationServiceProvider extends ServiceProvider
{
    public function register(ContainerInterface $container): void
    {
        $container->singleton(GateInterface::class, function () use ($container) {
            return new Gate($container, $container->get(GuardInterface::class));
        });
    }
}
