<?php
declare(strict_types=1);

namespace Unit;

use Autoframe\Components\Env\AfrEnv;

use Autoframe\Components\Env\Exception\AfrEnvException;
use PHPUnit\Framework\TestCase;

class AfrEnvEmptyTest extends TestCase
{

    /**
     * @test
     */
    public function AfrEnvEmptyTest(): void
    {
        $oEnv = AfrEnv::getInstance();
        $this->assertSame(null,  $oEnv->getEnv('APP_ENV'));
        $this->assertSame(true,  $oEnv->isDev());

        $oEnv->setInlineEnvVar('APP_ENV', 'LOCAL');
        $this->assertSame('LOCAL',  $oEnv->getEnv('APP_ENV'));
    }


}