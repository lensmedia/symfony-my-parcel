<?php

declare(strict_types=1);

namespace Lens\Bundle\MyParcelBundle;

use MyParcelNL\Sdk\Factory\ConsignmentFactory;
use MyParcelNL\Sdk\Helper\MyParcelCollection;
use MyParcelNL\Sdk\Model\Carrier\CarrierPostNL;
use MyParcelNL\Sdk\Model\Consignment\AbstractConsignment;
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
            ->setEmail($consignmentData->email);

        // Setting StreetAdditionalInfo or NumberSuffix based on strlen, this is on all sites (BAG, MyParcel, ours) different.
        // Since we only add concepts, this can always be checked/modified in Myparcel backoffice.
        if ($consignmentData->addition && strlen($consignmentData->addition) > 2) {
            $consignment->setStreetAdditionalInfo($consignmentData->addition);
        } else {
            $consignment->setNumberSuffix($consignmentData->addition);
        }

        return $consignment;
    }

    /**
     * Creates a concept within Myparcel backoffice. When you do this on dev environments, make sure you have a higher
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
     * @throws \MyParcelNL\Sdk\Exception\ApiException
     * @throws \MyParcelNL\Sdk\Exception\MissingFieldException
     * @throws \MyParcelNL\Sdk\Exception\AccountNotActiveException
     */
    public function findConsignment(string $identifier): MyParcelCollection
    {
        // Consignments by referenceId are only available by labels we created by using Myparcel SDK
        $consignments = MyParcelCollection::findByReferenceId($identifier, $this->apiKey);

        // So for older shipments, we can search all the fields (label_description) by 'q'
        // There is no other way to filter for label_description
        if ($consignments->count() === 0) {
            // Size (items per page) is required, default is 30
            return MyParcelCollection::query(
                $this->apiKey,
                [
                    'q' => $identifier,
                    'size' => 30
                ]
            );
        }

        return $consignments;
    }

    /**
     * @throws \MyParcelNL\Sdk\Exception\MissingFieldException
     * @throws \MyParcelNL\Sdk\Exception\ApiException
     * @throws \MyParcelNL\Sdk\Exception\AccountNotActiveException
     */
    public function getParcel(string $identifier): ?AbstractConsignment
    {
        $consignments = $this->findConsignment($identifier)->fetchTrackTraceData();

        return $consignments->count() > 0 ? $consignments->first() : null;
    }

    public function getStatus(AbstractConsignment $consignment): string
    {
        return $this->shipmentStatus->getStatus($consignment->getStatus());
    }

    public function getHistory(AbstractConsignment $consignment): array
    {
        $history = $consignment->getHistory();

        $out = [];

        foreach ($history as $item) {
            $entry['timestamp'] = $item['time'];
            $entry['status'] = $item['description'];
            $entry['location'] = $item['location']['name'] ?? '-';

            $out[] = $entry;
        }

        return $out;
    }

    public function getTrackTraceUrl(AbstractConsignment $consignment): ?string
    {
        return $consignment->getTrackTraceUrl();
    }

}
