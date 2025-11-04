<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Core12\Environment;

use FGTCLB\AcademicBase\Environment\EnvironmentBuilderInterface;
use FGTCLB\AcademicBase\Environment\Exception\SiteConfigCouldNotBeDetermined;
use FGTCLB\AcademicBase\Environment\StateBuildContext;
use FGTCLB\AcademicBase\Environment\StateInterface;
use Symfony\Component\DependencyInjection\Attribute\Exclude;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\TypoScriptAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Environment builder compatible with TYPO3 v12.
 *
 * Note that `#[Exclude]` is used intentionally to avoid automatic early compiling into the
 * dependency injection container leading to missing class and other issues for not related
 * TYPO3 version. TYPO3 version aware configuration is handled and re_enabled within the
 * `EXT:academic_base/Configuration/Services.php` file.
 *
 * @internal only to be used within `EXT:academic_*` extensions and not part of public API.
 */
#[Exclude]
final class FrontendEnvironmentBuilder implements EnvironmentBuilderInterface
{
    public function __construct(
        private readonly SiteFinder $siteFinder,
    ) {}

    /**
     * Build environment configured by passed $buildContext.
     */
    public function build(StateBuildContext $stateBuildContext): StateInterface
    {
        $state = new State();
        $site = $this->determineSiteConfig($stateBuildContext);
        $siteLanguage = $this->determineSiteLanguage($stateBuildContext, $site);
        $state = $this->createRequest($stateBuildContext, $state, $site, $siteLanguage);
        $state = $this->createTypoScriptFrontendController($stateBuildContext, $state, $site, $siteLanguage);
        return $state;
    }

    private function createRequest(StateBuildContext $stateBuildContext, State $state, Site $site, SiteLanguage $siteLanguage): State
    {
        $request = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('site', $site)
            ->withAttribute('siteLanguage', $siteLanguage);
        return $state->withRequest($request);
    }

    private function createTypoScriptFrontendController(StateBuildContext $stateBuildContext, State $state, Site $site, SiteLanguage $siteLanguage): State
    {
        $request = $state->request() ?? new ServerRequest();
        $context = GeneralUtility::makeInstance(Context::class);
        // Ensure frontend user authentication in request
        // @todo Consider if frontend user authentication data may be set through StateBuildContext.
        $frontendUser = GeneralUtility::makeInstance(FrontendUserAuthentication::class);
        $frontendUser->start($request);
        $this->unpackFrontendUserConfiguration($frontendUser);
        $request = $request->withAttribute('frontend.user', $frontendUser);
        // Ensure template parsing by setting TypoScriptAspect::$forcedTemplateParsing (required for TypoScript setup initialization)
        $typoScriptAspect = GeneralUtility::makeInstance(TypoScriptAspect::class, true);
        $context->setAspect('typoscript', $typoScriptAspect);
        // Bootstrap TypoScriptFrontendController
        $controller = GeneralUtility::makeInstance(
            TypoScriptFrontendController::class,
            $context,
            $site,
            $siteLanguage,
            new PageArguments(
                pageId: $stateBuildContext->pageId ?? $site->getRootPageId(),
                pageType: '0',
                routeArguments: [],
            ),
        );
        $request = $request->withAttribute('frontend.controller', $controller);
        $controller->determineId($request);
        $controller->calculateLinkVars([]);
        $request = $controller->getFromCache($request);
        $controller->releaseLocks();
        $controller->newCObj($request);
        if (!$controller->sys_page instanceof PageRepository) {
            $controller->sys_page = GeneralUtility::makeInstance(PageRepository::class);
        }
        return $state
            ->withRequest($request)
            ->withTypoScriptFrontendController($controller)
            ->withTypoScriptAspect($typoScriptAspect);
    }

    /**
     * @param StateBuildContext $buildContext
     * @return Site
     * @throws SiteConfigCouldNotBeDetermined
     */
    private function determineSiteConfig(StateBuildContext $buildContext): Site
    {
        $pageId = $buildContext->pageId;
        if ($pageId === null || $pageId <= 0) {
            $allSiteConfigs = $this->siteFinder->getAllSites();
            if (count($allSiteConfigs) > 1) {
                throw new SiteConfigCouldNotBeDetermined(
                    'No pageId or pageId=0 given and multiple site configuration found.',
                    1762255738,
                );
            }
            if ($allSiteConfigs === []) {
                throw new SiteConfigCouldNotBeDetermined(
                    'No pageId or pageId=0 given and no existing site configuration found.',
                    1762255830,
                );
            }
            return $allSiteConfigs[array_key_first($allSiteConfigs)];
        }
        try {
            return $this->siteFinder->getSiteByPageId($pageId);
        } catch (SiteNotFoundException $e) {
            throw new SiteConfigCouldNotBeDetermined(
                sprintf(
                    'Could not find SiteConfiguration for pageId=%s',
                    $buildContext->pageId,
                ),
                1762255989,
                $e,
            );
        }
    }

    /**
     * Determine the SiteLanguage to use based on the state build context, if available. Otherwise default language
     * is returned as language to use.
     */
    private function determineSiteLanguage(StateBuildContext $buildContext, Site $site): SiteLanguage
    {
        if ($buildContext->languageId === null) {
            return $site->getDefaultLanguage();
        }
        return $site->getLanguageById($buildContext->languageId);
    }

    /**
     * Helper method to unpack the serialized frontend user configuration.
     */
    private function unpackFrontendUserConfiguration(FrontendUserAuthentication $frontendUserAuthentication): void
    {
        if (!isset($frontendUserAuthentication->user['uc'])) {
            return;
        }
        $userConfiguration = unserialize($frontendUserAuthentication->user['uc'], ['allowed_classes' => false]);
        if (!is_array($userConfiguration)) {
            return;
        }
        $frontendUserAuthentication->uc = $userConfiguration;
    }
}
