# zenstruck/signed-url-bundle

Helpers for signing and verifying urls with support for temporary and single-use urls. Some common
use cases include:

- [Stateless Password Resets](#stateless-password-resets)
- [Stateless Email Verification](#stateless-email-verification)
- [Stateless Verified Change Email](#stateless-verified-change-email)

```php
use Zenstruck\SignedUrl\Generator;

public function sendPasswordResetEmail(User $user, Generator $generator)
{
    $resetUrl = $generator->build('password_reset_route', ['id' => $user->getId()])
        ->expires('+1 day')
        ->singleUse($user->getPassword())
    ;

    // create email with $resetUrl and send
}
```

```php
use Zenstruck\SignedUrl\Verifier;
use Zenstruck\SignedUrl\Exception\UrlVerificationFailed;

public function resetPasswordAction(User $user, Verifier $urlVerifier)
{
    try {
        $urlVerifier->verifyCurrentRequest(singleUseToken: $user->getPassword());
    } catch (UrlVerificationFailed $e) {
        $this->flashError($e->messageKey()); // safe reason to show user

        return $this->redirect(...);
    }

    // continue
}
```

## Why This Bundle?

Symfony includes a `UriSigner` (in fact, this bundle uses this) but it doesn't have out of the
box support for temporary/single-use urls. Symfony 5.2 introduced
[login links](https://symfony.com/blog/new-in-symfony-5-2-login-links) that has these features
but is restricted to these type of links only.

[`tilleuls/url-signer-bundle`](https://packagist.org/packages/tilleuls/url-signer-bundle) is
another bundle that provides expiring signed urls but not single-use (out of the box).

Additionally, this bundle provides the following features:
1. [`SignedUrl` Object](#signedurl-object) that contains metadata about the created signed url.
2. Explicit exceptions so you can know exactly why verification failed and optionally relay this
   to the user (ie _the url has already been used_ or _the url has expired_)

## Installation

```bash
composer require zenstruck/signed-url-bundle
```

**NOTE**: If not added automatically by `symfony/flex`, enable `ZenstruckSignedUrlBundle`.

## Generate

The `Zenstruck\SignedUrl\Generator` is an auto-wireable service that is used to generate signed urls
for your Symfony routes. By default, all generated urls are absolute.

### Standard Signed Urls

```php
/** @var Zenstruck\SignedUrl\Generator $generator */

$generator->generate('route1'); // http://example.com/route1?_hash=...
$generator->generate('route2', ['parameter1' => 'value']); // http://example.com/route2/value?_hash=...
```

### Temporary Urls

These urls expire (cannot be verified) after a certain time. They are also signed so cannot be tampered with.

```php
/** @var Zenstruck\SignedUrl\Generator $generator */

(string) $generator->build('route1')->expires('+1 hour'); // http://example.com/route1?__expires=...&_hash=...
(string) $generator->build('route2', ['parameter1' => 'value'])->expires('+1 hour'); // http://example.com/route2/value?__expires=...&_hash=...

// use # of seconds
(string) $generator->build('route1')->expires(3600);

// use an explicit \DateTime
(string) $generator->build('route1')->expires(new \DateTime('+1 hour'));
```

### Single-Use Urls

These urls are generated with a token that should change once the url has been used. It is up to you
to determine this token and depends on the context. A good example is a password reset. For these
urls, the token would be the current user's password. Once they successfully change their password
the token wouldn't match so the url would become invalid.

**NOTE**: The URL is first hashed with this token, then hashed again with the app-level secret
to ensure it hasn't been tampered with.

```php
/** @var Zenstruck\SignedUrl\Generator $generator */

$generator->singleUse($user->getPassword(), 'reset_password', ['id' => $user->getId()]);
```

### Combination Urls

You can create a signed, temporary, single-use URL using `Generator::build()`.

```php
/** @var Zenstruck\SignedUrl\Generator $generator */

(string) $generator->build('reset_password', ['id' => $user->getId()])
    ->expires('+1 hour')
    ->singleUse($user->getPassword())
;
```

### `SignedUrl` Object

`Generator::build()` creates a signed URL builder, calling `create()` on this returns
a `SignedUrl` object with context for the url:

```php
/** @var Zenstruck\SignedUrl\Generator $generator */

$signedUrl = $generator->build('reset_password', ['id' => $user->getId()])
    ->expires('+1 hour')
    ->singleUse($user->getPassword())
    ->create()
;

/** @var Zenstruck\SignedUrl $signedUrl */
(string) $signedUrl; // the actual URL
$signedUrl->expiresAt(); // \DateTimeImmutable
$signedUrl->isTemporary(); // true
$signedUrl->isSingleUse(); // true
```

## Verification

The `Zenstruck\SignedUrl\Verifier` is an auto-wireable service that is used to verify signed urls.

```php
use Zenstruck\SignedUrl\Exception\UrlVerificationFailed;

/** @var Zenstruck\SignedUrl\Verifier $verifier */
/** @var string $url */
/** @var Symfony\Component\HttpFoundation\Request $request */

// simple usage: return true if valid and non-expired (if applicable), false otherwise
$verifier->isVerified($url);
$verifier->isVerified($request); // can pass Symfony request object
$verifier->isCurrentRequestVerified(); // verifies the current request (fetched from RequestStack)

// try/catch usage: catch exceptions to provide better feedback to users
try {
    $verifier->verify($url);
    $verifier->verify($request); // alternative
    $verifier->verifyCurrentRequest(); // alternative
} catch (UrlVerificationFailed $e) {
    $e->url(); // the url used
    $e->getMessage(); // Internal message (ie for logging)
    $e->messageKey(); // Safe message with reason to show the user (or use with translator)
}
```

**NOTE:** See [Verification Exceptions](#verification-exceptions) for more information on
the thrown exception.

### Single-Use Verification

For validating [single-use urls](#single-use-urls), you need to pass a token to the Verifier's
verify methods:

```php
use Zenstruck\SignedUrl\Exception\UrlVerificationFailed;

/** @var Zenstruck\SignedUrl\Verifier $verifier */
/** @var string $url */
/** @var Symfony\Component\HttpFoundation\Request $request */

$verifier->isVerified($url, $user->getPassword());
$verifier->isVerified($request, $user->getPassword());
$verifier->isCurrentRequestVerified($user->getPassword());

// try/catch usage: catch exceptions to provide better feedback to users
try {
    $verifier->verify($url, $user->getPassword());
    $verifier->verify($request, $user->getPassword()); // alternative
    $verifier->verifyCurrentRequest($user->getPassword()); // alternative
} catch (UrlVerificationFailed $e) {
    $e->messageKey(); // "URL has already been used." (if failed for this reason)
}
```

#### Token Objects

The single-use token is required for both generating and verifying the url. These are likely
done in different parts of your application. To avoid duplicating the generation of your
token, it is recommended to wrap the logic into simple *token objects* that are `\Stringable`:

```php
final class ResetPasswordToken
{
    public function __construct(private User $user) {}

    public function __toString(): string
    {
        return $this->user->getPassword();
    }
}
```

Generate the url using this token object:

```php
/** @var Zenstruck\SignedUrl\Generator $generator */

$generator->singleUse(new ResetPasswordToken($user), 'reset_password', ['id' => $user->getId()]);
```

When verifying, use the token object here as well:

```php
/** @var Zenstruck\SignedUrl\Verifier $verifier */

$verifier->isVerified($url, new ResetPasswordToken($user));
$verifier->verify($url, new ResetPasswordToken($user));
$verifier->isCurrentRequestVerified(new ResetPasswordToken($user));
$verifier->verifyCurrentRequest(new ResetPasswordToken($user));
```

### Auto-Verify Routes

You can auto-verify specific routes using a routing option or attribute. Before
these controllers are called, an event listener verifies the route and throws
an `HttpException` (`403` by default) on failure. You do not have the option
to intercept and provide a friendly message to the user. Additionally, single-use
URL verification is not possible.

This feature needs to be enabled:

```yaml
# config/packages/zenstruck_signed_url.yaml

zenstruck_signed_url:
    route_verification: true
```

Add the `Zenstruck\SignedUrl\Attribute\Signed` attribute to the controller you
want auto-verified (can be added to the class to mark all methods as signed):

```php
use Zenstruck\SignedUrl\Attribute\Signed;

#[Signed]
#[Route(...)]
public function action1() {} // throws a 403 HttpException if verification fails

#[Signed(status: 404)]
#[Route(...)]
public function action1() {} // throw a 404 exception instead
```

Alternatively, a `signed` route option can be added to your route definition:

```yaml
# config/routes.yaml

action1:
    path: /action1
    options: { signed: true } # throws a 403 HttpException if verification fails

action2:
    path: /action2
    options: { signed: 404 } # throw a 404 exception instead
```

### Verification Exceptions

Verification can fail for the following reasons (in this order):
1. Signature missing or invalid (URL has been tampered with).
2. If the URL has an expiration and has expired.
3. Single-use URL has been _used_.

Each of the above reasons has a corresponding exception that can be caught separately
(all exceptions are instances of `Zenstruck\SignedUrl\Exception\UrlVerificationFailed`):

```php
use Zenstruck\SignedUrl\Exception\UrlVerificationFailed;
use Zenstruck\SignedUrl\Exception\UrlHasExpired;
use Zenstruck\SignedUrl\Exception\UrlAlreadyUsed;

/** @var Zenstruck\SignedUrl\Verifier $verifier */

try {
    $verifier->verifyCurrentRequest($user->getPassword());
} catch (UrlHasExpired $e) {
    // this exception makes the expiration available
    $e->expiredAt(); // \DateTimeImmutable
    $e->messageKey(); // "URL has expired."
    $e->url(); // the URL that failed verification
} catch (UrlAlreadyUsed $e) {
    $e->messageKey(); // "URL has already been used."
    $e->url(); // the URL that failed verification
} catch (UrlVerificationFailed $e) {
    // must be last as a "catch all"
    $e->messageKey(); // "URL Verification failed."
    $e->url(); // the URL that failed verification
}
```

## Full Default Configuration

```yaml
zenstruck_signed_url:

    # The secret key to sign urls with
    secret:               '%kernel.secret%'

    # Enable auto route verification (trigger with "signed" route option or "Zenstruck\SignedUrl\Attribute\Signed" attribute)
    route_verification:   false
```

## Cookbook

The following are pseudo-code recipes for possible use-cases for this bundle:

### Stateless Password Resets

Generate a password-reset link that has a 1 day expiration and is considered
_used_ when the password changes:

```php
/** @var \Zenstruck\SignedUrl\Generator $generator */
/** @var \Zenstruck\SignedUrl\Verifier $verifier */

// REQUEST PASSWORD RESET ACTION (GENERATE URL)
$url = $generator->build('reset_password', ['id' => $user->getId()])
    ->expires('+1 day')
    ->singleUse($user->getPassword())
    ->create()
;

// send email to user with $url

// PASSWORD RESET ACTION (VERIFY URL)
try {
    $verifier->verifyCurrentRequest($user->getPassword());
} catch (\Zenstruck\SignedUrl\Exception\UrlVerificationFailed $e) {
    $this->flashError($e->messageKey());

    return $this->redirect(...);
}

// proceed with the reset, once a new password will be set/saved, this URL will become invalid
```

### Stateless Email Verification

After a user registers, send a verification email. These emails don't expire
but are considered _used_ once `$user->isVerified() === true`. Since these
links do not expire, you'll likely want some kind of cron job that removes
users that haven't verified after a time.

```php
final class VerifyToken
{
    public function __construct(private User $user) {}

    public function __toString(): string
    {
        return $this->user->isVerified() ? 'verified' : 'unverified';
    }
}

/** @var \Zenstruck\SignedUrl\Generator $generator */
/** @var \Zenstruck\SignedUrl\Verifier $verifier */

// REGISTRATION CONTROLLER ACTION (GENERATE URL)
$url = $generator->build('verify_user', ['id' => $user->getId()])
    ->singleUse(new VerifyToken($user))
    ->create()
;

// send email to user with $url

// VERIFICATION ACTION (VERIFY URL)
try {
    $verifier->verifyCurrentRequest(new VerifyToken($user));
} catch (\Zenstruck\SignedUrl\Exception\UrlVerificationFailed $e) {
    $this->flashError($e->messageKey());

    return $this->redirect(...);
}

$user->verify(); // marks the user as verified and invalidates the URL

// save user & login user immediately or redirect to login page
```

### Stateless Verified Change Email

If your app requires all users have a verified email, a system to allow users
to _change_ their email requires verification as well. You can use this bundle
to enable this in a stateless way. First, when a user requests an email change,
send a link to the _new_ email. This link includes the _new_ email within it so when
they click it, the app knows the new _verified_ email to set.

```php
/** @var \Zenstruck\SignedUrl\Generator $generator */
/** @var \Zenstruck\SignedUrl\Verifier $verifier */

// REQUEST EMAIL CHANGE ACTION (GENERATE URL)
$url = $generator->build('reset_password', ['id' => $user->getId(), 'new-email' => $newEmailRequested])
    ->expires('+1 day')
    ->singleUse($user->getEmail()) // the user's current email
    ->create()
;

// send verification email to $newEmailRequested with $url

// EMAIL CHANGE ACTION (VERIFY URL)
try {
    $verifier->verify($request, $user->getEmail()); // the user's current email
} catch (\Zenstruck\SignedUrl\Exception\UrlVerificationFailed $e) {
    $this->flashError($e->messageKey());

    return $this->redirect(...);
}

$user->setEmail($request->query->get('new-email')); // changes the user email and invalidates the URL

// save user
```

**NOTE:** Since the new email is included in the query string, this could be considered
a [PII](https://en.wikipedia.org/wiki/Personal_data) leak (as it will appear in logs).
An option to avoid this is to encrypt/decrypt the `new-email` value.
