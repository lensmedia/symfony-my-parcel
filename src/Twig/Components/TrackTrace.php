<?php

declare(strict_types=1);

namespace Lens\Bundle\MyParcelBundle\Twig\Components;

use BadMethodCallException;
use Lens\Bundle\MyParcelBundle\LensMyParcel;
use MyParcelNL\Sdk\Model\Consignment\AbstractConsignment;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(name: 'MyParcel:TrackTrace', template: '@LensMyParcel/components/track_trace.html.twig')]
class TrackTrace
{
    public string $identifier;
    public ?AbstractConsignment $parcel = null;
    public string $status = '';
    public array $history = [];
    public ?string $url = null;

    public function __construct(
        private readonly LensMyParcel $myParcel,
    ) {
    }

    public function mount(string $identifier): void
    {
        $this->identifier = $identifier;

        try {
            $this->parcel = $parcel = $this->myParcel->getParcel($identifier);
        } catch (BadMethodCallException) {
            return;
        }

        if (!$parcel) {
            return;
        }

        $this->status = $this->myParcel->getStatus($parcel);
        $this->history = $this->myParcel->getHistory($parcel);
        $this->url = $this->myParcel->getTrackTraceUrl($parcel);
    }
}
