# zenstruck/signed-url-bundle

Helpers for signing and verifying urls with support for temporary and single-use urls. Some common
use cases include:

- [Stateless Password Resets](#stateless-password-resets)
- [Stateless Email Verification](#stateless-email-verification)
- [Stateless Verified Change Email](#stateless-verified-change-email)
- [Login Links](#login-links)

```php
public function sendPasswordResetEmail(User $user, Generator $generator)
{
    $resetUrl = $generator->builder('password_reset_route')
        ->expires('+1 day')
        ->singleUse($user->getPassword())
    ;

    // create email with $resetUrl and send
}
```

```php
public function resetPasswordAction(User $user, Verifier $urlVerifier)
{
    try {
        $urlVerifier->verifyCurrentRequest(singleUseToken: $user->getPassword());
    } catch (ExpiredUrl) {
        // flash "This url has expired, please try again" and redirect
    } catch (UrlAlreadyUsed) {
        // flash "This url has already been used, please try again" and redirect
    } catch (InvalidUrlSignature) {
        // flash "This url is invalid, please try again" and redirect
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

$generator->temporary('+1 hour', 'route1'); // http://example.com/route1?__expires=...&_hash=...
$generator->temporary('+1 hour', 'route2', ['parameter1' => 'value']); // http://example.com/route2/value?__expires=...&_hash=...

// use # of seconds
$generator->temporary(3600, 'route1');

// use an explicit \DateTime
$generator->temporary(new \DateTime('+1 hour'), 'route1');
```

### Single-Use Urls

These urls are generated with a token that should change once the url has been used. It is up to you
to determine this token and depends on the context. A good example is a password reset. For these
urls, the token would be the current user's password. Once they successfully change their password
the token wouldn't match so the url would be invalid.

**NOTE**: The URL is first hashed with this token, then hashed again with the app-level secret
to ensure it hasn't been tampered with.

```php
/** @var Zenstruck\SignedUrl\Generator $generator */

$generator->singleUse($user->getPassword(), 'reset_password', ['id' => $user->getId()]);
```

### Combination Urls

### `SignedUrl` Object

## Verification

### Single-Use Verification

#### Token Objects

The single-use token is required for both generating and verifying the url. These are likely
done in different parts of your application. To avoid duplicating the generation of your
token, it is recommended to wrap the logic up into simple *token objects* that are `\Stringable`:

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

## Full Default Configuration

## Cookbook

### Stateless Password Resets

### Stateless Email Verification

### Stateless Verified Change Email

### Login Links
