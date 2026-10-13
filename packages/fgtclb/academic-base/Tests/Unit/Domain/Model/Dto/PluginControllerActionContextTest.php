<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Unit\Domain\Model\Dto;

use FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContext;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * The context object is handed to views and to the controller action events of every academic
 * extension, so it is read by third party code that cannot check what it was built from. Whether
 * it answers or raises therefore depends only on the request attributes an extbase plugin
 * happens to carry, which is what is pinned here.
 *
 * `EXT:academic_persons` carries its own, older copy of this class with its own functional test
 * ({@see \FGTCLB\AcademicPersons\Tests\Functional\Domain\Model\Dto\PluginControllerActionContextTest}),
 * which covers the plain pass-through of request, site, language and settings of that class. Here
 * the branching, the delegation and the raising are covered instead.
 */
final class PluginControllerActionContextTest extends UnitTestCase
{
    /**
     * Views reach for the content object to read the plugin record, and the attribute is set by
     * the extbase bootstrap only - a plugin dispatched from anywhere else has no content object.
     */
    #[Test]
    public function getContentObjectRendererReturnsTheContentObjectOfTheRequest(): void
    {
        $contentObjectRenderer = $this->createStub(ContentObjectRenderer::class);
        $subject = $this->subject((new ServerRequest())->withAttribute('currentContentObject', $contentObjectRenderer));

        $this->assertSame($contentObjectRenderer, $subject->getContentObjectRenderer());
    }

    #[Test]
    public function getContentObjectRendererReturnsNullWithoutAContentObject(): void
    {
        $this->assertNull($this->subject(new ServerRequest())->getContentObjectRenderer());
    }

    #[Test]
    public function getSiteReturnsTheSiteOfTheRequest(): void
    {
        $site = $this->createStub(Site::class);
        $subject = $this->subject((new ServerRequest())->withAttribute('site', $site));

        $this->assertSame($site, $subject->getSite());
    }

    #[Test]
    public function getSiteReturnsNullWithoutASite(): void
    {
        $this->assertNull($this->subject(new ServerRequest())->getSite());
    }

    /**
     * The backend stores a `NullSite` for page 0 and for a page outside any site. It implements
     * `SiteInterface` but is no `Site`, and it means "no site", which is what the getter answers.
     */
    #[Test]
    public function getSiteReturnsNullForTheNullSiteOfARequestOutsideAnySite(): void
    {
        $subject = $this->subject((new ServerRequest())->withAttribute('site', new NullSite()));

        $this->assertNull($subject->getSite());
    }

    /**
     * The attribute name is not reserved, and a listener reading the context is not in a
     * position to handle a `TypeError`.
     */
    #[Test]
    public function getSiteReturnsNullForAForeignAttributeValue(): void
    {
        $subject = $this->subject((new ServerRequest())->withAttribute('site', new \stdClass()));

        $this->assertNull($subject->getSite());
    }

    #[Test]
    public function getLanguageReturnsTheLanguageOfTheRequest(): void
    {
        $language = $this->createStub(SiteLanguage::class);
        $subject = $this->subject((new ServerRequest())->withAttribute('language', $language));

        $this->assertSame($language, $subject->getLanguage());
    }

    #[Test]
    public function getLanguageReturnsNullWithoutALanguage(): void
    {
        $this->assertNull($this->subject(new ServerRequest())->getLanguage());
    }

    #[Test]
    public function getLanguageReturnsNullForAForeignAttributeValue(): void
    {
        $subject = $this->subject((new ServerRequest())->withAttribute('language', new \stdClass()));

        $this->assertNull($subject->getLanguage());
    }

    /**
     * A plugin renders in the frontend and in the backend preview alike, and listeners branch on
     * it, so the request type has to survive unaltered.
     *
     * These two are all there is: `ApplicationType` has no other case on TYPO3 v12 and v13, the
     * versions this branch supports. A plugin context is never built in the install tool anyway.
     *
     * @return \Generator<string, array{0: int, 1: ApplicationType}>
     */
    public static function applicationTypes(): \Generator
    {
        yield 'frontend' => [SystemEnvironmentBuilder::REQUESTTYPE_FE, ApplicationType::FRONTEND];
        yield 'backend' => [SystemEnvironmentBuilder::REQUESTTYPE_BE, ApplicationType::BACKEND];
    }

    #[DataProvider('applicationTypes')]
    #[Test]
    public function getApplicationTypeReflectsTheRequestType(int $requestType, ApplicationType $expected): void
    {
        $subject = $this->subject((new ServerRequest())->withAttribute('applicationType', $requestType));

        $this->assertSame($expected, $subject->getApplicationType());
    }

    /**
     * The getter that raises instead of answering with null, as
     * {@see \FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContextInterface::getApplicationType()}
     * states. It is core behaviour and not caught on purpose: a request without that attribute
     * did not come through a TYPO3 application.
     */
    #[Test]
    public function getApplicationTypeThrowsWithoutARequestType(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1606222812);

        $this->subject(new ServerRequest())->getApplicationType();
    }

    /**
     * The second exception the interface states: a type no academic plugin is rendered with.
     */
    #[Test]
    public function getApplicationTypeThrowsForATypeThatIsNeitherFrontendNorBackend(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1678875015);

        $this->subject(
            (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_CLI)
        )->getApplicationType();
    }

    #[Test]
    public function getExtbaseRequestParametersReturnsTheParametersOfTheRequest(): void
    {
        $parameters = $this->createExtbaseRequestParameters();
        $subject = $this->subject((new ServerRequest())->withAttribute('extbase', $parameters));

        $this->assertSame($parameters, $subject->getExtbaseRequestParameters());
    }

    /**
     * The attribute name is not reserved, so anything can sit under it - and everything that
     * reads it downstream expects an `ExtbaseRequestParameters` or nothing.
     */
    #[Test]
    public function getExtbaseRequestParametersReturnsNullForAForeignAttributeValue(): void
    {
        $subject = $this->subject((new ServerRequest())->withAttribute('extbase', new \stdClass()));

        $this->assertNull($subject->getExtbaseRequestParameters());
    }

    /**
     * @return \Generator<string, array{0: non-empty-string, 1: string}>
     */
    public static function delegatingGetters(): \Generator
    {
        yield 'plugin name' => ['getPluginName', 'Pi1'];
        yield 'controller name' => ['getControllerName', 'Profile'];
        yield 'controller object name' => ['getControllerObjectName', 'FGTCLB\\AcademicPersons\\Controller\\ProfileController'];
        yield 'action name' => ['getActionName', 'detail'];
        yield 'controller extension key' => ['getControllerExtensionKey', 'academic_persons'];
        yield 'controller extension name' => ['getControllerExtensionName', 'AcademicPersons'];
    }

    /**
     * @param non-empty-string $getterName
     */
    #[DataProvider('delegatingGetters')]
    #[Test]
    public function theExtbaseRequestParametersAreDelegatedTo(string $getterName, string $expected): void
    {
        $subject = $this->subject(
            (new ServerRequest())->withAttribute('extbase', $this->createExtbaseRequestParameters())
        );

        $this->assertSame($expected, $subject->{$getterName}());
    }

    /**
     * The counterpart of the functional test's "attribute is missing" case: an attribute that is
     * there but unusable must not reach the getters either, and none of them may raise - an event
     * listener reading the context is not in a position to handle a `TypeError`.
     *
     * @param non-empty-string $getterName
     */
    #[DataProvider('delegatingGetters')]
    #[Test]
    public function theDelegatingGettersReturnNullForAForeignAttributeValue(string $getterName): void
    {
        $subject = $this->subject((new ServerRequest())->withAttribute('extbase', new \stdClass()));

        $this->assertNull($subject->{$getterName}());
    }

    private function subject(ServerRequestInterface $request): PluginControllerActionContext
    {
        return new PluginControllerActionContext($request, []);
    }

    private function createExtbaseRequestParameters(): ExtbaseRequestParameters
    {
        $parameters = new ExtbaseRequestParameters();
        $parameters->setPluginName('Pi1');
        $parameters->setControllerExtensionName('AcademicPersons');
        // The mapping is what the request builder resolves the object name from - setting the
        // controller name without it silently blanks the object name again.
        $parameters->setControllerAliasToClassNameMapping(
            ['Profile' => 'FGTCLB\\AcademicPersons\\Controller\\ProfileController']
        );
        $parameters->setControllerName('Profile');
        $parameters->setControllerActionName('detail');
        return $parameters;
    }
}
