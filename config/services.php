<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Lens\Bundle\MyparcelBundle\LensMyparcel;
use Lens\Bundle\MyparcelBundle\LensMyParcelShipmentStatus;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->defaults()
        ->autowire()
        ->autoconfigure();

    $services->set(LensMyparcel::class);
    $services->set(LensMyParcelShipmentStatus::class);

};
