<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Unit\Settings;

use FGTCLB\AcademicBase\Settings\TcaValidationMerger;
use FGTCLB\AcademicBase\Settings\Validation;
use FGTCLB\AcademicBase\Settings\ValidationSet;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * The merger is what the TCA files of an extension hand their table array to. Two
 * things have to hold: the fragment it builds is keyed by column, and merging it
 * leaves everything the fragment does not name untouched.
 */
final class TcaValidationMergerTest extends UnitTestCase
{
    /**
     * The result is merged into `$GLOBALS['TCA']`, so the array key is the column name
     * - the `fieldName` of the validation, not the key it is registered under. Those
     * two are deliberately different here, because a set is keyed by validation
     * identifier and nothing enforces that it equals the column.
     */
    #[Test]
    public function theTcaConfigIsKeyedByTheFieldNameOfEachValidation(): void
    {
        $validationSet = new ValidationSet(
            identifier: 'profile',
            validations: [
                'firstName' => $this->validation('first_name', ['type' => 'input', 'required' => true]),
                'lastName' => $this->validation('last_name', ['type' => 'input', 'max' => 60]),
            ],
        );

        $this->assertSame(
            [
                'columns' => [
                    'first_name' => ['config' => ['type' => 'input', 'required' => true]],
                    'last_name' => ['config' => ['type' => 'input', 'max' => 60]],
                ],
            ],
            (new TcaValidationMerger())->toTcaTableConfig($validationSet),
        );
    }

    /**
     * A validation may only exist to attach an Extbase validator, in which case it has
     * nothing to say about TCA. Skipping it matters beyond tidiness: writing an empty
     * `config` into `$GLOBALS['TCA']` would drop the column's own configuration.
     */
    #[Test]
    public function aValidationWithoutTcaConfigContributesNothing(): void
    {
        $validationSet = new ValidationSet(
            identifier: 'profile',
            validations: [
                'firstName' => $this->validation('first_name', []),
                'lastName' => $this->validation('last_name', ['type' => 'input']),
            ],
        );

        $this->assertSame(
            ['columns' => ['last_name' => ['config' => ['type' => 'input']]]],
            (new TcaValidationMerger())->toTcaTableConfig($validationSet),
        );
    }

    /**
     * Not even an empty `columns` key: the caller merges the result into the table TCA,
     * and `['columns' => []]` is not the same neutral element as `[]` for every merge
     * strategy.
     */
    #[Test]
    public function aSetWithoutAnyTcaConfigProducesAnEmptyArray(): void
    {
        $validationSet = new ValidationSet(
            identifier: 'profile',
            validations: ['firstName' => $this->validation('first_name', [])],
        );

        $this->assertSame([], (new TcaValidationMerger())->toTcaTableConfig($validationSet));
    }

    /**
     * TCA files call this for every table the extension knows, whether or not an
     * integrator configured a validation set for it - the settings lookup answers
     * `null` for an unknown identifier, and that has to be a no-op here.
     */
    #[Test]
    public function aMissingSetProducesAnEmptyArray(): void
    {
        $this->assertSame([], (new TcaValidationMerger())->toTcaTableConfig(null));
    }

    /**
     * Two validations of one set naming the same column silently collapse into the last
     * one - the earlier `config` is replaced, not merged. Pinned because the set is
     * keyed by validation identifier, so nothing stops a configuration from doing it.
     */
    #[Test]
    public function twoValidationsOnOneColumnKeepTheLastTcaConfig(): void
    {
        $validationSet = new ValidationSet(
            identifier: 'profile',
            validations: [
                'firstName' => $this->validation('first_name', ['type' => 'input']),
                'firstNameAgain' => $this->validation('first_name', ['type' => 'text']),
            ],
        );

        $this->assertSame(
            ['columns' => ['first_name' => ['config' => ['type' => 'text']]]],
            (new TcaValidationMerger())->toTcaTableConfig($validationSet),
        );
    }

    /**
     * The merge is a recursive overrule: the fragment wins for the keys it names and
     * nothing else changes. A column's `label`, its other `config` keys and the columns
     * the set does not mention all have to survive - a plain replace of `config` would
     * wipe `max` here, and a plain replace of `columns` would wipe `last_name`.
     */
    #[Test]
    public function mergingOverrulesTheNamedConfigKeysAndKeepsTheRest(): void
    {
        $tableTca = [
            'ctrl' => ['label' => 'first_name'],
            'columns' => [
                'first_name' => [
                    'label' => 'First name',
                    'config' => ['type' => 'input', 'max' => 60, 'required' => false],
                ],
                'last_name' => [
                    'label' => 'Last name',
                    'config' => ['type' => 'input'],
                ],
            ],
        ];
        $validationSet = new ValidationSet(
            identifier: 'profile',
            validations: [
                'firstName' => $this->validation('first_name', ['required' => true, 'minitems' => 1]),
            ],
        );

        $this->assertSame(
            [
                'ctrl' => ['label' => 'first_name'],
                'columns' => [
                    'first_name' => [
                        'label' => 'First name',
                        'config' => ['type' => 'input', 'max' => 60, 'required' => true, 'minitems' => 1],
                    ],
                    'last_name' => [
                        'label' => 'Last name',
                        'config' => ['type' => 'input'],
                    ],
                ],
            ],
            (new TcaValidationMerger())->merge($tableTca, $validationSet),
        );
    }

    #[Test]
    public function mergingAMissingSetReturnsTheTableUnchanged(): void
    {
        $tableTca = ['columns' => ['first_name' => ['config' => ['type' => 'input']]]];

        $this->assertSame($tableTca, (new TcaValidationMerger())->merge($tableTca, null));
    }

    /**
     * @param array<string, mixed> $tcaConfig
     */
    private function validation(string $fieldName, array $tcaConfig): Validation
    {
        return new Validation(
            identifier: 'validation of ' . $fieldName,
            fieldName: $fieldName,
            required: false,
            disabled: false,
            readOnly: false,
            validatorClassNames: [],
            tcaConfig: $tcaConfig,
        );
    }
}
