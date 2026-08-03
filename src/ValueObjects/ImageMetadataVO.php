<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages\ValueObjects;

use AndyDefer\DomainStructures\Abstracts\AbstractValueObject;
use AndyDefer\DomainStructures\Utils\StrictAssociative;

/**
 * Value Object for image metadata.
 *
 * Encapsulates image metadata such as alt text, caption, order, and primary flag.
 * Provides immutable accessors and mutation methods that return new instances.
 *
 * @example
 * $metadata = new ImageMetadataVO(['alt_text' => 'Profile photo']);
 * $altText = $metadata->getAltText();
 * $updated = $metadata->withOrder(5);
 */
final class ImageMetadataVO extends AbstractValueObject
{
    public function __construct(
        private readonly array $data = [],
    ) {}

    /**
     * Returns the alternative text for the image.
     */
    public function getAltText(): ?string
    {
        return $this->data['alt_text'] ?? null;
    }

    /**
     * Returns the caption of the image.
     */
    public function getCaption(): ?string
    {
        return $this->data['caption'] ?? null;
    }

    /**
     * Returns the order position of the image.
     */
    public function getOrder(): ?int
    {
        return $this->data['order'] ?? null;
    }

    /**
     * Returns whether the image is marked as primary.
     */
    public function getIsPrimary(): bool
    {
        return $this->data['is_primary'] ?? false;
    }

    /**
     * Returns whether the image supports inverse mode.
     */
    public function getInverseMode(): bool
    {
        return $this->data['inverse_mode'] ?? false;
    }

    /**
     * Creates a new instance with the specified order.
     *
     * @param  int  $order  The new order position
     * @return self A new instance with the updated order
     */
    public function withOrder(int $order): self
    {
        $data = $this->data;
        $data['order'] = $order;

        return new self($data);
    }

    /**
     * Creates a new instance with the specified primary flag.
     *
     * @param  bool  $primary  The new primary flag value
     * @return self A new instance with the updated primary flag
     */
    public function withPrimary(bool $primary): self
    {
        $data = $this->data;
        $data['is_primary'] = $primary;

        return new self($data);
    }

    /**
     * Creates a new instance with the specified alt text.
     *
     * @param  string  $altText  The new alt text
     * @return self A new instance with the updated alt text
     */
    public function withAltText(string $altText): self
    {
        $data = $this->data;
        $data['alt_text'] = $altText;

        return new self($data);
    }

    /**
     * Creates a new instance with the specified caption.
     *
     * @param  string  $caption  The new caption
     * @return self A new instance with the updated caption
     */
    public function withCaption(string $caption): self
    {
        $data = $this->data;
        $data['caption'] = $caption;

        return new self($data);
    }

    /**
     * Creates a new instance with inverse mode enabled.
     *
     * @return self A new instance with inverse mode enabled
     */
    public function withInverseMode(): self
    {
        $data = $this->data;
        $data['inverse_mode'] = true;

        return new self($data);
    }

    /**
     * Creates a new instance with inverse mode disabled.
     *
     * @return self A new instance with inverse mode disabled
     */
    public function withoutInverseMode(): self
    {
        $data = $this->data;
        $data['inverse_mode'] = false;

        return new self($data);
    }

    /**
     * Returns the metadata as an array.
     *
     * @return array<string, mixed> The metadata array
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Checks if the metadata is empty.
     *
     * @return bool True if no metadata is present
     */
    public function isEmpty(): bool
    {
        return empty($this->data);
    }

    /**
     * Returns the metadata as a StrictAssociative object.
     *
     * @return StrictAssociative The metadata as a typed associative object
     */
    public function getValue(): StrictAssociative
    {
        return StrictAssociative::from($this->data);
    }
}
