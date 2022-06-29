<?php

declare(strict_types=1);

include_once __DIR__ . '/TestBase.php';

class InvalidVariableTest extends TestBase
{
    public function testNoVariableAction()
    {

        //Instances
        $instanceID = $this->AnwesenheitsSimulationID;
        $archive = $this->ArchiveControlID;

        //Variables
        $variableID = IPS_CreateVariable(VAR_INT);
        IPS_SetName($variableID, 'Variable1');
        AC_SetLoggingStatus($archive, $variableID, true);

        //Configuration
        IPS_SetConfiguration($instanceID, json_encode(
            [
                'Targets' => json_encode([
                    [
                        'VariableID' => $variableID,
                        'Name'       => 'Var1',
                    ]
                ]),
                'RequiredSwitchCount' => 1
            ]
        ));
        IPS_ApplyChanges($instanceID);
        $values = json_decode(IPS_GetConfigurationForm($instanceID), true)['elements'][0]['values'];
        $this->assertEquals($values[0]['Status'], 'No Action');
    }

    public function testNoVariableLogging()
    {

        //Instances
        $instanceID = $this->AnwesenheitsSimulationID;
        $archive = $this->ArchiveControlID;

        //Variables
        $variableID = IPS_CreateVariable(VAR_INT);
        IPS_SetName($variableID, 'Variable1');
        IPS_SetIdent($variableID, 'Variable1');
        IPS\VariableManager::setVariableAction($variableID, $instanceID);

        //Configuration
        IPS_SetConfiguration($instanceID, json_encode(
            [
                'Targets' => json_encode([
                    [
                        'VariableID' => $variableID,
                        'Name'       => 'Var1',
                    ]
                ]),
                'RequiredSwitchCount' => 1
            ]
        ));
        IPS_ApplyChanges($instanceID);
        $values = json_decode(IPS_GetConfigurationForm($instanceID), true)['elements'][0]['values'];
        $this->assertEquals($values[0]['Status'], 'No Logging');
    }
}