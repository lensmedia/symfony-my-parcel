<?php

declare(strict_types=1);

namespace Lens\Bundle\MyparcelBundle;

class LensMyParcelConsignmentData
{
    public string $reference;

    // Address
    public string $country;
    public string $street;
    public int $streetNumber;
    public ?string $addition = null;
    public string $zipCode;
    public string $city;

    // Personal
    public string $recipient;
    public ?string $company = null;
    public string $email;

}
