<?php

declare(strict_types=1);

namespace Keboola\SynapseTransformation\Exception;

use Keboola\CommonExceptions\ApplicationExceptionInterface;

class UnexpectedColumnType extends \Exception implements ApplicationExceptionInterface
{

}
