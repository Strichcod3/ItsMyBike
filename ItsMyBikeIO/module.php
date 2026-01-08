<?php

class ItsMyBikeIO extends IPSModule
{
    public function Create()
    {
        parent::Create();

        // Eigenschaften (später für Login / Token)
        $this->RegisterPropertyString("Phone", "+49123456");
        $this->RegisterPropertyString("AppBrand", "ITS_MY_BIKE");
        $this->RegisterPropertyString("Token", "");
        $this->RegisterPropertyString("SMSCode", "");
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        // Noch keine Verbindung nach außen
    }

    /**********************************************************
     * Platzhalter für spätere API-Funktionen
     **********************************************************/

    public function RequestSMSCode()
    {
        // kommt später
    }

    public function CreateToken()
    {
        // kommt später
    }

    public function ApiRequest(string $method, string $endpoint, array $body = null)
    {
        // kommt später
    }
}
