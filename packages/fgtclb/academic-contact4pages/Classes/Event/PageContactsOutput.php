<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Event;

/**
 * Which output asked for the contacts a {@see ModifyPageContactsEvent} carries.
 *
 * The case set is fixed, so that a listener can `match` over it exhaustively.
 *
 * @api
 */
enum PageContactsOutput: string
{
    /**
     * The contacts content element.
     */
    case Plugin = 'plugin';

    /**
     * The data processor that hands the contacts to a page template.
     */
    case DataProcessor = 'data-processor';
}
