<?php

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class jnapResult {
    private $response;

    public function __construct($response) {
        $this->response = json_decode($response);;
    }

    public function isSuccess() {
        return isset($this->response->{'result'}) && $this->response->{'result'} === 'OK';
    }

    public function getOutput() {
        return $this->isSuccess() ? $this->response->{'output'} : (object)[];
    }

    public function getResult() {
        return $this->response->{'result'} ?? (object)[];
    }
}

class jnapClient {

    /**
     * @var LoggerInterface
     */
    private $logger;

    private $url = '';
    private $auth = '';

    public function __construct($host, $user, $pswd, LoggerInterface $logger = null) {
        $this->logger = $logger ?? new NullLogger();
        $this->url = "http://{$host}/JNAP/";
        $this->auth = base64_encode("{$user}:{$pswd}");
    }

    private function PostAction($action, $data = []) {
        $headers = [
            "Content-Type: application/json; charset=utf-8",
            "Accept: application/json",
            "X-JNAP-Action: http://linksys.com/jnap/{$action}",
            "X-JNAP-Authorization: Basic {$this->auth}"
        ];

        $ch = curl_init();

        curl_setopt_array($ch, array(
            CURLOPT_URL => $this->url,
            CURLOPT_POST => true,
            CURLOPT_HEADER  => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 1,
            CURLOPT_TIMEOUT => 2,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => $headers
        ));

        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $action = str_pad($action, 42);
        $msg = "{$action}: {$code} => {$response}";
        if ($code != "200") {
            $this->logger->error($msg);
        } else {
            $this->logger->debug($msg);
        }

        return new jnapResult($response);
    }

    public function CheckAdminPassword() {
        try {
            $result = $this->PostAction('core/CheckAdminPassword');
            return $result->isSuccess();
        } catch (\Throwable $th) {
            $this->logger->error('core/CheckAdminPassword:' . $th->getMessage());
        }
        return false;
    }

    public function GetDeviceInfo() {
        return $this->PostAction('core/GetDeviceInfo');
    }

    public function GetGuestRadioSettings() {
        return $this->PostAction('guestnetwork/GetGuestRadioSettings');
    }

    public function GetParentalControlSettings() {
        return $this->PostAction('parentalcontrol/GetParentalControlSettings');
    }

    public function GetWANStatus() {
        return $this->PostAction('router/GetWANStatus');
    }

    public function GetRouterLEDSettings() {
        return $this->PostAction('routerleds/GetRouterLEDSettings');
    }

    public function GetFirmwareUpdateStatus() {
        return $this->PostAction('firmwareupdate/GetFirmwareUpdateStatus');
    }

    public function GetNetworkConnections() {
        return $this->PostAction('networkconnections/GetNetworkConnections');
    }

    public function GetDevices() {
        return $this->PostAction('devicelist/GetDevices3');
    }

    public function Reboot() {
        return $this->PostAction('core/Reboot');
    }

    public function UpdateFirmware() {
        return $this->PostAction('firmwareupdate/UpdateFirmwareNow');
    }

    public function SetParentalControlSettings($enable) {
        $response = $this->GetParentalControlSettings();
        if (!$response->isSuccess()) {
            return $response;
        } else {
            $rules = $response->getOutput()->rules;
            $params = array('isParentalControlEnabled' => $enable, 'rules' => $rules);
            return $this->PostAction('parentalcontrol/SetParentalControlSettings', $params);
        }
    }

    public function SetGuestRadioSettings($enable) {
        $response = $this->GetGuestRadioSettings();
        if (!$response->isSuccess()) {
            return $response;
        } else {
            $radios = $response->getOutput()->radios;
            $max = $response->getOutput()->maxSimultaneousGuests;
            $params = array('isGuestNetworkEnabled' => $enable, 'maxSimultaneousGuests' => $max, 'radios' => $radios);
            return $this->PostAction('guestnetwork/SetGuestRadioSettings', $params);
        }
    }

    public function SetRouterLEDSettings($enable) {
        $params = array('isSwitchportLEDEnabled' => $enable);
        return $this->PostAction('routerleds/SetRouterLEDSettings', $params);
    }
}
