<?php

class ItsMyBikeIO extends IPSModule
{
    public function Create()
    {
        parent::Create();
    
        // Benutzer-Konfiguration
        $this->RegisterPropertyString("Phone", "");
        $this->RegisterPropertyString("AppBrand", "ITS_MY_BIKE");
        $this->RegisterPropertyString("SMSCode", "");
    
        // Interner Zustand
        $this->RegisterAttributeString("Token", "");
        $this->RegisterAttributeString("AuthState", "INIT");
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();
    
        $smsCode = trim($this->ReadPropertyString("SMSCode"));
        $token   = $this->ReadAttributeString("Token");
    
        // Wenn bereits eingeloggt → nichts tun
        if ($token !== "") {
            return;
        }
    
        // Wenn SMS-Code gesetzt wurde → automatisch Token erzeugen
        if ($smsCode !== "") {
            $this->CreateTokenFromSMS($smsCode);
        }
    }


    /**********************************************************
     * Platzhalter für spätere API-Funktionen
     **********************************************************/

    public function RequestSMSCode()
    {
        // kommt später
    }
    
    private function CreateTokenFromSMS(string $smsCode)
    {
        $phone = $this->ReadPropertyString("Phone");
        $brand = $this->ReadPropertyString("AppBrand");
    
        [$http, $resp] = $this->ApiRequest(
            "POST",
            "/api/phone/v2/token",
            [
                "user" => [
                    "phone"     => $phone,
                    "smscode"   => $smsCode,
                    "app_brand" => $brand
                ]
            ]
        );
    
        $data = json_decode($resp, true);
    
        if (isset($data['user_token']['access_token'])) {
            $this->WriteAttributeString("Token", $data['user_token']['access_token']);
            $this->WriteAttributeString("AuthState", "AUTH_OK");
    
            // SMS-Code automatisch leeren (sehr wichtig!)
            IPS_SetProperty($this->InstanceID, "SMSCode", "");
            IPS_ApplyChanges($this->InstanceID);
        } else {
            $this->WriteAttributeString("AuthState", "ERROR");
        }
    
        $this->ReloadForm();
    }

    public function GetConfigurationForm()
    {
        $state = $this->ReadAttributeString("AuthState");
    
        return json_encode([
            "elements" => [
                [
                    "type"    => "ValidationTextBox",
                    "name"    => "Phone",
                    "caption" => "Telefonnummer"
                ],
                [
                    "type"    => "ValidationTextBox",
                    "name"    => "AppBrand",
                    "caption" => "App-Brand"
                ],
                [
                    "type"    => "ValidationTextBox",
                    "name"    => "SMSCode",
                    "caption" => "SMS-Code (nach Erhalt eintragen)"
                ]
            ],
            "actions" => [
                [
                    "type"    => "Button",
                    "label"   => "SMS anfordern",
                    "onClick" => "IMB_RequestSMSCode(\$id);"
                ],
                [
                    "type"  => "Label",
                    "label" => "Status: $state"
                ]
            ]
        ]);
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
