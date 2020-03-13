<?php

declare(strict_types=1);

include_once __DIR__ . '/TestBase.php';

class NoVariableActionTest extends TestBase
{
    public function testNoVariableAction()
    {
        //Variables
        $variableID = IPS_CreateVariable(VAR_INT);
        IPS_SetName($variableID, 'Variable1');

        //Instances
        $instanceID = $this->AnwesenheitsSimulationID;

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
        IPS_ApplyChanges($instanceID);
        $caption = json_decode(IPS_GetConfigurationForm($instanceID), true)['elements'][0]['caption'];
        $this->assertEquals($caption, "The following variables have no action and therefore cannot be switched:\n - Variable1");
    }
}