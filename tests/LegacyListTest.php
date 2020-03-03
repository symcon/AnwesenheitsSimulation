<?php

declare(strict_types=1);

include __DIR__ . '/TestBase.php';

class LegacyListTest extends TestBase
{
    public function testListConversion()
    {
        //Variables
        $boolID = $this->CreateActionVariable(VAR_BOOL, 'Booleaan');
        $intID = $this->CreateActionVariable(VAR_INT, 'Integer');

        //Instances
        $instanceID = $this->AnwesenheitsSimulationID;

        $categoryID = IPS_CreateCategory();
        IPS_SetParent($categoryID, $instanceID);
        IPS_SetIdent($categoryID, 'Targets');

        $boolLinkID = $this->CreateLink($boolID, $categoryID);
        $intLinkID = $this->CreateLink($intID, $categoryID);

        IPS_ApplyChanges($instanceID);
        //Check if transfer was successful
        $this->assertEquals(json_encode([['VariableID' => $boolID], ['VariableID' => $intID]]), IPS_GetProperty($instanceID, 'Targets'));
        $this->assertFalse(IPS_CategoryExists($categoryID));
        $this->assertFalse(IPS_LinkExists($intLinkID));
        $this->assertFalse(IPS_LinkExists($boolLinkID));
    }
}