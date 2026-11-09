<?php

namespace App\Exceptions;

use DomainException;

/**
 * A business rule from the SRS stopped an action (for example "Active accounts cannot be deleted").
 * The message is written for the person using the system, so controllers show it as-is.
 */
class BusinessRuleViolation extends DomainException
{
}
