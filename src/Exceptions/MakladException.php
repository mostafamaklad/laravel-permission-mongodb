<?php
/**
 * Created by PhpStorm.
 * User: mostafamaklad
 * Date: 9/6/17
 * Time: 9:59 PM
 */

namespace Maklad\Permission\Exceptions;

use InvalidArgumentException;
use Throwable;

class MakladException extends InvalidArgumentException
{
    /**
     * MakladException constructor.
     *
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        if (config('permission.log_registration_exception')) {
            $logger = app('log');
            $logger->alert($message);
        }
    }
}
