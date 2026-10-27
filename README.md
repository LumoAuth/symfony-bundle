# lumoauth/symfony-bundle

The LumoAuth bundle for Symfony 6.4 and 7. It does two things:

- It registers the `LumoAuth\LumoAuth` client as a service that you can autowire.
- It provides `LumoAuthAccessTokenHandler`, which lets the Symfony security component
  accept bearer tokens from LumoAuth on your API.

The bundle builds on the core SDK, [`lumoauth/lumoauth`](../lumoauth/README.md). That
package documents the namespaces, credentials, scopes and errors.

## Install

```bash
composer require lumoauth/symfony-bundle
```

The package type is `symfony-bundle`, so Symfony Flex adds it to `config/bundles.php`.
Without Flex, add it yourself:

```php
// config/bundles.php
return [
    // …
    LumoAuth\Symfony\LumoAuthBundle::class => ['all' => true],
];
```

## Configuration

The bundle reads the environment by default, so `.env` is often enough:

```dotenv
LUMOAUTH_URL=https://app.lumoauth.dev
LUMOAUTH_ORG_ID=acme-corp
LUMOAUTH_API_KEY=lmk_…
```

To set the values in YAML, create `config/packages/lumoauth.yaml`:

```yaml
lumoauth:
    url: '%env(LUMOAUTH_URL)%'
    org_id: '%env(LUMOAUTH_ORG_ID)%'
    api_key: '%env(LUMOAUTH_API_KEY)%'
```

| Key | Default | Meaning |
|---|---|---|
| `url` | `LUMOAUTH_URL`, else `https://app.lumoauth.dev` | LumoAuth URL for your region (EU: `https://eu.app.lumoauth.dev`) |
| `org_id` | `LUMOAUTH_ORG_ID` | Organization slug |
| `api_key` | `LUMOAUTH_API_KEY` | Organization API key, sent as `X-API-Key` |
| `http_client` | `null` | Service ID of a PSR-18 client. When it is `null`, the SDK finds one. |
| `user_identifier_claim` | `sub` | The userinfo claim that becomes the user identifier in Symfony |

## Use the client

Autowire `LumoAuth\LumoAuth`. The service is also available under the ID `lumoauth`.

```php
use LumoAuth\LumoAuth;

final class DocumentController
{
    public function __construct(private readonly LumoAuth $lumo) {}

    public function edit(): Response
    {
        if (!$this->lumo->permissions->check('document.edit')) {
            throw new AccessDeniedHttpException();
        }
        // …
    }
}
```

The configured client uses the organization API key. A permission check with that key is
about the user who created the key. To check a signed-in user, pass the user's LumoAuth
user ID as `userId:`. With the default `user_identifier_claim` of `sub`, that ID is the
Symfony user identifier:

```php
$lumo->permissions->check('document.edit', userId: $this->getUser()->getUserIdentifier());
```

The API key needs the `authz:check` scope for this. Without it, LumoAuth answers 403
`INSUFFICIENT_PERMISSIONS`. If you set `user_identifier_claim` to another claim, such as
`email`, the identifier is no longer a LumoAuth user ID. In that case, call LumoAuth with
the user's own access token instead:

```php
$asUser = new LumoAuth(orgId: 'acme-corp', tokenProvider: fn () => $accessToken);
$asUser->permissions->check('document.edit');
```

## Accept bearer tokens from LumoAuth

`LumoAuthAccessTokenHandler` works with the security component's `access_token`
authenticator. For each request it calls the LumoAuth userinfo endpoint with the bearer
token. It then returns a user badge whose identifier is the `sub` claim, or the claim set
in `user_identifier_claim`.

```yaml
# config/packages/security.yaml
security:
    firewalls:
        api:
            pattern: ^/api
            stateless: true
            access_token:
                token_handler: LumoAuth\Symfony\Security\LumoAuthAccessTokenHandler
```

- Your firewall's user provider loads the user by that identifier.
- The badge also carries every userinfo claim as attributes.
- An invalid or expired token causes a `BadCredentialsException`, and Symfony answers 401.
- When LumoAuth cannot be reached, the request also fails with 401.

## More

- Core SDK guide: [`lumoauth/lumoauth`](../lumoauth/README.md)
- Full guide and troubleshooting: [sdk-php README](../../README.md)

## Licence

MIT
