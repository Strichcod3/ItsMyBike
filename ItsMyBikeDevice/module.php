<?php

class ItsMyBikeDevice extends IPSModule
{
    public function Create()
    {
        parent::Create();

        // Konfiguration
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
        $trackerOptions = [
            ["label" => "-- Tracker auswählen --", "value" => ""]
        ];
    
        $ioID = $this->GetIOInstanceID();
        if ($ioID > 0) {
            $devices = IPS_RequestAction($ioID, "GetDeviceOptions", null);
            if (is_array($devices)) {
                $trackerOptions = array_merge($trackerOptions, $devices);
            }
        }
    
        return json_encode([
            "elements" => [
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
    
        $ioID = $this->GetIOInstanceID();
        if ($ioID === 0) {
            $this->SetStatus(201); // Kein IO vorhanden
            return;
        }
    
        $this->SetStatus(102); // Aktiv
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
        $ioID = $this->GetIOInstanceID();
        if ($ioID === 0) {
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



    private function GetIOInstanceID(): int
    {
        $ioIDs = IPS_GetInstanceListByModuleID(
            '{A1C8E0B5-2D9F-4A89-9A8C-6B5A4F8F1E10}' // ItsMyBike IO
        );
    
        if (count($ioIDs) !== 1) {
            return 0;
        }
    
        return $ioIDs[0];
    }



}
