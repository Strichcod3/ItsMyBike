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
        $this->LogMessage("IMB Device: GetConfigurationForm()", KL_MESSAGE);
        $options = [
            [
                "label" => "-- Tracker auswählen --",
                "value" => ""
            ]
        ];
    
        // IO ermitteln
        $ioID = IPS_GetInstance($this->InstanceID)['ConnectionID'];
    
        if ($ioID > 0 && IPS_InstanceExists($ioID)) {
            // 🔴 einzig erlaubter Weg
            $ioOptions = @IPS_RequestAction($ioID, "GetDeviceOptions", null);

            $this->LogMessage(
                "IMB Device: ioOptions=" . print_r($ioOptions, true),
                KL_MESSAGE
            );
            
            if (is_array($ioOptions)) {
                $options = array_merge($options, $ioOptions);
            }
        }
    
        return json_encode([
            "elements" => [
                [
                    "type"    => "Select",
                    "name"    => "SerialNumber",
                    "caption" => "Tracker auswählen",
                    "options" => $options
                ]
            ],
            "actions" => []
        ]);
    }







    public function ApplyChanges()
    {
        parent::ApplyChanges();

        // Prüfen, ob IO verbunden ist
        if (!$this->HasActiveParent()) {
            $this->SetStatus(201); // Kein Gateway
            return;
        }

        $this->SetStatus(102); // Aktiv
    }

    /**********************************************************
     * Platzhalter – kommt als Nächstes
     **********************************************************/

    public function Update()
    {
        if (!$this->HasActiveParent()) {
            $this->SetStatus(201);
            return;
        }
    
        $serial = trim($this->ReadPropertyString("SerialNumber"));
        if ($serial === "") {
            return;
        }
    
        // IO-Instanz ermitteln
        $ioID = IPS_GetInstance($this->InstanceID)['ConnectionID'];
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

}
