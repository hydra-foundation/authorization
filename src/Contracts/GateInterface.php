<?php

declare(strict_types=1);

namespace Hydra\Authorization\Contracts;

/**
 * Decides whether the current user is allowed to do something.
 *
 * The controller-facing surface of authorization, the sibling of auth's
 * GuardInterface: a controller injects this and asks it questions
 * ({@see allows()}/{@see denies()}) or enforces a decision ({@see authorize()}).
 * It never resolves an ability or reads the user itself — the gate composes the
 * app-supplied {@see AbilityInterface} with the authenticated user from the
 * guard.
 *
 * Every method keys on an ability *class-string*, not a magic ability name:
 * `$gate->authorize(EditNote::class, $note)`. The ability is a first-class typed
 * object resolved through the container, so there is no string-to-method
 * dispatch and no separate place abilities are registered — the same deliberate
 * typed deviation auth made by taking `attempt(string, string)` over a
 * credentials array.
 *
 * Binding the interface keeps the mechanism swappable, the seam pattern shared
 * with GuardInterface, SessionInterface, and ViewInterface.
 */
interface GateInterface
{
    /**
     * Whether the current user is allowed the given ability against an optional
     * subject.
     *
     * @param class-string<AbilityInterface> $ability
     */
    public function allows(string $ability, mixed $subject = null): bool;

    /**
     * The negation of {@see allows()} — reads cleanly in a guard clause.
     *
     * @param class-string<AbilityInterface> $ability
     */
    public function denies(string $ability, mixed $subject = null): bool;

    /**
     * Enforce the ability: return silently when allowed, otherwise throw an
     * {@see \Hydra\Authorization\Exceptions\AuthorizationException} (HTTP 403).
     * The convenience verb for the common "check, or stop the request" path.
     *
     * @param class-string<AbilityInterface> $ability
     */
    public function authorize(string $ability, mixed $subject = null): void;
}
