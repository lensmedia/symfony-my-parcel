<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Lens\Bundle\MyParcelBundle\LensMyParcel;
use Lens\Bundle\MyParcelBundle\LensMyParcelShipmentStatus;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->defaults()
        ->autowire()
        ->autoconfigure();

    $services->set(LensMyParcel::class);
    $services->set(LensMyParcelShipmentStatus::class);

};
