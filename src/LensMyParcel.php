<?php

declare(strict_types=1);

namespace Lens\Bundle\MyParcelBundle;

use MyParcelNL\Sdk\Factory\ConsignmentFactory;
use MyParcelNL\Sdk\Helper\MyParcelCollection;
use MyParcelNL\Sdk\Model\Carrier\CarrierPostNL;
use MyParcelNL\Sdk\Model\Consignment\AbstractConsignment;
use MyParcelNL\Sdk\Model\MyParcelRequest;
use SensitiveParameter;

class LensMyParcel
{
    /** @var string ApiKey */
    private string $apiKey;

    public function __construct(
        #[SensitiveParameter]
        string $apiKey,
        private readonly LensMyParcelShipmentStatus $shipmentStatus,
    ) {
        $this->apiKey = $apiKey;
    }

    /**
     * @throws \Exception
     */
    public function createConsignment(LensMyParcelConsignmentData $consignmentData): AbstractConsignment
    {
        $consignment = (ConsignmentFactory::createByCarrierId(CarrierPostNL::ID))
            ->setApiKey($this->apiKey)
            ->setReferenceIdentifier($consignmentData->reference)
            ->setLabelDescription($consignmentData->reference)

            // Address, setCountry() must be called before setPostalCode().
            ->setCountry($consignmentData->country)
            ->setStreet($consignmentData->street)
            ->setNumber($consignmentData->streetNumber)
            ->setCity($consignmentData->city)
            ->setPostalCode($consignmentData->zipCode)

            // Recipient
            ->setPerson($consignmentData->recipient)
            ->setCompany($consignmentData->company)
            ->setEmail($consignmentData->email)

            // Setting a standard weight (in grams), default is 1kg set by creating a shipment in their backoffice.
            // Creating a concept by their sdk does not auto include a weight, so that required value is missing.
            ->setPhysicalProperties([
                'weight' => 1000
            ]);

        // Setting StreetAdditionalInfo or NumberSuffix based on strlen, this is on all sites (BAG, MyParcel, ours) different.
        // Since we only add concepts, this can always be checked/modified in MyParcel backoffice.
        if ($consignmentData->addition && strlen($consignmentData->addition) > 2) {
            $consignment->setStreetAdditionalInfo($consignmentData->addition);
        } else {
            $consignment->setNumberSuffix($consignmentData->addition);
        }

        return $consignment;
    }

    /**
     * Creates a concept within MyParcel backoffice. When you do this on dev environments, make sure you have a higher
     * number on reference than already existing. This is so we don't have any duplicates or create concepts with the
     * same reference id (e.g. LM12345).
     *
     * @throws \MyParcelNL\Sdk\Exception\MissingFieldException
     * @throws \MyParcelNL\Sdk\Exception\ApiException
     * @throws \MyParcelNL\Sdk\Exception\AccountNotActiveException
     * @throws \Exception
     */
    public function createConcept(LensMyParcelConsignmentData $consignmentData): void
    {
        $consignment = $this->createConsignment($consignmentData);

        new MyParcelCollection()->addConsignment($consignment)->createConcepts();
    }

    /**
     * @return array<int, array<string, mixed>>
     *
     * @throws \MyParcelNL\Sdk\Exception\ApiException
     * @throws \MyParcelNL\Sdk\Exception\MissingFieldException
     * @throws \MyParcelNL\Sdk\Exception\AccountNotActiveException
     */
    public function findShipments(string $identifier): array
    {
        $shipments = $this->filterShipments(
            $this->requestShipments(['reference_identifier' => $identifier]),
            $identifier,
        );

        if ($shipments) {
            return $shipments;
        }

        return $this->filterShipments(
            $this->requestShipments(['q' => $identifier, 'size' => 30]),
            $identifier,
        );
    }

    /**
     * @throws \MyParcelNL\Sdk\Exception\MissingFieldException
     * @throws \MyParcelNL\Sdk\Exception\ApiException
     * @throws \MyParcelNL\Sdk\Exception\AccountNotActiveException
     */
    public function getParcel(string $identifier): ?LensMyParcelParcel
    {
        $shipment = $this->findShipments($identifier)[0] ?? null;

        if (!$shipment) {
            return null;
        }

        $id = (int) $shipment['id'];
        $trackTrace = $this->requestTrackTraces([$id])[$id] ?? [];

        return new LensMyParcelParcel(
            id: $id,
            status: (int) $shipment['status'],
            history: $trackTrace['history'] ?? [],
            url: $trackTrace['link_tracktrace'] ?? null,
        );
    }

    public function getStatus(LensMyParcelParcel $parcel): string
    {
        return $this->shipmentStatus->getStatus($parcel->status);
    }

    public function getHistory(LensMyParcelParcel $parcel): array
    {
        $out = [];

        foreach ($parcel->history as $item) {
            $entry['timestamp'] = $item['time'];
            $entry['status'] = $item['description'];
            $entry['location'] = $item['location']['name'] ?? '-';

            $out[] = $entry;
        }

        return $out;
    }

    public function getTrackTraceUrl(LensMyParcelParcel $parcel): ?string
    {
        return $parcel->url;
    }

    /**
     * @return array<int, array<string, mixed>>
     *
     * @throws \MyParcelNL\Sdk\Exception\ApiException
     * @throws \MyParcelNL\Sdk\Exception\MissingFieldException
     * @throws \MyParcelNL\Sdk\Exception\AccountNotActiveException
     */
    private function requestShipments(array $query): array
    {
        $request = (new MyParcelRequest())
            ->setRequestParameters($this->apiKey)
            ->setQuery($query)
            ->sendRequest('GET', MyParcelRequest::REQUEST_TYPE_SHIPMENTS);

        return $request->getResult('data.shipments') ?? [];
    }

    /**
     * @param int[] $ids
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws \MyParcelNL\Sdk\Exception\ApiException
     * @throws \MyParcelNL\Sdk\Exception\MissingFieldException
     * @throws \MyParcelNL\Sdk\Exception\AccountNotActiveException
     */
    private function requestTrackTraces(array $ids): array
    {
        $request = (new MyParcelRequest())
            ->setRequestParameters($this->apiKey)
            ->sendRequest('GET', 'tracktraces/'.implode(';', $ids));

        return array_column($request->getResult('data.tracktraces') ?? [], null, 'shipment_id');
    }

    /**
     * @param array<int, array<string, mixed>> $shipments
     *
     * @return array<int, array<string, mixed>>
     */
    private function filterShipments(array $shipments, string $identifier): array
    {
        return array_values(array_filter(
            $shipments,
            static fn (array $shipment): bool => ($shipment['options']['label_description'] ?? null) === $identifier
                || ($shipment['reference_identifier'] ?? null) === $identifier,
        ));
    }
}
