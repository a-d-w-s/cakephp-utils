<?php
declare(strict_types=1);

namespace ADWS\Utils\Exception;

use Cake\Http\Exception\ForbiddenException;

class SignatureException extends ForbiddenException
{
}
