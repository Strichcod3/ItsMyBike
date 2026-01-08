<?php

class ItsMyBikeDevice extends IPSModule
{
    public function Create()
    {
        parent::Create();

        // Fixe Konfiguration (vom IO gesetzt!)
        $this->RegisterPropertyString("SerialNumber", "");

        // Status
        $this->RegisterAttributeInteger("LastUpdate", 0);

        // Variablen
        $this->RegisterVariableFloat("Latitude",  "Latitude");
        $this->RegisterVariableFloat("Longitude", "Longitude");
        $this->RegisterVariableInteger("Battery", "Battery (%)");
        $this->RegisterVariableString("LastSeen", "Last Seen");
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        if (!$this->HasActiveParent()) {
            $this->SetStatus(201); // Kein IO
            return;
        }

        $this->SetStatus(102); // Aktiv
    }

    public function Update()
    {
        $this->LogMessage(
            "IMBD Update called, Serial=" . $this->ReadPropertyString("SerialNumber"),
            KL_MESSAGE
        );
        $this->LogMessage(
            "IMBD Devices dump: " . json_encode($devices),
            KL_MESSAGE
        );
        
        if (!$this->HasActiveParent()) {
            return;
        }

        $serial = trim($this->ReadPropertyString("SerialNumber"));
        if ($serial === "") {
            return;
        }

        $ioID = IPS_GetInstance($this->InstanceID)['ConnectionID'];
        if ($ioID <= 0) {
            return;
        }

        $devices = @IPS_RequestAction($ioID, "GetDevices", null);
        if (!is_array($devices)) {
            return;
        }

        foreach ($devices as $device) {
            if ((string)$device['serialnumber'] !== $serial) {
                continue;
            }

            if (isset($device['position'])) {
                SetValue($this->GetIDForIdent("Latitude"),  (float)$device['position']['lat']);
                SetValue($this->GetIDForIdent("Longitude"), (float)$device['position']['lng']);
            }

            if (isset($device['battery'])) {
                SetValue($this->GetIDForIdent("Battery"), (int)$device['battery']);
            }

            if (isset($device['last_seen_timestamp'])) {
                SetValue($this->GetIDForIdent("LastSeen"), (string)$device['last_seen_timestamp']);
            }

            $this->WriteAttributeInteger("LastUpdate", time());
            break;
        }
    }

    public function RequestAction($Ident, $Value)
    {
        if ($Ident === "Update") {
            $this->Update();
        }
    }

    
}
