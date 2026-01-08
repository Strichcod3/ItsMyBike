<?php

class ItsMyBikeDevice extends IPSModule
{
    public function Create()
    {
        parent::Create();

        // Konfiguration
        $this->RegisterPropertyInteger("IOInstance", 0);
        $this->RegisterPropertyString("SerialNumber", "");

        // Status / Laufzeit
        $this->RegisterAttributeInteger("LastUpdate", 0);

        // Basis-Variablen
        $this->RegisterVariableFloat("Latitude",  "Latitude");
        $this->RegisterVariableFloat("Longitude", "Longitude");
        $this->RegisterVariableInteger("Battery", "Battery (%)");
        $this->RegisterVariableString("LastSeen", "Last Seen");
    }
    
    public function GetConfigurationForm()
    {
        $this->LogMessage("IMB Device: GetConfigurationForm()", KL_MESSAGE);
    
        // 1. Alle vorhandenen IO-Instanzen sammeln
        $ioOptions = [];
        foreach (IPS_GetInstanceListByModuleID(
            "{A1C8E0B5-2D9F-4A89-9A8C-6B5A4F8F1E10}" // ItsMyBike IO
        ) as $id) {
            $ioOptions[] = [
                "label" => IPS_GetName($id),
                "value" => $id
            ];
        }
    
        // 2. Tracker-Dropdown vorbereiten
        $trackerOptions = [
            [
                "label" => "-- Tracker auswählen --",
                "value" => ""
            ]
        ];
    
        $ioID = $this->ReadPropertyInteger("IOInstance");
        if ($ioID > 0 && IPS_InstanceExists($ioID)) {
        $devices = @IPS_RequestAction($ioID, "GetDeviceOptions", null);

            if (is_array($devices)) {
                $trackerOptions = array_merge($trackerOptions, $devices);
            }
        }
    
        return json_encode([
            "elements" => [
                [
                    "type"    => "Select",
                    "name"    => "IOInstance",
                    "caption" => "ItsMyBike IO",
                    "options" => $ioOptions,
                    "onChange" => "IMBD_ReloadFormAction(\$id);"
                ],

                [
                    "type"    => "Select",
                    "name"    => "SerialNumber",
                    "caption" => "Tracker auswählen",
                    "options" => $trackerOptions
                ]
            ]
        ]);
    }








    public function ApplyChanges()
    {
        parent::ApplyChanges();
    
        $ioID = $this->ReadPropertyInteger("IOInstance");
    
        $this->LogMessage(
            "IMB Device: ApplyChanges(), IOInstance=" . $ioID,
            KL_MESSAGE
        );
    
        if ($ioID <= 0 || !IPS_InstanceExists($ioID)) {
            $this->SetStatus(201);
            return;
        }
    
        // 🔑 ENTSCHEIDEND: Formular neu aufbauen
        $this->ReloadForm();
    
        $this->SetStatus(102);
    }



    /**********************************************************
     * Platzhalter – kommt als Nächstes
     **********************************************************/

    public function Update()
    {
        $serial = trim($this->ReadPropertyString("SerialNumber"));
        if ($serial === "") {
            return;
        }
    
        // IO-Instanz ermitteln
        $ioID = $this->ReadPropertyInteger("IOInstance");
        if ($ioID <= 0 || !IPS_InstanceExists($ioID)) {
            return;
        }
    
        // Geräte vom IO holen
        $devices = @IPS_RequestAction($ioID, "GetDevices", null);
        if (!is_array($devices)) {
            return;
        }
    
        // Passendes Device suchen
        foreach ($devices as $device) {
            if ((string)$device['serialnumber'] === $serial) {
    
                if (isset($device['position'])) {
                    SetValue(
                        $this->GetIDForIdent("Latitude"),
                        (float)$device['position']['lat']
                    );
                    SetValue(
                        $this->GetIDForIdent("Longitude"),
                        (float)$device['position']['lng']
                    );
                }
    
                if (isset($device['battery'])) {
                    SetValue(
                        $this->GetIDForIdent("Battery"),
                        (int)$device['battery']
                    );
                }
    
                if (isset($device['last_seen_timestamp'])) {
                    SetValue(
                        $this->GetIDForIdent("LastSeen"),
                        (string)$device['last_seen_timestamp']
                    );
                }
    
                $this->WriteAttributeInteger("LastUpdate", time());
                break;
            }
        }
    }

    public function ReloadFormAction($InstanceID, $Value)
    {
        $this->ReloadForm();
    }




}
