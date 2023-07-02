<?php
declare(strict_types=1);

namespace Autoframe\Components\Env;

use Autoframe\Components\Arr\Export\AfrArrExportArrayAsStringClass;
use Autoframe\Components\Arr\Export\AfrArrExportArrayAsStringInterface;
use Autoframe\Components\Env\Exception\AfrEnvException;
use Autoframe\Components\FileSystem\Exception\AfrFileSystemException;
use Autoframe\Components\FileSystem\OverWrite\AfrOverWriteClass;
use Autoframe\Components\FileSystem\OverWrite\AfrOverWriteInterface;
use Autoframe\Components\FileSystem\Traversing\AfrDirTraversingFileListInterface;
use Autoframe\Components\FileSystem\Traversing\AfrDirTraversingFileListClass;
use Autoframe\DesignPatterns\Singleton\AfrSingletonAbstractClass;

class AfrEnv extends AfrSingletonAbstractClass implements AfrEnvInterface
{
    protected AfrEnvParserInterface $oEnvParser;
    protected AfrDirTraversingFileListInterface $oFileList;
    protected AfrOverWriteInterface $oOverWrite;
    protected AfrArrExportArrayAsStringInterface $oExportArray;
    protected AfrEnvValidatorInterface $oValidator;
    protected array $aEnvData = [];
    protected array $aEnvDirsFiles = [];
    protected bool $bValidated = false;
    protected string $sWorkDir;
    protected string $sCacheFile;

    /**
     * Immutability refers if the .env is allowed to overwrite existing environment variables.
     * If you to overwrite existing environment variables, use $bMutableOverwrite = true
     * @param bool $bMutableOverwrite
     * @param bool $bRegisterPutEnv
     * @return self
     * @throws AfrEnvException
     */
    public function registerEnv(
        bool $bMutableOverwrite = false,
        bool $bRegisterPutEnv = false
    ): self
    {
        if (empty($this->aEnvData)) {
            throw new AfrEnvException(
                'No env settings to register! ' .
                'Run $oEnv->setWorkDir(__DIR__)->readEnv() or $oEnv->readEnvPhpFile(path)'
            );
        }
        $this->validateAll();

        $aGetEnv = $bRegisterPutEnv ? (array)getenv() : [];
        foreach ($this->aEnvData as $sKey => $mVal) {
            $sKey = strtoupper($sKey);
            if (
                $bMutableOverwrite === false && (
                    isset($_SERVER[$sKey]) ||
                    isset($_ENV[$sKey]) ||
                    isset($aGetEnv[$sKey])
                )
            ) {
                continue;
            }
            $_SERVER[$sKey] = $_ENV[$sKey] = $mVal;
            if ($bRegisterPutEnv) {
                if (is_bool($mVal)) {
                    $sVal = $mVal ? 'TRUE' : 'FALSE';
                } elseif ($mVal === null) {
                    $sVal = 'NULL';
                } elseif (is_array($mVal)) {
                    $sVal = implode(',', $mVal);
                } else {
                    $sVal = (string)$mVal;
                }
                putenv("$sKey=$sVal");
            }
        }
        return $this;
    }

    /**
     * Run $oEnv->setWorkDir(__DIR__)->readEnv() or $oEnv->readEnvPhpFile(path)
     * @param string $sKey
     * @param null $mFallback
     * @return array|mixed|null
     * @throws AfrEnvException
     */
    public function getEnv(string $sKey = '', $mFallback = null)
    {
        $this->validateAll();
        if (strlen($sKey)) {
            return $this->aEnvData[$sKey] ?? $mFallback;
        }
        return $this->aEnvData;
    }

    /**
     * Set load env dir and cache dir
     * @param string $sDir
     * @return self
     * @throws AfrEnvException
     */
    public function setWorkDir(string $sDir): self
    {
        if (!is_dir($sDir)) {
            throw new AfrEnvException('Unable to set the ENV word dir: ' . $sDir);
        }
        $this->sWorkDir = rtrim($sDir, '\/');
        return $this;
    }

    /**
     * @param string $sKey
     * @param $mData
     * @return self
     */
    public function setInlineEnvVar(string $sKey, $mData): self
    {
        $this->bValidated = false;
        $this->aEnvData[$sKey] = $mData;
        return $this;
    }

    /**
     * @param string $sFilePath
     * @return self
     * @throws AfrEnvException
     */
    public function readEnvPhpFile(string $sFilePath): self
    {
        $this->bValidated = false;
        if (!is_file($sFilePath)) {
            if (
                !empty($this->sWorkDir) &&
                is_file($this->sWorkDir . DIRECTORY_SEPARATOR . $sFilePath)
            ) {
                $sFilePath = $this->sWorkDir . DIRECTORY_SEPARATOR . $sFilePath;
            } else {
                throw new AfrEnvException('Unable to find the php array env file: ' . $sFilePath);
            }
        }
        $aData = include $sFilePath;
        if (!is_array($aData) || count($aData) < 1) {
            throw new AfrEnvException('Unable to load an empty php array env file: ' . $sFilePath);
        }
        $this->aEnvData = array_merge($this->aEnvData, $aData);
        return $this;
    }

    /**
     * iCacheSeconds is the number of cache seconds before expire. Use zero for no cache
     * aExtraEnvDirsFiles to add extra env directories and .env files
     * @param int $iCacheSeconds
     * @param array $aExtraEnvDirsFiles
     * @return self
     * @throws AfrEnvException
     */
    public function readEnv(int $iCacheSeconds, array $aExtraEnvDirsFiles = []): self
    {
        $this->bValidated = false;
        $this->aEnvDirsFiles = array_merge([$this->sWorkDir], $aExtraEnvDirsFiles);

        if (
            $iCacheSeconds > 0 &&
            is_file($this->getCacheFileName()) &&
            filemtime($this->getCacheFileName()) + $iCacheSeconds > time()
        ) {
            $this->readEnvPhpFile($this->getCacheFileName());
            return $this;
        }

        foreach ($this->aEnvDirsFiles as $sSources) {
            if (file_exists($sSources)) {
                if (is_dir($sSources)) {
                    $this->loadDir($sSources);
                } elseif (is_file($sSources)) {
                    $this->loadEnvFile($sSources);
                } else {
                    throw new AfrEnvException('Unable to verify the existence of : ' . $sSources);
                }
            }
        }
        if ($iCacheSeconds > 0) {
            $this->setCache();
        }
        return $this;
    }

    /**
     * @return $this
     */
    public function flush(): self
    {
        if (is_file($this->getCacheFileName())) {
            unlink($this->getCacheFileName());
        }
        $this->bValidated = false;
        $this->aEnvData = $this->aEnvDirsFiles = [];
        $this->sWorkDir = $this->sCacheFile = '';
        unset($this->oValidator);
        return $this;
    }

    /**
     * @return bool
     * @throws AfrEnvException
     */
    public function isProduction(): bool
    {
        return $this->getEnv('APP_ENV') === 'PRODUCTION';
    }

    /**
     * @return bool
     * @throws AfrEnvException
     */
    public function isStaging(): bool
    {
        return $this->getEnv('APP_ENV') === 'STAGING';
    }

    /**
     * @return bool
     * @throws AfrEnvException
     */
    public function isLocal(): bool
    {
        return $this->getEnv('APP_ENV') === 'LOCAL';
    }

    /**
     * @return bool
     * @throws AfrEnvException
     */
    public function isDev(): bool
    {
        if ($this->isProduction() || $this->isStaging() || $this->isLocal()) {
            return false;
        }
        if (isset($this->aEnvData['APP_ENV'])) {
            return $this->aEnvData['APP_ENV'] === 'DEV';
        }
        return true;
    }


    /**
     * @return void
     */
    protected function setCache(): void
    {
        $sHeader = '<?php /* ' . gmdate('D, d M Y H:i:s') . ' GMT ->loadCache: ' .
            str_replace('*/', '* /', print_r($this->aEnvDirsFiles, true)) .
            "*/ \n return ";
        $this->xetOverWrite()->overWriteFile(
            $this->getCacheFileName(),
            $sHeader . $this->xetExportArray()->exportPhpArrayAsString($this->aEnvData),
        );
    }

    /**
     * @return string
     */
    protected function getCacheFileName(): string
    {
        if (empty($this->sCacheFile)) {
            $this->sCacheFile = $this->sWorkDir .
                DIRECTORY_SEPARATOR .
                'AfrEnvCache' .
                //'_' . substr(md5(serialize($this->aEnvDirsFiles)), 10, 8) .
                '.env.php';
        }
        return $this->sCacheFile;
    }

    /**
     * @param string $sDir = __DIR__
     * @return self
     * @throws AfrEnvException
     */
    protected function loadDir(string $sDir): self
    {
        try {
            $aEnvsFiles = $this->xetFileList()->getDirFileList($sDir, ['env']);
            if (!is_array($aEnvsFiles)) {
                throw new AfrFileSystemException('Unable to load *.env from ' . $sDir);
            }
            $sDir = rtrim($sDir, '\/') . DIRECTORY_SEPARATOR;
            foreach ($aEnvsFiles as $sFile) {
                $this->loadEnvFile($sDir . $sFile);
            }
        } catch (AfrFileSystemException|AfrEnvException $e) {
            throw new AfrEnvException($e->getMessage());
        }
        return $this;
    }

    /**
     * @param string $sEnvFilePath
     * @return self
     * @throws AfrEnvException
     */
    protected function loadEnvFile(string $sEnvFilePath): self
    {
        $this->aEnvData = array_merge(
            $this->aEnvData,
            $this->xetAfrEnvParser()->parseFile($sEnvFilePath)
        );
        return $this;
    }

    /**
     * @param array $aKeys
     * @return AfrEnvValidatorInterface
     */
    public function required(array $aKeys): AfrEnvValidatorInterface
    {
        $this->bValidated = false;
        return $this->xetAfrEnvValidator()->required($aKeys);
    }

    public function ifPresent(array $aKeys): AfrEnvValidatorInterface
    {
        $this->bValidated = false;
        return $this->xetAfrEnvValidator()->ifPresent($aKeys);
    }

    /**
     * @param array $aKeys
     * @return AfrEnvValidatorInterface
     */
    public function unrequire(array $aKeys): AfrEnvValidatorInterface
    {
        $this->bValidated = false;
        return $this->xetAfrEnvValidator()->unrequire($aKeys);
    }

    /**
     * @return void
     * @throws AfrEnvException
     */
    protected function validateAll(): void
    {

        if (!$this->bValidated) {
            $this->bValidated = $this->xetAfrEnvValidator()->validateAll($this->aEnvData);
        }
        if (!$this->bValidated) {
            throw new AfrEnvException('Validation for ENV settings failed!');
        }
    }

    /**
     * @param AfrEnvParserInterface|null $oEnvParser
     * @return AfrEnvParserInterface
     */
    public function xetAfrEnvParser(AfrEnvParserInterface $oEnvParser = null): AfrEnvParserInterface
    {
        if ($oEnvParser) {
            $this->oEnvParser = $oEnvParser;
        } elseif (empty($this->oEnvParser)) {
            $this->oEnvParser = AfrEnvParserClass::getInstance();
        }
        return $this->oEnvParser;
    }

    /**
     * @param AfrEnvValidatorInterface|null $oValidator
     * @return AfrEnvValidatorInterface
     */
    public function xetAfrEnvValidator(AfrEnvValidatorInterface $oValidator = null): AfrEnvValidatorInterface
    {
        $bInit = false;
        if ($oValidator) {
            $this->oValidator = $oValidator;
            $this->bValidated = false;
            $bInit = true;
        } elseif (empty($this->oValidator)) {
            $this->oValidator = new AfrEnvValidatorClass();
            $this->bValidated = false;
            $bInit = true;
        }
        if ($bInit) {
            $this->ifPresent(['APP_ENV'])->allowedValues([
                'DEV',
                'PRODUCTION',
                'STAGING',
                'LOCAL',
            ]);
        }
        return $this->oValidator;
    }

    /**
     * @param AfrDirTraversingFileListInterface|null $oFileList
     * @return AfrDirTraversingFileListInterface
     */
    public function xetFileList(AfrDirTraversingFileListInterface $oFileList = null): AfrDirTraversingFileListInterface
    {
        if ($oFileList) {
            $this->oFileList = $oFileList;
        } elseif (empty($this->oFileList)) {
            $this->oFileList = AfrDirTraversingFileListClass::getInstance();
        }
        return $this->oFileList;
    }

    /**
     * @param AfrOverWriteInterface|null $oOverWrite
     * @return AfrOverWriteInterface
     */
    public function xetOverWrite(AfrOverWriteInterface $oOverWrite = null): AfrOverWriteInterface
    {
        if ($oOverWrite) {
            $this->oOverWrite = $oOverWrite;
        } elseif (empty($this->oOverWrite)) {
            $this->oOverWrite = AfrOverWriteClass::getInstance();
        }
        return $this->oOverWrite;
    }

    /**
     * @param AfrArrExportArrayAsStringInterface|null $oExportArray
     * @return AfrArrExportArrayAsStringInterface
     */
    public function xetExportArray(AfrArrExportArrayAsStringInterface $oExportArray = null): AfrArrExportArrayAsStringInterface
    {
        if ($oExportArray) {
            $this->oExportArray = $oExportArray;
        } elseif (empty($this->oExportArray)) {
            $this->oExportArray = AfrArrExportArrayAsStringClass::getInstance();
        }
        return $this->oExportArray;
    }


}