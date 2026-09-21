<?php

declare(strict_types=1);

namespace FGTCLB\AcademicTestConfiguration;

/**
 * A class of an academic extension that is not final, so an XCLASS of it is a
 * warning rather than an error.
 */
class Replaceable
{
    public function name(): string
    {
        return 'replaceable';
    }
}
