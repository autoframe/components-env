<?php

namespace Autoframe\Components\Env;

use Autoframe\Components\Arr\Export\AfrArrExportArrayAsStringInterface;
use Autoframe\Components\Env\Exception\AfrEnvException;
use Autoframe\Components\FileSystem\OverWrite\AfrOverWriteInterface;
use Autoframe\Components\FileSystem\Traversing\AfrDirTraversingFileListInterface;

interface AfrEnvInterface
{
    /**
     * Immutability refers if the .env is allowed to overwrite existing environment variables.
     * If you to overwrite existing environment variables, use $bMutableOverwrite = true
     * @param bool $bMutableOverwrite
     * @param bool $bRegisterPutEnv
     * @return self
     * @throws AfrEnvException
     */
    public function registerEnv(bool $bMutableOverwrite = false, bool $bRegisterPutEnv = false): self;

    /**
     * Run $oEnv->setWorkDir(__DIR__)->readEnv() or $oEnv->readEnvPhpFile(path)
     * @param string $sKey
     * @param null $mFallback
     * @return array|mixed|null
     * @throws AfrEnvException
     */
    public function getEnv(string $sKey = '', $mFallback = null);

    /**
     * Set load env dir and cache dir
     * @param string $sDir
     * @return $this
     * @throws AfrEnvException
     */
    public function setWorkDir(string $sDir): self;

    /**
     * @param string $sKey
     * @param $mData
     * @return $this
     */
    public function setInlineEnvVar(string $sKey, $mData): self;

    /**
     * @param string $sFilePath
     * @return $this
     * @throws AfrEnvException
     */
    public function readEnvPhpFile(string $sFilePath): self;

    /**
     * @param int $iCacheSeconds
     * @param array $aExtraEnvDirsFiles
     * @return $this
     * @throws AfrEnvException
     */
    public function readEnv(int $iCacheSeconds, array $aExtraEnvDirsFiles = []): self;

    /**
     * @return $this
     */
    public function flush(): self;

    /**
     * @return bool
     * @throws AfrEnvException
     */
    public function isProduction(): bool;

    /**
     * @return bool
     * @throws AfrEnvException
     */
    public function isStaging(): bool;

    /**
     * @return bool
     * @throws AfrEnvException
     */
    public function isLocal(): bool;

    /**
     * @return bool
     * @throws AfrEnvException
     */
    public function isDev(): bool;

    /**
     * @param array $aKeys
     * @return AfrEnvValidatorInterface
     */
    public function required(array $aKeys): AfrEnvValidatorInterface;

    public function ifPresent(array $aKeys): AfrEnvValidatorInterface;

    /**
     * @param array $aKeys
     * @return AfrEnvValidatorInterface
     */
    public function unrequire(array $aKeys): AfrEnvValidatorInterface;

    /**
     * @param AfrEnvParserInterface|null $oEnvParser
     * @return AfrEnvParserInterface
     */
    public function xetAfrEnvParser(AfrEnvParserInterface $oEnvParser = null): AfrEnvParserInterface;

    /**
     * @param AfrEnvValidatorInterface|null $oValidator
     * @return AfrEnvValidatorInterface
     */
    public function xetAfrEnvValidator(AfrEnvValidatorInterface $oValidator = null): AfrEnvValidatorInterface;

    /**
     * @param AfrDirTraversingFileListInterface|null $oFileList
     * @return AfrDirTraversingFileListInterface
     */
    public function xetFileList(AfrDirTraversingFileListInterface $oFileList = null): AfrDirTraversingFileListInterface;

    /**
     * @param AfrOverWriteInterface|null $oOverWrite
     * @return AfrOverWriteInterface
     */
    public function xetOverWrite(AfrOverWriteInterface $oOverWrite = null): AfrOverWriteInterface;

    /**
     * @param AfrArrExportArrayAsStringInterface|null $oExportArray
     * @return AfrArrExportArrayAsStringInterface
     */
    public function xetExportArray(AfrArrExportArrayAsStringInterface $oExportArray = null): AfrArrExportArrayAsStringInterface;
}