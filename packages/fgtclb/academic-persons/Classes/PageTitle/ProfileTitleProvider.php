<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\PageTitle;

use FGTCLB\AcademicPersons\Controller\ProfileController;
use FGTCLB\AcademicPersons\Domain\Model\Profile;
use TYPO3\CMS\Core\PageTitle\AbstractPageTitleProvider;

/**
 * Concrete page title provider implementation based on {@see AbstractPageTitleProvider},
 * providing simplified `setTitle()` method and a advanced `setFromProfile()` method to
 * allow format based setting possible.
 *
 * Used in {@see ProfileController::detailAction()} to set page title for displaced profile.
 */
final class ProfileTitleProvider extends AbstractPageTitleProvider
{
    public const DETAIL_PAGE_TITLE_FORMAT = '%%TITLE%% %%FIRST_NAME%% %%MIDDLE_NAME%% %%LAST_NAME%%';

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * Set page title based on Profile values using the specified format,
     * supporting placeholder with syntax `%%UPPER_CASED_PROFILE_FIELD%%`
     * converted to camel-cased model getter `getUpperCasedProfileField()`
     * to retrieve replacement value.
     *
     * Note: Non-existing model getter will be not replaced and does not throw or log any errors.
     */
    public function setFromProfile(Profile $profile, string $format = self::DETAIL_PAGE_TITLE_FORMAT): void
    {
        // replace all `%%` surrounded values with model fields
        $title = (string)preg_replace_callback(
            pattern: '/%%([\w\d]+)%%/',
            callback: static function (array $matches) use ($profile): string {
                $getterName = 'get' . str_replace('_', '', ucwords(mb_strtolower($matches[1]), '_'));
                return method_exists($profile, $getterName)
                    ? trim($profile->{$getterName}(), ' ')
                    : $matches[0];
            },
            subject: $format,
        );
        // remove all leading and tailing spaces
        $title = trim($title, ' ');
        // ensure keeping only single spaces in content (replacing multi spaces with single space)
        $title = (string)preg_replace('/[[:blank:]]+/', ' ', $title);
        $this->setTitle($title);
    }
}
