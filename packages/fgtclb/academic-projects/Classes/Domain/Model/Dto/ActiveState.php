<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Domain\Model\Dto;

enum ActiveState: string
{
    case ALL = 'all';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';

    public static function default(): self
    {
        return self::ALL;
    }

    public static function tryFromDefault(string $value): self
    {
        return self::tryFrom($value) ?? self::ALL;
    }

    /**
     * @return string[]
     */
    public static function values(): array
    {
        $values = [];
        foreach (self::cases() as $case) {
            $values[] = $case->value;
        }
        return $values;
    }
}
