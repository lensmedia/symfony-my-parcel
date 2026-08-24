<?php

declare(strict_types=1);

namespace Lens\Bundle\MyparcelBundle;

use Symfony\Contracts\Translation\TranslatorInterface;

class LensMyParcelShipmentStatus
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    public const array STATUS = [
        1 => 'my_parcel.status.pending.concept',
        2 => 'my_parcel.status.pending.registered',

        3 => 'my_parcel.status.enroute.handed_to_carrier',
        4 => 'my_parcel.status.enroute.sorting',
        5 => 'my_parcel.status.enroute.distribution',
        6 => 'my_parcel.status.enroute.customs',

        7 => 'my_parcel.status.delivered.at_recipient',
        8 => 'my_parcel.status.delivered.ready_for_pickup',
        9 => 'my_parcel.status.delivered.package_picked_up',
        10 => 'my_parcel.status.delivered.return_shipment_ready_for_pickup',
        11 => 'my_parcel.status.delivered.return_shipment_package_picked_up',

        12 => 'my_parcel.status.printed.letter',
        13 => 'my_parcel.status.credited',
        14 => 'my_parcel.status.printed.digital_stamp',
        15 => 'my_parcel.status.printed.external_shipment',
        16 => 'my_parcel.status.expired',
        17 => 'my_parcel.status.cancelled',
        18 => 'my_parcel.status.printed.untracked_shipment',
        19 => 'my_parcel.status.delivered.at_agreed_location',

        30 => 'my_parcel.status.inactive.concept',
        31 => 'my_parcel.status.inactive.registered',
        32 => 'my_parcel.status.inactive.enroute.handed_to_carrier',
        33 => 'my_parcel.status.inactive.enroute.sorting',
        34 => 'my_parcel.status.inactive.enroute.distribution',
        35 => 'my_parcel.status.inactive.enroute.customs',
        36 => 'my_parcel.status.inactive.delivered.at_recipient',
        37 => 'my_parcel.status.inactive.delivered.ready_for_pickup',
        38 => 'my_parcel.status.inactive.delivered.package_picked_up',
    ];

    public function getStatus(int $status): string
    {
        return $this->translator->trans(self::STATUS[$status], domain: 'LensMyParcel');
    }
}
