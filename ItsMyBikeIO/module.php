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
        $this->RegisterAttributeString("DevicesCache", "[]");

    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();
    
        if ($this->ReadAttributeString("AuthState") === "AUTH_OK") {
            return;
        }
    
        $smsCode = trim($this->ReadPropertyString("SMSCode"));
    
        if ($smsCode !== "") {
            $this->CreateTokenFromSMS($smsCode);
        }
    }



    /**********************************************************
     * Platzhalter für spätere API-Funktionen
     **********************************************************/

    public function RequestSMSCode()
    {
        $this->LogMessage("IMB: RequestSMSCode() called", KL_MESSAGE);
    
        $phone = trim($this->ReadPropertyString("Phone"));
        $brand = trim($this->ReadPropertyString("AppBrand"));
    
        if ($phone === "") {
            $this->WriteAttributeString("AuthState", "NO_PHONE");
            $this->LogMessage("IMB: No phone number set", KL_WARNING);
            $this->ReloadForm();
            return;
        }
    
        [$httpCode, $response] = $this->ApiRequest(
            "PUT",
            "/api/phone/v2/token/request_sms_code",
            [
                "user" => [
                    "phone"     => $phone,
                    "app_brand" => $brand
                ]
            ]
        );
    
        $this->LogMessage(
            "IMB: SMS request HTTP=$httpCode RESPONSE=" . $response,
            KL_MESSAGE
        );
    
        if ($httpCode === 200) {
            $this->WriteAttributeString("AuthState", "SMS_REQUESTED");
        } else {
            $this->WriteAttributeString("AuthState", "ERROR_HTTP_$httpCode");
        }
    
        $this->ReloadForm();
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

             $this->GetDevices();
             $this->ReloadForm();
    
            // SMS-Code automatisch leeren (sehr wichtig!)
            IPS_SetProperty($this->InstanceID, "SMSCode", "");
            IPS_ApplyChanges($this->InstanceID);
        } else {
            $this->WriteAttributeString("AuthState", "TOKEN_FAILED");
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
 



    private function ApiRequest(string $method, string $path, array $body = null)
    {
        $url = "https://itsmybike.cloud" . $path;
    
        $headers = [
            "Accept: application/json"
        ];
    
        $token = $this->ReadAttributeString("Token");
        if ($token !== "") {
            $headers[] = "Authorization: Token token=$token";
        }
    
        $ch = curl_init($url);
    
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => false,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 15
        ]);
    
        if ($body !== null) {
            $headers[] = "Content-Type: application/json";
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }
    
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
    
        curl_close($ch);
    
        if ($error) {
            $this->LogMessage("IMB cURL error: $error", KL_ERROR);
        }
    
        return [$httpCode, $response];
    }

    public function GetDevices()
    {
        if ($this->ReadAttributeString("AuthState") !== "AUTH_OK") {
            return null;
        }
    
        [$httpCode, $response] = $this->ApiRequest(
            "GET",
            "/api/phone/v2/device"
        );
    
        if ($httpCode !== 200) {
            $this->LogMessage("IMB: GetDevices failed HTTP=$httpCode", KL_WARNING);
            return null;
        }
    
        $data = json_decode($response, true);
        if (!is_array($data)) {
            return null;
        }
    
        $this->WriteAttributeString("DevicesCache", json_encode($data));
    
        return $data;
    }



    public function RequestAction($Ident, $Value)
    {
        switch ($Ident) {
            case "GetDevices":
                return $this->GetDevices();
    
            case "GetDeviceOptions":
                return $this->GetDeviceOptions();
        }
    }


public function GetDeviceOptions()
{

    $this->LogMessage(
    "IMB: GetDeviceOptions() called, AuthState=" .
    $this->ReadAttributeString("AuthState"),
    KL_MESSAGE
    );
    
    if ($this->ReadAttributeString("AuthState") !== "AUTH_OK") {
        return [];
    }

    $cache = json_decode(
        $this->ReadAttributeString("DevicesCache"),
        true
    );

    if (!is_array($cache)) {
        return [];
    }

    $options = [];
    foreach ($cache as $device) {
        if (isset($device['serialnumber'], $device['name'])) {
            $options[] = [
                "label" => $device['name'] . " (" . $device['serialnumber'] . ")",
                "value" => (string)$device['serialnumber']
            ];
        }
    }

    $this->LogMessage(
        "IMB: DevicesCache=" . $this->ReadAttributeString("DevicesCache"),
        KL_MESSAGE
    );
    
    return $options;
}



}
