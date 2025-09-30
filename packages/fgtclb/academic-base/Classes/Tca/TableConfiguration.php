<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tca;

use FGTCLB\AcademicBase\Tca\Exception\TableDoesNotExistInTcaException;

/**
 * Small wrapper for reading TCA with v12/v13 support, should
 * be replaced with TcaSchemaFactory when TYPO3 v12 support is
 * dropped.
 *
 * @internal to be used only within academic extension and not part of public api.
 */
final class TableConfiguration
{
    /**
     * @param array<string, mixed> $rawConfiguration
     */
    private function __construct(
        public readonly array $rawConfiguration,
        public readonly string $tableName,
        public readonly ?string $createdAtFieldName,
        public readonly ?string $updatedAtFieldName,
        public readonly ?string $startTimeFieldName,
        public readonly ?string $endTimeFieldName,
        public readonly ?string $deletedFieldName,
        public readonly ?string $disabledFieldName,
        public readonly ?string $languageFieldName,
        public readonly ?string $translationSourceFieldName,
        public readonly ?string $transOrigPointerFieldName,
        public readonly ?string $transOrigDiffSourceFieldName,
        public readonly ?string $origUidFieldName,
    ) {}

    public static function create(string $tableName): self
    {
        return self::createFromGlobalsTcaArray($tableName);
        // @todo Add createFromTcaSchemaFactory for TYPO3 v13+
    }

    private static function createFromGlobalsTcaArray(string $tableName): self
    {
        $tableConfiguration = $GLOBALS['TCA'][$tableName] ?? null;
        if ($tableConfiguration === null) {
            throw new TableDoesNotExistInTcaException(
                sprintf(
                    'No table TCA configuration determinable for table "%s", cannot create "%s".',
                    $tableName,
                    self::class,
                ),
                1759201469,
            );
        }
        $ctrl = $tableConfiguration['ctrl'] ?? [];
        $enableColumns = $ctrl['enablecolumns'] ?? [];
        $createdAtFieldName = $ctrl['crdate'] ?? null;
        $updatedAtFieldName = $ctrl['tstamp'] ?? null;
        $startTimeFieldName = $enableColumns['starttime'] ?? null;
        $endTimeFieldName = $enableColumns['endtime'] ?? null;
        $deletedFieldName = $ctrl['delete'] ?? null;
        $disabledFieldName = $enableColumns['disabled'] ?? null;
        $languageFieldName = $ctrl['languageField'] ?? null;
        $translationSourceFieldName = $ctrl['translationSource'] ?? null;
        $transOrigPointerFieldName = $ctrl['transOrigPointerField'] ?? null;
        $transOrigDiffSourceFieldName = $ctrl['transOrigDiffSourceField'] ?? null;
        $origUidFieldName = $ctrl['origUid'] ?? null;
        return new self(
            rawConfiguration: $tableConfiguration,
            tableName: $tableName,
            createdAtFieldName: $createdAtFieldName,
            updatedAtFieldName: $updatedAtFieldName,
            startTimeFieldName: $startTimeFieldName,
            endTimeFieldName: $endTimeFieldName,
            deletedFieldName: $deletedFieldName,
            disabledFieldName: $disabledFieldName,
            languageFieldName: $languageFieldName,
            translationSourceFieldName: $translationSourceFieldName,
            transOrigPointerFieldName: $transOrigPointerFieldName,
            transOrigDiffSourceFieldName: $transOrigDiffSourceFieldName,
            origUidFieldName: $origUidFieldName,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function ctrl(): array
    {
        return $this->rawConfiguration['ctrl'] ?? [];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function columns(): array
    {
        return $this->rawConfiguration['columns'] ?? [];
    }
}
