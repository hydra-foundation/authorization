<?php

declare(strict_types=1);

namespace Hydra\Authorization\Contracts;

use Hydra\Auth\Contracts\AuthenticatableInterface;

/**
 * A single authorization rule: may this user do this thing, to this subject?
 *
 * This is the package's one extension point, the authorization twin of
 * hydra/validation's RuleInterface. An ability inspects the current user (and
 * optionally a subject — the note being edited, the account being viewed) and
 * answers a plain yes/no. The phrasing of *what* the ability is lives in its
 * class name, not a string passed around: an app writes typed ability classes
 * (`EditNote`, `AccessAdminPanel`) and the gate keys on the class-string, the
 * same `::class` idiom Hydra already uses for middleware and container bindings.
 *
 * Abilities are application policy and live in the app, not this package — auth
 * cannot know what owning a note means any more than it can know your user
 * storage. Because the gate resolves them through the container, an ability is
 * free to depend on app services (a repository, a clock) by constructor
 * injection.
 *
 * The user is nullable: an unauthenticated visitor reaches abilities too, and
 * the ability decides what that means (almost always: deny by returning false).
 */
interface AbilityInterface
{
    /**
     * @param AuthenticatableInterface|null $user    the current user, or null when none is authenticated
     * @param mixed                         $subject the thing being acted on, when the ability is about one
     *
     * @return bool true to allow, false to deny
     */
    public function authorize(?AuthenticatableInterface $user, mixed $subject = null): bool;
}
