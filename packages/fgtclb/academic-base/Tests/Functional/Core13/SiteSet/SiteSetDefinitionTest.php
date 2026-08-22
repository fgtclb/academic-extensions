<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Functional\Core13\SiteSet;

use FGTCLB\AcademicBase\Tests\Functional\AbstractAcademicBaseTestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Site\Set\SetDefinition;
use TYPO3\CMS\Core\Site\Set\SetRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Pins what the sets of this extension declare, and that the file they point at is
 * there.
 *
 * `pagets:` is a string the core resolves at runtime and `file_exists()`-guards
 * (`TsConfigTreeBuilder::getSitePageTsConfigTree()`), so a typo in it is not an error,
 * it is a site that silently gets no page TSconfig from this extension.
 *
 * TYPO3 v12 has no `Classes/Site/Set/` at all - site sets arrived in v13.1
 * (Feature: #103437) - so this class lives in a `Core13/` folder rather than merely
 * carrying the group attribute: the group keeps PHPUnit from running it, and the
 * folder is what keeps PHPStan from analysing it against the v12 core, which does not
 * know `SetRegistry` or `SetDefinition`.
 *
 * There is deliberately no test that asserts the *delivery* through a site set here, as
 * there is for `academic_bite_jobs`: the one file this extension ships is imported by
 * `Configuration/page.tsconfig` as well, which TYPO3 includes for the whole installation
 * whatever the site configuration says. A test that wrote a site naming the set would be
 * green with a broken `pagets:` too, and would therefore not test what its name claims.
 */
#[Group('not-core-12')]
final class SiteSetDefinitionTest extends AbstractAcademicBaseTestCase
{
    private const AGGREGATE_SET = 'fgtclb/academic-base';
    private const COMPONENT_SET = 'fgtclb/academic-base-ctype-group';

    #[Test]
    public function componentSetPointsAtTheRegisteredPageTsConfigFile(): void
    {
        $set = $this->getSetRegistry()->getSet(self::COMPONENT_SET);

        $this->assertNotNull($set, sprintf('The set "%s" is not registered.', self::COMPONENT_SET));
        $this->assertSame(
            'EXT:academic_base/Configuration/TSconfig/CTypeGroup/page.tsconfig',
            $set->pagets,
        );
        $this->assertFileExists(GeneralUtility::getFileAbsFileName((string)$set->pagets));
        $this->assertSetCarriesNoTypoScript($set);
    }

    /**
     * The aggregate carries no payload of its own on purpose: it delivers through the
     * component set, and a second `pagets:` would parse the same file twice.
     */
    #[Test]
    public function aggregateSetDependsOnTheComponentSetAndCarriesNoPayload(): void
    {
        $set = $this->getSetRegistry()->getSet(self::AGGREGATE_SET);

        $this->assertNotNull($set, sprintf('The set "%s" is not registered.', self::AGGREGATE_SET));
        $this->assertContains(self::COMPONENT_SET, $set->dependencies);
        $this->assertSetCarriesNoTypoScript($set);
        $this->assertFileDoesNotExist(
            GeneralUtility::getFileAbsFileName((string)$set->pagets),
            'The aggregate set must not carry a page TSconfig of its own.',
        );
    }

    /**
     * A set that declares no `typoscript:` does not get `null`: the core defaults it to
     * the set folder itself (`YamlSetDefinitionProvider::createDefinition()`) and reads
     * whatever it finds there. "Ships no TypoScript" therefore means the set folder holds
     * none of the three files the static mechanism looks for.
     */
    private function assertSetCarriesNoTypoScript(SetDefinition $set): void
    {
        $path = rtrim(GeneralUtility::getFileAbsFileName((string)$set->typoscript), '/') . '/';
        foreach (['constants.typoscript', 'setup.typoscript', 'include_static_file.txt'] as $fileName) {
            $this->assertFileDoesNotExist(
                $path . $fileName,
                sprintf('The set "%s" carries TypoScript: %s', $set->name, $fileName),
            );
        }
    }

    private function getSetRegistry(): SetRegistry
    {
        $setRegistry = $this->get(SetRegistry::class);
        $this->assertInstanceOf(SetRegistry::class, $setRegistry);

        return $setRegistry;
    }
}
