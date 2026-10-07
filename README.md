# lumoauth/symfony-bundle

```bash
composer require lumoauth/symfony-bundle
```

```yaml
# config/packages/lumoauth.yaml
lumoauth:
    url: '%env(LUMOAUTH_URL)%'
    org_id: '%env(LUMOAUTH_ORG_ID)%'
    api_key: '%env(LUMOAUTH_API_KEY)%'
```

`LumoAuth\LumoAuth` is now autowirable:

```php
public function __construct(private LumoAuth $lumo) {}

if (!$this->lumo->permissions->check('document.edit', userId: $user->getUserIdentifier())) {
    throw $this->createAccessDeniedException();
}
```

Authenticate API requests with LumoAuth bearer tokens through the security
component's `access_token` authenticator; the badge carries the userinfo
claims so your user provider needs no second call:

```yaml
security:
    firewalls:
        api:
            pattern: ^/api
            stateless: true
            access_token:
                token_handler: LumoAuth\Symfony\Security\LumoAuthAccessTokenHandler
```
