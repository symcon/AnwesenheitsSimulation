<?php

declare(strict_types=1);

include_once __DIR__ . '/timetest.php';
class AnwesenheitsSimulation extends IPSModule
{
    use TestTime;
    public function Create()
    {
        //Never delete this line!
        parent::Create();

        //Properties
        $this->RegisterPropertyInteger('RequiredSwitchCount', 4);
        $this->RegisterPropertyInteger('ArchiveControlID', IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0]);
        $this->RegisterPropertyString('Targets', '[]');

        //Attributes
        $this->RegisterAttributeString('SimulationData', '[]');

        //Timer
        $this->RegisterTimer('MidnightTimer', 0, 'if(AS_UpdateData($_IPS[\'TARGET\'])) {AS_UpdateTargets($_IPS[\'TARGET\']);}');
        $this->RegisterTimer('UpdateTargetsTimer', 0, 'AS_UpdateTargets($_IPS[\'TARGET\']);');

        //Variables
        $this->RegisterVariableString('SimulationView', $this->Translate('Simulation preview'), '~HTMLBox');
        $this->RegisterVariableString('SimulationDay', $this->Translate('Simulations source (Day)'), '');
        $this->RegisterVariableBoolean('Active', $this->Translate('Simulation active'), '~Switch');
        $this->EnableAction('Active');
    }

    public function Destroy()
    {
        //Never delete this line!
        parent::Destroy();
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

        //deletes unneeded event
        if (@$this->GetIDForIdent('UpdateDataTimer')) {
            IPS_DeleteEvent($this->GetIDForIdent('UpdateDataTimer'));
        }

        //Transfer links in list
        if ($this->ReadPropertyString('Targets') == '[]') {
            $targetCategoryID = @$this->GetIDForIdent('Targets');

            if ($targetCategoryID) {
                $variables = [];
                foreach (IPS_GetChildrenIDs($targetCategoryID) as $ChildrenID) {
                    $targetID = IPS_GetLink($ChildrenID)['TargetID'];
                    $line = [
                        'VariableID' => $targetID
                    ];
                    array_push($variables, $line);
                    IPS_DeleteLink($ChildrenID);
                }

                IPS_DeleteCategory($targetCategoryID);
                IPS_SetProperty($this->InstanceID, 'Targets', json_encode($variables));
                IPS_ApplyChanges($this->InstanceID);
                return;
            }
        }

        //Transfer legacy SimulationData into attribute
        $simulationDataID = @$this->GetIDForIdent('SimulationData');
        $simulationDataAttr = $this->ReadAttributeString('SimulationData');
        if (($simulationDataID !== false) && (IPS_VariableExists($simulationDataID))) {
            if ($simulationDataAttr == '[]' && function_exists('wddx_deserialize')) {
                $simulationData = json_encode(wddx_deserialize(GetValue($simulationDataID)));
                $this->WriteAttributeString('SimulationData', $simulationData);
            } elseif ($simulationDataAttr == '[]' && !function_exists('wddx_deserialize')) {
                $this->UpdateData();
            }
            $this->UnregisterVariable('SimulationData');
        }
        //Adding references
        foreach ($this->GetReferenceList() as $referenceID) {
            $this->UnregisterReference($referenceID);
        }
        $targets = json_decode($this->ReadPropertyString('Targets'));
        foreach ($targets as $targetID) {
            $this->RegisterReference($targetID->VariableID);
        }

        //Setting initial timer interval
        if ($this->GetValue('Active')) {
            $starttimer = strtotime('tomorrow', $this->getTime());
            $this->SendDebug('TimerInterval', strval($starttimer), 0);
            $this->SetTimerInterval('UpdateTargetsTimer', ($starttimer - $this->getTime()) * 1000);
        } else {
            $this->SetTimerInterval('UpdateTargetsTimer', 0);
        }

        if (GetValue($this->GetIDForIdent('Active'))) {
            $this->UpdateData();
            $this->UpdateTargets();
        }
    }

    public function GetConfigurationForm()
    {
        $jsonForm = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
        $archiveControlID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
        $targets = json_decode($this->ReadPropertyString('Targets'), true);
        foreach ($targets as $target) {
            if (!IPS_VariableExists($target['VariableID'])) {
                $jsonForm['elements'][0]['values'][] = [
                    'rowColor' => '#FFC0C0',
                    'Status'   => $this->Translate('Doesn\'t Exist')
                ];
            } elseif (!HasAction($target['VariableID'])) {
                $jsonForm['elements'][0]['values'][] = [
                    'rowColor' => '#FFC0C0',
                    'Status'   => $this->Translate('No Action')
                ];
            } elseif (!AC_GetLoggingStatus($archiveControlID, $target['VariableID'])) {
                $jsonForm['elements'][0]['values'][] = [
                    'rowColor' => '#FFC0C0',
                    'Status'   => $this->Translate('No Logging')
                ];
            } else {
                $jsonForm['elements'][0]['values'][] = [
                    'Status' => 'OK',
                ];
            }
        }
        return json_encode($jsonForm);
    }

    public function SetSimulation(bool $SwitchOn)
    {
        if ($SwitchOn) {
            //When activating the simulation, fetch actual data for a day and activate timer for updating targets
            if ($this->UpdateData()) {
                $this->UpdateTargets();
                IPS_SetHidden($this->GetIDForIdent('SimulationView'), false);
            }
        } else {
            //When deactivating the simulation, kill data for simulation and deactivate timer for updating targets
            $this->SetValue('SimulationDay', 'Simulation deaktiviert');
            $this->WriteAttributeString('SimulationData', '[]');
            $this->SetTimerInterval('UpdateTargetsTimer', 0);
            $this->SetTimerInterval('MidnightTimer', 0);
            $this->SetValue('SimulationView', 'Simulation deaktiviert');
            IPS_SetHidden($this->GetIDForIdent('SimulationView'), true);
        }

        $this->SetValue('Active', $SwitchOn);
    }

    public function RequestAction($Ident, $Value)
    {
        switch ($Ident) {
            case 'Active':
                $this->SetSimulation($Value);
                break;
            default:
                throw new Exception('Invalid ident');
        }
    }

    //Fetches the needed SimulationData for a whole day
    public function UpdateData()
    {
        $targetIDs = $this->GetTargets();
        $this->SendDebug('Targets', print_r($targetIDs, true), 0);
        //Tries to fetch data for a random but same weekday for the last 4 weeks
        $weekDays = [7, 14, 21, 28];
        shuffle($weekDays);

        //If no same weekday possible -> fetch 1 out of the last 30 days (but not the last 4 weeks)
        $singleDays = array_diff(range(1, 30), $weekDays);
        shuffle($singleDays);

        $simulationData = $this->GetDataArray(array_merge($weekDays, $singleDays), $targetIDs);
        $this->SendDebug('SimulationData', print_r($simulationData, true), 0);
        if (count($simulationData) == 0) {
            $this->SetValue('SimulationDay', $this->Translate('Not enough data!'));
        } else {
            $this->SetValue('SimulationDay', $simulationData['Date']);
            $this->WriteAttributeString('SimulationData', json_encode($simulationData['Data']));
        }

        return count($simulationData) > 0;
    }

    public function GetNextSimulationData()
    {
        $simulationData = json_decode($this->ReadAttributeString('SimulationData'), true);
        $nextSwitchTimestamp = PHP_INT_MAX;
        $result = [];

        //Being sure there is simulationData
        if ($simulationData !== null && $simulationData != '') {
            //Going through all variableID's of the simulationData
            foreach ($simulationData as $id => $value) {
                if (IPS_VariableExists($id)) {
                    unset($currentValue);
                    unset($currentTime);
                    unset($nextValue);
                    unset($nextTime);

                    //Getting the value to set
                    foreach ($value as $key) {
                        if (date('H:i:s', $this->getTime()) >= $key['TimeStamp']) {
                            $currentValue = $key['Value'];
                            $currentTime = $key['TimeStamp'];
                        } else {
                            $nextValue = $key['Value'];
                            $nextTime = $key['TimeStamp'];

                            $nextSwitchTimestamp = min($nextSwitchTimestamp, strtotime($key['TimeStamp'], $this->getTime()));
                            break;
                        }
                    }

                    if (!isset($currentValue) || !isset($currentTime)) {
                        $currentValue = false;
                        $currentTime = '00:00';
                    }
                    if (!isset($nextValue) || !isset($nextTime)) {
                        $nextValue = '-';
                        $nextTime = '-';
                    }

                    $result[$id] = ['currentValue' => $currentValue, 'currentTime' => $currentTime, 'nextValue' => $nextValue, 'nextTime' => $nextTime];
                }
            }
        } else {
            echo 'No valid SimulationData';
        }

        if ($nextSwitchTimestamp != PHP_INT_MAX) {
            $result['nextSwitchTimestamp'] = $nextSwitchTimestamp;
        }
        $this->SendDebug('NextSimResult', print_r($result, true), 0);
        return $result;
    }

    public function UpdateTargets()
    {
        $targetIDs = $this->GetTargets();
        $NextSimulationData = $this->GetNextSimulationData();

        //lets update the preview table
        $this->UpdateView($targetIDs, $NextSimulationData);

        foreach ($targetIDs as $targetID) {
            $v = IPS_GetVariable($targetID);

            if (!isset($NextSimulationData[$targetID])) {
                $this->SendDebug('Update', 'Device ' . $targetID . ' has no simulation data for now!', 0);
            } else {
                $this->SendDebug('Update', 'Device ' . $targetID . ' shall be ' . (int) $NextSimulationData[$targetID]['currentValue'] . ' since ' . $NextSimulationData[$targetID]['currentTime'] . ' and currently is ' . (int) $v['VariableValue'], 0);

                //Set variableValue, if there is a currentValue and its not the same as already set
                $targetValue = $NextSimulationData[$targetID]['currentValue'];

                //Only update if target differs
                if ($targetValue != $v['VariableValue']) {
                    $o = IPS_GetObject($targetID);
                    if ($v['VariableCustomAction'] !== 0) {
                        $actionID = $v['VariableCustomAction'];
                    } else {
                        $actionID = $v['VariableAction'];
                    }

                    $this->SendDebug('Action', 'Device ' . $targetID . ' will be updated!', 0);
                    $this->SendDebug('RequestActionID', "$actionID", 0);
                    if (IPS_InstanceExists($actionID)) {
                        IPS_RequestAction($actionID, $o['ObjectIdent'], $targetValue);
                    } elseif (IPS_ScriptExists($actionID)) {
                        echo IPS_RunScriptWaitEx($actionID, ['VARIABLE' => $targetID, 'VALUE' => $targetValue]);
                    }
                }
            }
        }

        if (isset($NextSimulationData['nextSwitchTimestamp'])) {
            $this->SetTimerInterval('UpdateTargetsTimer', max(1, $NextSimulationData['nextSwitchTimestamp'] - $this->getTime()) * 1000);
        } else {
            $this->SetTimerInterval('UpdateTargetsTimer', 0);
        }
        $this->SetTimerInterval('MidnightTimer', 1000 * (strtotime('tomorrow', $this->getTime()) - $this->getTime()));
    }
    //If the the variable has a name we use it
    private function GetName($VariableID)
    {
        $targets = json_decode($this->ReadPropertyString('Targets'), true);
        foreach ($targets as $target) {
            if (($target['VariableID'] == $VariableID) && IPS_VariableExists($target['VariableID'])) {
                if (!isset($target['Name']) || $target['Name'] == '') {
                    return IPS_GetName($VariableID);
                } else {
                    return $target['Name'];
                }
            }
        }
    }

    //Returns all variableIDs in list
    private function GetTargets()
    {
        $targets = json_decode($this->ReadPropertyString('Targets'), true);

        $result = [];
        foreach ($targets as $target) {
            if (IPS_VariableExists($target['VariableID'])) {
                $result[] = $target['VariableID'];
            }
        }
        return $result;
    }

    //returns a array of the dayData of 1 Variable
    private function GetDayData($day, $targetIDs)
    {
        $dayStart = mktime(0, 0, 0, intval(date('m', $this->getTime())), intval(date('d', $this->getTime())), intval(date('Y', $this->getTime())));
        $dayDiff = $day * 24 * 3600;
        $dayData = [];
        $archiveControlID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];

        //Going through all variables
        foreach ($targetIDs as $targetID) {
            if (AC_GetLoggingStatus($archiveControlID, $targetID)) {
                //Fetch Data for all variables but only one day
                $values = AC_GetLoggedValues($archiveControlID, $targetID, $dayStart - $dayDiff, $dayStart + (24 * 3600) - $dayDiff - 1, 0);
                if (count($values) > 0) {

                    //Transform UnixTimeStamp into human readable value
                    foreach ($values as $key => $value) {
                        $values[$key]['TimeStamp'] = date('H:i:s', $value['TimeStamp']);
                    }

                    //Reverse array to have the Timestamps ascending
                    $dayData[$targetID] = array_reverse($values);
                }
            }
        }

        // return all values for listed variables for one day in a array
        return ['Date' => date('d.m.Y', $dayStart - $dayDiff), 'Data' => $dayData];
    }

    //returns a array of all listed variables for 1 day and checks if this meets the needed switchcount
    private function GetDataArray($days, $targetIDs)
    {

        //Get the dayData for all variables
        foreach ($days as $day) {
            $data = $this->GetDayData($day, $targetIDs);

            $this->SendDebug('Fetch', 'Fetched day -' . $day . ' with ' . count($data['Data']) . ' valid device(s)', 0);

            if (count($data['Data']) > 0) {

                //Sum up the switchCount
                $switchCounts = 0;
                foreach ($data['Data'] as $value) {
                    $switchCounts += count($value);
                }

                $this->SendDebug('Fetch', '> Required entropy of ' . ($this->ReadPropertyInteger('RequiredSwitchCount') * count($targetIDs)) . '. Have ' . $switchCounts, 0);

                //Check if the needed switchCount requierement is meet
                if ($switchCounts >= ($this->ReadPropertyInteger('RequiredSwitchCount') * count($targetIDs))) {
                    return $data;
                }
            }
        }

        return [];
    }

    private function UpdateView($targetIDs, $nextSimulationData)
    {
        $html = "<table style='width: 100%; border-collapse: collapse;'>";
        $html .= '<tr>';
        $html .= "<td style='padding: 5px; font-weight: bold;'>" . $this->Translate('Actor') . '</td>';
        $html .= "<td style='padding: 5px; font-weight: bold;'>" . $this->Translate('Last value') . '</td>';
        $html .= "<td style='padding: 5px; font-weight: bold;'>" . $this->Translate('Since') . '</td>';
        $html .= "<td style='padding: 5px; font-weight: bold;'>" . $this->Translate('Next value') . '</td>';
        $html .= "<td style='padding: 5px; font-weight: bold;'>" . $this->Translate('At') . '</td>';
        $html .= '</tr>';

        foreach ($targetIDs as $targetID) {
            $name = $this->GetName($targetID);
            $html .= "<tr style='border-top: 1px solid rgba(255,255,255,0.10);'>";

            $tableContent = [
                'name'         => $name,
                'currentValue' => '-',
                'currentTime'  => '00:00',
                'nextValue'    => '-',
                'nextTime'     => '-'
            ];

            if (isset($nextSimulationData[$targetID])) {
                $tableContent['currentValue'] = GetValueFormattedEx($targetID, $nextSimulationData[$targetID]['currentValue']);
                $tableContent['currentTime'] = $nextSimulationData[$targetID]['currentTime'];
                $tableContent['nextTime'] = $nextSimulationData[$targetID]['nextTime'];
                if ($nextSimulationData[$targetID]['nextValue'] !== '-') {
                    $tableContent['nextValue'] = GetValueFormattedEx($targetID, $nextSimulationData[$targetID]['nextValue']);
                }
            }
            $archiveControlID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
            if (!HasAction($targetID)) {
                $tableContent['nextValue'] = '<span style="color:red">' . $this->Translate('No Action') . '</span>';
            } elseif (!AC_GetLoggingStatus($archiveControlID, $targetID)) {
                $tableContent['nextValue'] = '<span style="color:red">' . $this->Translate('No Logging') . '</span>';
            }

            foreach ($tableContent as $element => $value) {
                $html .= "<td style='padding: 5px;'>$value</td>";
            }
            $html .= '</tr>';
        }

        $html .= '</table>';

        $this->SetValue('SimulationView', $html);
    }
}
