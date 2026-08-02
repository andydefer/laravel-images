<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages\Collections;

use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;
use AndyDefer\LaravelImages\Enums\ImageType;

final class ImageTypeCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct(ImageType::class);
    }

    public function hasType(ImageType $type): bool
    {
        return $this->contains($type);
    }

    public function hasAnyType(array $types): bool
    {
        foreach ($types as $type) {
            if ($this->contains($type)) {
                return true;
            }
        }

        return false;
    }

    public function hasAllTypes(array $types): bool
    {
        foreach ($types as $type) {
            if (! $this->contains($type)) {
                return false;
            }
        }

        return true;
    }

    public function toCodes(): array
    {
        return array_map(fn (ImageType $type) => $type->value, $this->toArray());
    }

    public function toLabels(): array
    {
        return array_map(fn (ImageType $type) => $type->getLabel(), $this->toArray());
    }

    public function getPrimary(): ?ImageType
    {
        $items = $this->toArray();

        return ! empty($items) ? $items[0] : null;
    }

    public function filterByMaxSize(int $maxSize): self
    {
        return $this->filter(fn (ImageType $type) => $type->getMaxSize() <= $maxSize);
    }

    public function filterByMimeType(string $mimeType): self
    {
        return $this->filter(function (ImageType $type) use ($mimeType) {
            return in_array($mimeType, $type->getAllowedMimeTypes(), true);
        });
    }

    public function getSquareTypes(): self
    {
        return $this->filter(fn (ImageType $type) => $type->isSquare());
    }

    public function getTypesWithDimensions(): self
    {
        return $this->filter(fn (ImageType $type) => $type->getDimensions() !== null);
    }

    public static function fromCodes(array $codes): self
    {
        $collection = new self;

        foreach ($codes as $code) {
            $type = ImageType::tryFrom($code);
            if ($type !== null) {
                $collection->add($type);
            }
        }

        return $collection;
    }

    public static function fromLabels(array $labels): self
    {
        $collection = new self;

        foreach ($labels as $label) {
            foreach (ImageType::cases() as $type) {
                if ($type->getLabel() === $label) {
                    $collection->add($type);
                    break;
                }
            }
        }

        return $collection;
    }
}
