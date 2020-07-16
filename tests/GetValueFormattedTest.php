<?php

declare(strict_types=1);

include_once __DIR__ . '/TestBase.php';

class GetValueFormattedTest extends TestBase
{
    public function testTable()
    {
        //Variables
        $variableID = $this->CreateActionVariable(VAR_INT);

        //Profile for GetValueFormatted
        IPS_CreateVariableProfile('TestValueFormattedProfile', 1);
        IPS_SetVariableProfileText('TestValueFormattedProfile', 'Prefix ', ' %');
        IPS_SetVariableProfileValues('TestValueFormattedProfile', 0, 255, 0);
        IPS_SetVariableCustomProfile($variableID, 'TestValueFormattedProfile');

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

        //Activating instance and comparing SimulationView content
        RequestAction($activeID, true);
        $expected = "<table style='width: 100%; border-collapse: collapse;'><tr><td style='padding: 5px; font-weight: bold;'>Actor</td><td style='padding: 5px; font-weight: bold;'>Last value</td><td style='padding: 5px; font-weight: bold;'>Since</td><td style='padding: 5px; font-weight: bold;'>Next value</td><td style='padding: 5px; font-weight: bold;'>At</td></tr><tr style='border-top: 1px solid rgba(255,255,255,0.10);'><td style='padding: 5px;'>Var1</td><td style='padding: 5px;'>Prefix 16 %</td><td style='padding: 5px;'>11:00:00</td><td style='padding: 5px;'>Prefix 9 %</td><td style='padding: 5px;'>15:00:00</td></tr></table>";
        $actual = GetValue(IPS_GetObjectIDByIdent('SimulationView', $instanceID));
        $this->assertEquals($expected, $actual);
    }
}