<?php

namespace Autoframe\Components\Env;

use Autoframe\Components\Env\Exception\AfrEnvException;

interface AfrEnvValidatorInterface
{
    /**
     * @param array $aKeys
     * @return $this
     */
    public function required(array $aKeys): self;

    /**
     * @param array $aKeys
     * @return $this
     */
    public function ifPresent(array $aKeys): self;

    /**
     * @param callable $fX
     * @return void
     */
    public function customClosure(callable $fX): void;

    /**
     * @param array $aAllowed
     * @return void
     */
    public function allowedValues(array $aAllowed): void;

    /**
     * @param array $aDataSet
     * @return bool
     * @throws AfrEnvException
     */
    public function validateAll(array $aDataSet): bool;

    /**
     * @return $this
     */
    public function reset(): self;

    /**
     * @param array $aKeys
     * @return $this
     */
    public function unrequire(array $aKeys): self;

    /**
     * @return void
     */
    public function isInteger(): void;

    /**
     * @return void
     */
    public function isFloat(): void;

    /**
     * @return void
     */
    public function isBoolean(): void;

    /**
     * @return void
     */
    public function isArray(): void;

    /**
     * @return void
     */
    public function isString(): void;

    /**
     * @return void
     */
    public function notEmpty(): void;
    /**
     * @return void
     */
    public function isDateTime(): void;
}