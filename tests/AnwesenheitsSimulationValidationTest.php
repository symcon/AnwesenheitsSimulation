<?php

declare(strict_types=1);
include_once __DIR__ . '/stubs/Validator.php';
class AnwesenheitsSimulationValidationTest extends TestCaseSymconValidation
{
    public function testValidateAnwesenheitsSimulation(): void
    {
        $this->validateLibrary(__DIR__ . '/..');
    }
    public function testValidatePresenceSimulationModule(): void
    {
        $this->validateModule(__DIR__ . '/../PresenceSimulation');
    }
}