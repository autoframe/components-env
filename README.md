# Autoframe is a low level framework that is oriented on SOLID flexibility

[![Build Status](https://github.com/autoframe/components-env/workflows/PHPUnit-tests/badge.svg?branch=main)](https://github.com/autoframe/components-env/actions?query=branch:main)
[![License: The 3-Clause BSD License](https://img.shields.io/github/license/autoframe/components-env)](https://opensource.org/license/bsd-3-clause/)
![Packagist Version](https://img.shields.io/packagist/v/autoframe/components-env?label=packagist%20stable)
[![Downloads](https://img.shields.io/packagist/dm/autoframe/components-env.svg)](https://packagist.org/packages/autoframe/components-env)

*PHP Environment Tools, Autoframe Framework*

Namespace:
- Autoframe\Components\Env
- **AfrEnv::getInstance()->isDev() returns true if APP_ENV is null**
- **APP_ENV is not mandatory but recommended AfrEnv::getInstance()->setInlineEnvVar('APP_ENV', 'DEV');**

Classes:
- class AfrEnv extends AfrSingletonAbstractClass implements AfrEnvInterface
  - setWorkDir(string $sDir)
  - readEnv(int $iCacheSeconds, array $aExtraEnvDirsFiles = [])
    * iCacheSeconds is the number of cache seconds before expire. Use zero for no cache
    * aExtraEnvDirsFiles to add extra env directories and .env files
  - readEnvPhpFile(string $sFilePath)
  - setInlineEnvVar(string $sKey, $mData)
  - - populate inline keys
  - required(array $aKeys): AfrEnvValidatorInterface
  - ifPresent(array $aKeys): AfrEnvValidatorInterface
  - unrequire(array $aKeys): AfrEnvValidatorInterface
  - - unset required or ifPresent
  - registerEnv(bool $bMutableOverwrite = false, bool $bRegisterPutEnv = false)
  - - will populate $_ENV, $_SERVER and getenv()
  - getEnv(string $sKey = '') single key or all registered values
  - - storage only inside class without $_ENV, $_SERVER or getenv()
  - flush()  reset and clear all
  - isProduction()
  - isStaging()
  - isLocal()
  - isDev()
- class AfrEnvParserClass extends AfrSingletonAbstractClass implements AfrEnvParserInterface
    - parseStr
    - parseFile
- class AfrEnvValidatorClass implements AfrEnvValidatorInterface
    - reusable validator using closures
    - required(array $aKeys)
    - ifPresent(array $aKeys)
    - customClosure(callable $fX)
    - allowedValues(array $aAllowed)
    - validateAll(array $aDataSet): bool
    - reset()
    - reset(array $aKeys)
    - unrequire(array $aKeys)
    - isInteger()
    - isFloat()
    - isBoolean()
    - isArray()
    - isString()
    - isDateTime()
    - notEmpty()

---

# AfrEnv

    // GENERAL
    $oEnv = AfrEnv::getInstance()->setWorkDir(__DIR__);
    $oEnv->readEnv(0); //load env files from __DIR__ without cache
    $oEnv->readEnv(60); //cache loaded env file for 60 seconds
    $oEnv->setInlineEnvVar('FOO', 'BAR'); //set *[FOO]=BAR
    
    $oEnv->getEnv('APP_ENV'); //get env key
    $oEnv->getEnv(); //get all env keys as array

    $oEnv->registerEnv($bMutableOverwrite = true, $bRegisterPutEnv = true);
    // populate $_SERVER, $_ENV and getenv()
    $oEnv->flush(); //total reset for class,cache, except superglobals are stil available

---

    // INCLUDES PHP FILE THAT CONTAINS return array(...);
    $oEnv = AfrEnv::getInstance();
    $oEnv->readEnvPhpFile(path);
    $oEnv->getEnv('FROM_PHP_FILE');

---

    // Validator: REQUIRED or throw error on register or access
    $oEnv = AfrEnv::getInstance();
    $oEnv->required(['APP_ENV','SECRET']);
    $oEnv->required(['NUMBER_INT'])->isInteger();
    $oEnv->ifPresent(['NUMBER_FLOAT'])->isFloat();
    $oEnv->ifPresent(['SOMENTHING'])->notEmpty();
    $oEnv->ifPresent(['SOME_DATE_TIME'])->isDateTime();
    $oEnv->unrequire(['NUMBER_INT']);
    $oEnv->getEnv('SECRET');

---

    // CUSTOM APP_ENV
    $oEnv = AfrEnv::getInstance();
    $this->ifPresent(['APP_ENV'])->allowedValues([
      'DEV',
      'PRODUCTION',
      'STAGING',
      'LOCAL',
      'CUSTOM',
    ]);
    $oEnv->setInlineEnvVar('APP_ENV', 'CUSTOM');
    echo $oEnv->getEnv('APP_ENV'); //prints CUSTOM
	
---

    // xet : set / get
    $oEnv = AfrEnv::getInstance();
    $oEnv->xetAfrEnvParser(AfrEnvParserInterface $oEnvParser = null): AfrEnvParserInterface
    $oEnv->xetAfrEnvValidator(AfrEnvValidatorInterface $oValidator = null): AfrEnvValidatorInterface
    $oEnv->xetFileList(AfrDirTraversingFileListInterface $oFileList = null): AfrDirTraversingFileListInterface
    $oEnv->xetOverWrite(AfrOverWriteInterface $oOverWrite = null): AfrOverWriteInterface
    $oEnv->xetExportArray(AfrArrExportArrayAsStringInterface $oExportArray = null): AfrArrExportArrayAsStringInterface
