<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Service;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use TYPO3\HtmlSanitizer\InitiatorInterface;

#[AsAlias(id: ProfileRichTextSanitizerInterface::class)]
final readonly class ProfileRichTextSanitizer implements ProfileRichTextSanitizerInterface, InitiatorInterface
{
    private const RICH_TEXT_PROPERTIES = [
        'coreCompetences',
        'teachingArea',
        'supervisedDoctoralThesis',
        'supervisedThesis',
        'miscellaneous',
    ];

    public function __construct(
        private ProfileRichTextSanitizerBuilder $builder,
    ) {
    }

    public function supports(string $propertyName): bool
    {
        return in_array($propertyName, self::RICH_TEXT_PROPERTIES, true);
    }

    public function sanitize(string $value): string
    {
        return trim($this->builder->build()->sanitize($value, $this));
    }

    public function __toString(): string
    {
        return self::class;
    }
}
