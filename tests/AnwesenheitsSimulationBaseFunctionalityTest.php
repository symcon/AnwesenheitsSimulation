<?php

declare(strict_types=1);

include_once __DIR__ . '/TestBase.php';

class AnwesenheitsSimulationTest extends TestBase
{
    public function testBaseFunctionality()
    {
        //Variables
        $variableID = $this->CreateActionVariable(VAR_INT, 'Variable1');

        //Instances
        $archiveID = $this->ArchiveControlID;
        $instanceID = $this->AnwesenheitsSimulationID;
        $activeID = IPS_GetObjectIDByIdent('Active', $instanceID);
        //Setting custom time for testing
        AS_setTime($instanceID, strtotime('May 25 1977 12:00'));

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