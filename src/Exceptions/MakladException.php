<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: mostafamaklad
 * Date: 9/6/17
 * Time: 9:59 PM
 */

namespace Maklad\Permission\Exceptions;

use InvalidArgumentException;
use Maklad\Permission\Helpers;
use Throwable;

/**
 * Class MakladException
 * @package Maklad\Permission\Exceptions
 */
class MakladException extends InvalidArgumentException
{
    protected $helpers;
    /**
     * MakladException constructor.
     *
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct($message = '', $code = 0, Throwable $previous = null)
    {
        $this->helpers = new Helpers();
        parent::__construct($message, $code, $previous);

        if ($this->helpers->config('permission.log_registration_exception')) {
            $logger = $this->helpers->app('log');
            $logger->alert($message);
        }
    }
}
