<?php

declare(strict_types=1);

namespace LumoAuth\Symfony\Security;

use LumoAuth\Error\LumoAuthApiError;
use LumoAuth\Error\LumoAuthError;
use LumoAuth\LumoAuth;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

/**
 * Resolves a LumoAuth bearer token to a Symfony user through the userinfo
 * endpoint, for the security component's `access_token` authenticator. The
 * UserBadge carries the userinfo claims as attributes, so a user provider
 * can build the user from them without a second round trip.
 */
final class LumoAuthAccessTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(private readonly LumoAuth $lumo, private readonly string $identifierClaim = 'sub')
    {
    }

    public function getUserBadgeFrom(string $accessToken): UserBadge
    {
        try {
            $claims = $this->lumo->auth->userinfo($accessToken);
        } catch (LumoAuthApiError $e) {
            throw new BadCredentialsException('Invalid LumoAuth access token.', 0, $e);
        } catch (LumoAuthError $e) {
            throw new BadCredentialsException('LumoAuth is unreachable.', 0, $e);
        }
        $identifier = $claims[$this->identifierClaim] ?? null;
        if (!is_string($identifier) || $identifier === '') {
            throw new BadCredentialsException(sprintf('userinfo has no "%s" claim.', $this->identifierClaim));
        }

        return new UserBadge($identifier, null, $claims);
    }
}
