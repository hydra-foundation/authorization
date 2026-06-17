<?php

declare(strict_types=1);

namespace Hydra\Authorization;

use Hydra\Authorization\Contracts\AbilityInterface;
use Hydra\Authorization\Contracts\GateInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Enforces a single ability before a route runs — the authorization counterpart
 * to auth's AuthenticateMiddleware. A denied request never reaches the
 * controller: the gate throws a 403 {@see \Hydra\Authorization\Exceptions\AuthorizationException}
 * which the app's outermost ErrorHandlerMiddleware renders.
 *
 * It is abstract on purpose. A route declares its middleware as a bare
 * class-string (`middleware: [...]`), which the container resolves with no
 * per-route arguments — so the middleware itself must already know which ability
 * to check. The app supplies that by extending this class and naming the
 * ability, the same `::class` policy-in-the-app split the gate and abilities
 * already follow:
 *
 *   final class RequireAdmin extends AuthorizeMiddleware
 *   {
 *       protected function ability(): string { return AccessAdmin::class; }
 *   }
 *
 *   #[RouteGroup('/admin', middleware: [AuthenticateMiddleware::class, RequireAdmin::class])]
 *
 * Order matters: place {@see \Hydra\Auth\AuthenticateMiddleware} OUTSIDE this one
 * so an anonymous visitor gets a 401 (and the app's login redirect) rather than a
 * 403 dead end from an ability that simply denies a null user.
 *
 * The check is deliberately subject-less. A flat ability ("is this user an
 * admin?") needs nothing but the current user, which the gate reads itself.
 * Subject-bound authorization ("may this user edit *this* note?") needs the
 * record, which only the controller can load — that stays an in-controller
 * `$gate->authorize(EditNote::class, $note)` call, not this middleware.
 */
abstract class AuthorizeMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly GateInterface $gate) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Throws a 403 when denied, which propagates past the controller; returns
        // silently when allowed, and the request continues inward.
        $this->gate->authorize($this->ability());

        return $handler->handle($request);
    }

    /**
     * The ability this middleware enforces.
     *
     * @return class-string<AbilityInterface>
     */
    abstract protected function ability(): string;
}
