<?php

declare(strict_types=1);

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;

return static function (DefinitionConfigurator $definition): void {
    $rootNode = $definition->rootNode()->children();

    $rootNode->scalarNode('api_key')->isRequired()->end();
};
