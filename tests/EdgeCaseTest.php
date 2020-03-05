<?php

declare(strict_types=1);

include_once __DIR__ . '/TestBase.php';

class EdgeCaseTest extends TestBase
{
    public function testEdgeCaseAction()
    {
        //Variables
        $variableID = $this->CreateActionVariable(VAR_INT, 'Variable1', false);

        //Instances
        $archiveID = $this->ArchiveControlID;
        $instanceID = $this->AnwesenheitsSimulationID;
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
        $caption = json_decode(IPS_GetConfigurationForm($instanceID), true)['elements'][0]['caption'];
        echo PHP_EOL . $caption . PHP_EOL;
        RequestAction($activeID, true);

        $this->assertEquals($caption, "The following variables have no action and therefore cannot be switched:\n - Variable1");
    }
}