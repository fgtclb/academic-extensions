<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_base" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

/*
 * This file is only for IDE / static analysis support of the deprecated class
 * alias declared in `Migrations/Code/ClassAliasMap.php`. It is never loaded at
 * runtime: the class alias is created by the `typo3/class-alias-loader` composer
 * plugin. The global `die()` guarantees the file can never be executed.
 */

namespace {
    die('Access denied: this file must never be loaded, it only aids IDEs.');
}

namespace FGTCLB\AcademicBase\Environment {
    /**
     * @deprecated since academic_base 2.4.0, will be removed in academic_base 3.0.0.
     *             Use \FGTCLB\EnvironmentStateManager\StateBuildContext instead.
     */
    class StateBuildContext extends \FGTCLB\EnvironmentStateManager\StateBuildContext {}
}
