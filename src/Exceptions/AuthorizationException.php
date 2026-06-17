<?php

declare(strict_types=1);

namespace Hydra\Authorization\Exceptions;

use Hydra\Http\Exceptions\HttpException;
use Throwable;

/**
 * The authenticated user is not allowed to perform the attempted action: HTTP 403.
 *
 * The authorization counterpart to auth's AuthenticationException (401). The
 * distinction is deliberate and standard: 401 means "we don't know who you are"
 * (the guard catches it and the app sends you to log in); 403 means "we know who
 * you are and you still may not" — logging in again would not help, so this is a
 * dead end, not a redirect.
 *
 * Like every typed HTTP error in Hydra, the gate only throws it; the app's
 * outermost ErrorHandlerMiddleware renders it. How a 403 looks (a plain page, a
 * branded error view) is the app's policy, the same way csrf's
 * TokenMismatchException leaves presentation to the app.
 */
final class AuthorizationException extends HttpException
{
    public function __construct(string $message = 'This action is unauthorized.', ?Throwable $previous = null)
    {
        parent::__construct(403, $message, [], $previous);
    }
}
