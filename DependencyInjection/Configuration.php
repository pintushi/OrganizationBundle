<?php


declare(strict_types=1);

namespace Pintushi\Bundle\OrganizationBundle\DependencyInjection;

use ApiPlatform\Core\Exception\InvalidArgumentException;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Pintushi\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;

final class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('pintushi_organization');

        SettingsBuilder::append(
            $rootNode,
            [
                'wechat_app_id' => ['value' => '', 'type' => 'scalar'],
                'wechat_app_secret' => ['value' => '', 'type' => 'scalar'],
                'alipay_app_id' => ['value' => '', 'type' => 'scalar'],
                'alipay_app_secret' => ['value' => '', 'type' => 'scalar']
            ]
        );

        return $treeBuilder;
    }
}
