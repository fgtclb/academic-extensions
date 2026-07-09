<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_base" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

/*
 * Backward-compatibility class alias for the environment build context DTO.
 *
 * `\FGTCLB\AcademicBase\Environment\StateBuildContext` has been removed from
 * EXT:academic_base and replaced by `\FGTCLB\EnvironmentStateManager\StateBuildContext`.
 * Because that DTO is carried by-type through the public PSR-14 event
 * `\FGTCLB\AcademicPersons\Service\Event\ModifyProfileCommandEnvironmentStateBuildContextForFrontendUserEvent`,
 * the old name is kept as an alias so existing event listeners keep working. The
 * alias is deprecated and will be removed together with this map in academic_base 3.0.0.
 *
 * Only the plain value object is aliased on purpose: the remaining environment /
 * state-manager classes stay real (deprecated) classes so their TYPO3 core-version
 * aware dependency injection keeps resolving to the correct implementation.
 */
return [
    'FGTCLB\\AcademicBase\\Environment\\StateBuildContext' => \FGTCLB\EnvironmentStateManager\StateBuildContext::class,
];
