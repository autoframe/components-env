<?php
declare(strict_types=1);

namespace Autoframe\Components\Env;

use Autoframe\Components\Env\Exception\AfrEnvException;
use Autoframe\DesignPatterns\Singleton\AfrSingletonInterface;

interface AfrEnvParserInterface extends AfrSingletonInterface
{
    /**
     * @param string $sEnvLines
     * @return array
     */
    public function parseStr(string $sEnvLines): array;

    /**
     * @param string $sPath
     * @return array
     * @throws AfrEnvException
     */
    public function parseFile(string $sPath): array;

}