# Hydra Authorization

Permissions — the sibling of `hydra/auth`. Auth answers *who you are*;
authorization answers *what you may do*. It ships the gate that decides, but
owns no policy of its own: the rules (abilities) are application policy and live
in the app.

## The gate

A controller injects `GateInterface` and asks `allows()`/`denies()`, or enforces
a decision with `authorize()` (returns silently when allowed, throws a 403
`AuthorizationException` otherwise). Every method keys on an ability
*class-string*, not a magic name:

```php
$gate->authorize(EditNote::class, $note);   // throw 403 unless allowed
if ($gate->allows(AccessAdminPanel::class)) { /* ... */ }
```

The `Gate` composes the app-supplied ability with the *current* request's user
(read from the auth guard) — there is no "authorize as someone else" path. It
resolves the ability through the container, so abilities autowire, may inject app
services (a repository, a clock), and need no separate registration.

## Abilities (app policy)

`AbilityInterface` is the package's one extension point — the authorization twin
of validation's `RuleInterface`. An app writes typed ability classes
(`EditNote`, `AccessAdminPanel`) that inspect the user and an optional subject
and return a plain yes/no:

```php
final class EditNote implements AbilityInterface
{
    public function authorize(?AuthenticatableInterface $user, mixed $subject = null): bool
    {
        return $user !== null && $subject instanceof Note && $subject->ownerId === $user->getAuthIdentifier();
    }
}
```

The user is nullable: an unauthenticated visitor reaches abilities too, and the
ability decides what that means (almost always: deny). Passing a class-string
that isn't an `AbilityInterface` is a programming error and fails loudly, not a
silent deny.

## Enforcing an ability as middleware

For a flat, subject-less gate ("admins only"), `authorize()` can move out of the
controller and onto the route. `AuthorizeMiddleware` is abstract because a route
names its middleware as a bare class-string with no per-route arguments — so the
app extends it and fixes the ability, the same policy-in-the-app split as the
abilities themselves:

```php
final class RequireAdmin extends AuthorizeMiddleware
{
    protected function ability(): string { return AccessAdmin::class; }
}

#[RouteGroup('/admin', middleware: [AuthenticateMiddleware::class, RequireAdmin::class])]
```

Place `AuthenticateMiddleware` *outside* it, so an anonymous visitor gets a 401
login redirect rather than a 403 dead end. The check is deliberately
subject-less: subject-bound authorization (`authorize(EditNote::class, $note)`)
needs the record only the controller can load, so it stays an in-controller call.
The app skeleton shows both halves together: `AdminController` rides
`RequireAdmin` (flat role) on the route group, then calls
`gate->authorize(ManageUser::class, $target)` in `edit`/`update`/`destroy` — the
subject-bound rule (an admin may not delete or demote themselves) that depends on
*which* user is being acted on, so it can't be a bare class-string middleware.
