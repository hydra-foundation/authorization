<?php

declare(strict_types=1);

namespace Hydra\Authorization;

use Hydra\Auth\Contracts\GuardInterface;
use Hydra\Authorization\Contracts\AbilityInterface;
use Hydra\Authorization\Contracts\GateInterface;
use Hydra\Authorization\Exceptions\AuthorizationException;
use Hydra\Core\Contracts\ContainerInterface;
use InvalidArgumentException;

/**
 * The shipped {@see GateInterface}: composes an app-supplied ability with the
 * user the auth guard reports for this request.
 *
 * It owns no policy of its own. Given an ability class-string it resolves the
 * ability through the container — so abilities autowire and may inject app
 * services, and need no separate registration — then hands it the current user
 * and the subject and returns the verdict. Authorization is therefore always
 * read against the *current* request's user; there is no "authorize as someone
 * else" path, by design.
 *
 * Resolving an ability runs it through the container ({@see ContainerInterface::get()}),
 * which autowires an unbound class-string. Passing a class-string that is not an
 * {@see AbilityInterface} is a programming error, not a runtime authorization
 * outcome, so it fails loudly with an {@see InvalidArgumentException} rather than
 * silently denying.
 */
final class Gate implements GateInterface
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly GuardInterface $guard,
    ) {}

    public function allows(string $ability, mixed $subject = null): bool
    {
        return $this->resolve($ability)->authorize($this->guard->user(), $subject);
    }

    public function denies(string $ability, mixed $subject = null): bool
    {
        return !$this->allows($ability, $subject);
    }

    public function authorize(string $ability, mixed $subject = null): void
    {
        if (!$this->allows($ability, $subject)) {
            throw new AuthorizationException;
        }
    }

    /**
     * @param class-string<AbilityInterface> $ability
     */
    private function resolve(string $ability): AbilityInterface
    {
        $resolved = $this->container->get($ability);

        if (!$resolved instanceof AbilityInterface) {
            throw new InvalidArgumentException(sprintf(
                '%s must implement %s to be used as an ability.',
                $ability,
                AbilityInterface::class,
            ));
        }

        return $resolved;
    }
}
