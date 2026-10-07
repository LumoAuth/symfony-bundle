<?php

declare(strict_types=1);

namespace LumoAuth\Symfony;

use LumoAuth\LumoAuth;
use LumoAuth\Symfony\Security\LumoAuthAccessTokenHandler;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * # config/packages/lumoauth.yaml
 * lumoauth:
 *     url: '%env(LUMOAUTH_URL)%'
 *     org_id: '%env(LUMOAUTH_ORG_ID)%'
 *     api_key: '%env(LUMOAUTH_API_KEY)%'
 *
 * Then autowire `LumoAuth\LumoAuth` anywhere, and optionally authenticate
 * API requests with LumoAuth bearer tokens:
 *
 * # config/packages/security.yaml
 * security:
 *     firewalls:
 *         api:
 *             pattern: ^/api
 *             stateless: true
 *             access_token:
 *                 token_handler: LumoAuth\Symfony\Security\LumoAuthAccessTokenHandler
 */
final class LumoAuthBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->scalarNode('url')->defaultValue('%env(default:lumoauth.default_url:LUMOAUTH_URL)%')->info('LumoAuth instance URL')->end()
                ->scalarNode('org_id')->defaultValue('%env(default::LUMOAUTH_ORG_ID)%')->info('Organization slug for org-scoped calls')->end()
                ->scalarNode('api_key')->defaultValue('%env(default::LUMOAUTH_API_KEY)%')->info('Organization API key (sent as X-API-Key)')->end()
                ->scalarNode('http_client')->defaultNull()->info('Service id of a PSR-18 client; discovered when null')->end()
                ->scalarNode('user_identifier_claim')->defaultValue('sub')->info('userinfo claim used as the Symfony user identifier')->end()
            ->end();
    }

    /** @param array<string, mixed> $config */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->parameters()->set('lumoauth.default_url', LumoAuth::DEFAULT_BASE_URL);
        $services = $container->services();

        $client = $services->set(LumoAuth::class)
            ->arg('$apiKey', $config['api_key'])
            ->arg('$baseUrl', $config['url'])
            ->arg('$orgId', $config['org_id'])
            ->public();
        if ($config['http_client'] !== null) {
            $client->arg('$httpClient', $container->service($config['http_client']));
        }
        $services->alias('lumoauth', LumoAuth::class);

        $services->set(LumoAuthAccessTokenHandler::class)
            ->arg('$lumo', $container->service(LumoAuth::class))
            ->arg('$identifierClaim', $config['user_identifier_claim']);
    }
}
