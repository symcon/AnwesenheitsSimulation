<?php

declare(strict_types=1);

include_once __DIR__ . '/stubs/GlobalStubs.php';
include_once __DIR__ . '/stubs/KernelStubs.php';
include_once __DIR__ . '/stubs/ModuleStubs.php';

use PHPUnit\Framework\TestCase;

class AnwesenheitsSimulationTest extends TestCase
{
    private $anwesenheitsSimulationID = '{87F47896-DD54-442D-94FD-9990BD8D9F54}';

    public function setUp(): void
    {
        //Reset
        IPS\Kernel::reset();

        //Register our core stubs for testing
        IPS\ModuleLoader::loadLibrary(__DIR__ . '/stubs/CoreStubs/library.json');

        //Register our library we need for testing
        IPS\ModuleLoader::loadLibrary(__DIR__ . '/../library.json');

        //Register required profiles
        if (!IPS\ProfileManager::variableProfileExists('~HTMLBox')) {
            IPS\ProfileManager::createVariableProfile('~HTMLBox', 3);
        }
        if (!IPS\ProfileManager::variableProfileExists('~Switch')) {
            IPS\ProfileManager::createVariableProfile('~Switch', 3);
        }

        parent::setUp();
    }

    public function testBaseFunctionality()
    {
        //Variables
        $variableID = IPS_CreateVariable(1);

        //ActionScript
        $scriptID = IPS_CreateScript(0 /* PHP */);
        IPS_SetScriptContent($scriptID, 'SetValue($_IPS[\'VARIABLE\'], $_IPS[\'VALUE\']);');
        IPS_SetVariableCustomAction($variableID, $scriptID);

        //Instances
        $archiveID = IPS_CreateInstance('{43192F0B-135B-4CE7-A0A7-1475603F3060}');
        $instanceID = IPS_CreateInstance($this->anwesenheitsSimulationID);
        $activeID = IPS_GetObjectIDByIdent('Active', $instanceID);
        //Setting cutom time for testing
        AS_setTime($instanceID, strtotime('May 25 1977 12:00'));
        IPS_EnableDebug($instanceID, 1);

        //Configuration
        IPS_SetConfiguration($instanceID, json_encode(
            [
                'Targets' => json_encode([
                    [
                        'VariableID' => $variableID,
                        'Name'       => 'Var1'
                    ]
                ]),
                'RequiredSwitchCount' => 1
            ]
        ));

        //Check basic settings
        IPS_ApplyChanges($instanceID);
        $this->assertEquals(json_encode([['VariableID' => $variableID, 'Name' => 'Var1']]), IPS_GetProperty($instanceID, 'Targets'));
        $this->assertEquals(1, IPS_GetProperty($instanceID, 'RequiredSwitchCount'));

        //Set archived values
        AC_SetLoggingStatus($archiveID, $variableID, true);
        $data = [
            [
                'TimeStamp' => strtotime(' May 25 1977 - 7 days 11:00'),
                'Value'     => 42
            ],
            [
                'TimeStamp' => strtotime(' May 25 1977 - 7 days 15:00'),
                'Value'     => 24
            ]
        ];
        AC_AddLoggedValues($archiveID, $variableID, $data);

        //Activating instance and checking outcome
        RequestAction($activeID, true);
        $this->assertEquals(42, GetValue($variableID));
        //Setting time in order to trigger next execution
        AS_setTime($instanceID, strtotime('May 25 1977 15:00:01'));
        //Mimicking expired timer anchecking outcome
        AS_UpdateTargets($instanceID);
        $this->assertEquals(24, GetValue($variableID));
    }
}