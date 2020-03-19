<?php

declare(strict_types=1);

include_once __DIR__ . '/TestBase.php';

class NoNameTest extends TestBase
{
    public function testNoName()
    {
        //Instances
        $archiveID = $this->ArchiveControlID;
        $instanceID = $this->AnwesenheitsSimulationID;

        AS_setTime($instanceID, strtotime('May 25 1977 12:00'));

        //Variable
        $variableID = $this->CreateActionVariable(VAR_INT);
        IPS_SetName($variableID, 'Name');

        IPS_SetConfiguration($instanceID, json_encode(
            [
                'Targets' => json_encode([
                    [
                        'VariableID' => $variableID
                    ]
                ]),
                'RequiredSwitchCount' => 1
            ]
        ));

        //Set archived values
        AC_SetLoggingStatus($archiveID, $variableID, true);
        $data = [
            [
                'TimeStamp' => strtotime(' May 25 1977 - 7 days 11:00'),
                'Value'     => 42
            ]
        ];
        AC_AddLoggedValues($archiveID, $variableID, $data);
        IPS_ApplyChanges($instanceID);
        error_clear_last();
        RequestAction(IPS_GetObjectIDByIdent('Active', $instanceID), true);
        $this->assertEquals(null, error_get_last());
    }
}