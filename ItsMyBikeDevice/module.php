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
    $this->LogMessage("IMBD Update entered", KL_MESSAGE);

    if (!$this->HasActiveParent()) {
        $this->LogMessage("IMBD no active parent", KL_WARNING);
        return;
    }

    $serial = trim($this->ReadPropertyString("SerialNumber"));
    $this->LogMessage("IMBD Serial=" . $serial, KL_MESSAGE);

    $ioID = IPS_GetInstance($this->InstanceID)['ConnectionID'];
    $this->LogMessage("IMBD IOID=" . $ioID, KL_MESSAGE);

    $devices = IPS_RequestAction($ioID, "GetDevices", null);

    $this->LogMessage(
        "IMBD Devices type=" . gettype($devices),
        KL_MESSAGE
    );

    if (!is_array($devices)) {
        $this->LogMessage("IMBD Devices is not array", KL_ERROR);
        return;
    }

    $this->LogMessage(
        "IMBD Devices JSON=" . json_encode($devices),
        KL_MESSAGE
    );

    foreach ($devices as $device) {
        if (!isset($device['serialnumber'])) {
            continue;
        }

        if ((string)$device['serialnumber'] !== $serial) {
            continue;
        }

        $this->LogMessage("IMBD matching device found", KL_MESSAGE);

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
