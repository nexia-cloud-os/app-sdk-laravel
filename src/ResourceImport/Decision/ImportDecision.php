<?php

declare(strict_types=1);

namespace Nexia\ResourceImport\Decision;

/**
 * Something about this file that only a person can settle.
 *
 * Not an error and not a rejection. A grade's level is NOT NULL and no HR
 * export carries one; a manager named in no row and no directory resolves
 * nowhere. Neither is anything wrong with the file, and calling them rejections
 * would tell the user their data is bad when the truth is that it is
 * incomplete.
 *
 * A closed set of shapes, deliberately. The host renders these, and a free-form
 * bag would mean the host rendering whatever an App put in it — with no basis
 * on which to lay it out, validate a reply, or refuse one. Each subclass is a
 * shape the host knows how to draw and how to check, and an App that needs
 * another adds it here where both sides can see it.
 *
 * The host does not interpret what a decision *means*. It renders the label the
 * App supplied, collects a reply in the shape declared, verifies the reply
 * against that shape, and hands it back. Which attribute a settled value fills
 * is the recipe's business; why it was needed is the App's.
 */
abstract readonly class ImportDecision
{
    /**
     * @param  string  $key  how the recipe refers to this decision
     * @param  string  $labelKey  i18n key for the question, resolved by the host
     *                            against the owning App's catalog
     * @param  array<string, string|int>  $labelParams  interpolation for the label
     * @param  bool  $blocking  whether approval is withheld until this is settled.
     *
     *         A grade level does not block: it arrives already filled in with its
     *         basis stated, and holding the button would make someone confirm a
     *         field they can see is right. A reference that resolves nowhere
     *         does, because there is no proposal to check
     */
    public function __construct(
        public string $key,
        public string $labelKey,
        public array $labelParams = [],
        public bool $blocking = false,
    ) {}

    /** The shape identifier the host renders and validates against. */
    abstract public function type(): string;

    /**
     * The decision as the card draws it.
     *
     * @return array<string, mixed>
     */
    abstract public function toArray(): array;
}
