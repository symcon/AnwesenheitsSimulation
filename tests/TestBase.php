<?php

declare(strict_types=1);

define('VAR_BOOL', 0);
define('VAR_INT', 1);
define('VAR_FLOAT', 2);
define('VAR_STRING', 3);

include_once __DIR__ . '/stubs/GlobalStubs.php';
include_once __DIR__ . '/stubs/KernelStubs.php';
include_once __DIR__ . '/stubs/ModuleStubs.php';

use PHPUnit\Framework\TestCase;

class TestBase extends TestCase
{
    protected $ArchiveControlID;
    protected $AnwesenheitsSimulationID;

    protected function setUp(): void
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
        $this->ArchiveControlID = IPS_CreateInstance('{43192F0B-135B-4CE7-A0A7-1475603F3060}');
        $this->AnwesenheitsSimulationID = IPS_CreateInstance('{87F47896-DD54-442D-94FD-9990BD8D9F54}');

        parent::setUp();
    }

    protected function CreateActionVariable(int $VariableType, string $Ident, bool $Action = true)
    {
        $variableID = IPS_CreateVariable($VariableType);
        IPS_SetIdent($variableID, $Ident);
        IPS_SetName($variableID, $Ident);
        if ($Action) {
            $scriptID = IPS_CreateScript(0 /* PHP */);
            IPS_SetScriptContent($scriptID, 'SetValue($_IPS[\'VARIABLE\'], $_IPS[\'VALUE\']);');
            IPS_SetVariableCustomAction($variableID, $scriptID);
        }
        return $variableID;
    }

    protected function CreateLink(int $TargetID = 0, int $ParentID = 0)
    {
        $id = IPS_CreateLink();
        IPS_SetParent($id, $ParentID);
        IPS_SetLinkTargetID($id, $TargetID);
        return $id;
    }
}