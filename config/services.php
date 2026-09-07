<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Lens\Bundle\MyParcelBundle\LensMyParcel;
use Lens\Bundle\MyParcelBundle\LensMyParcelShipmentStatus;
use Lens\Bundle\MyParcelBundle\Twig\Components\TrackTrace;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->defaults()
        ->autowire()
        ->autoconfigure();

    $services->set(LensMyParcel::class);
    $services->set(LensMyParcelShipmentStatus::class);

    if (class_exists(AsTwigComponent::class)) {
        $services->set(TrackTrace::class);
    }
};
