<?php

namespace App\Exceptions;

use Symfony\Component\Console\Exception\LogicException;

/**
 * Thrown if a subdomain is unknown from OVH API.
 */
class SubDomainIdException extends LogicException
{
}
