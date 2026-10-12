<?php

declare(strict_types=1);

namespace Lens\Bundle\MyParcelBundle;

readonly class LensMyParcelParcel
{
    public function __construct(
        public int $id,
        public int $status,
        public array $history = [],
        public ?string $url = null,
    ) {
    }
}
