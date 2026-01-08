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
        return json_encode([
            "elements" => [
                [
                    "type"    => "ValidationTextBox",
                    "name"    => "SerialNumber",
                    "caption" => "Seriennummer des Trackers"
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
        // Wird später vom Timer aufgerufen
        // Ruft dann das IO-Modul auf
    }
}
