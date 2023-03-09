<?php

require_once __DIR__  . '/../../../../core/php/core.inc.php';
require_once dirname(__FILE__) . '/../../vendor/autoload.php';
require_once __DIR__  . '/jnapApi.class.php';

class linksys extends eqLogic {
    use MipsEqLogicTrait;

    private $_client = null;
    private function getClient() {
        return $_client ?? $this->_client = new jnapClient($this->getConfiguration('ip'), $this->getConfiguration('login', 'admin'), $this->getConfiguration('password'), log::getLogger(__CLASS__));
    }

    public static function cron() {
        /** @var linksys */
        foreach (eqLogic::byType(__CLASS__, true) as $eqLogic) {
            $autorefresh = $eqLogic->getConfiguration('autorefresh', '*/5 * * * *');
            $cronIsDue = false;
            try {
                $cron = new Cron\CronExpression($autorefresh, new Cron\FieldFactory);
                $cronIsDue = $cron->isDue();
            } catch (Exception $e) {
                log::add(__CLASS__, 'error', __('Expression cron non valide: ', __FILE__) . $autorefresh);
            }
            try {
                if ($cronIsDue) {
                    $eqLogic->pullLinksys();
                }
            } catch (\Throwable $th) {
                log::add(__CLASS__, 'error', $th->getMessage());
            }
        }
    }

    private function tryGetNetworkConnections() {
        $result = $this->getClient()->GetNetworkConnections();
        if (!$result->isSuccess()) {
            return $result->getResult();
        } else {
            $output = $result->getOutput();

            $connections = $output->connections;
            $wifi24 = 0;
            $wifi5 = 0;
            $wired = 0;
            foreach ($connections as $connection) {
                if (isset($connection->wireless)) {
                    if (isset($connection->wireless->band)) {
                        if ($connection->wireless->band == "2.4GHz") {
                            $wifi24++;
                        } else {
                            $wifi5++;
                        }
                    }
                } else {
                    $wired++;
                }
            }
            log::add(__CLASS__, 'debug', $this->getHumanName() . ' networkConnections: wifi24: ' . $wifi24 . ', wifi5: ' . $wifi5 . ', wired: ' . $wired);

            $this->checkAndUpdateCmd('wifi24', $wifi24);
            $this->checkAndUpdateCmd('wifi5', $wifi5);
            $this->checkAndUpdateCmd('wired', $wired);
            return true;
        }
    }

    private function tryGetDevicelist() {
        $result = $this->getClient()->GetDevices();
        if (!$result->isSuccess()) {
            return $result->getResult();
        } else {
            $output = $result->getOutput();

            $devices = $output->devices;
            $wifi24 = 0;
            $wifi5 = 0;
            $wired = 0;
            foreach ($devices as $device) {
                if (isset($device->connections[0]->ipAddress)) {
                    if (isset($device->knownInterfaces[0]->interfaceType)) {
                        if ($device->knownInterfaces[0]->interfaceType == "Wireless") {
                            if ($device->knownInterfaces[0]->band == "2.4GHz") {
                                $wifi24++;
                            } else {
                                $wifi5++;
                            }
                        } else {
                            $wired++;
                        }
                    }
                }
            }
            log::add(__CLASS__, 'debug', $this->getHumanName() . ' devices: wifi24: ' . $wifi24 . ', wifi5: ' . $wifi5 . ', wired: ' . $wired);

            $this->checkAndUpdateCmd('wifi24', $wifi24);
            $this->checkAndUpdateCmd('wifi5', $wifi5);
            $this->checkAndUpdateCmd('wired', $wired);
            return true;
        }
    }

    private function updateDevicesCounter() {
        $resultNetworkConnections = $this->tryGetNetworkConnections();
        $resultDevices = $this->tryGetDevicelist();
        if ($resultNetworkConnections !== true || $resultDevices !== true) {
              log::add(__CLASS__, 'error', $this->getHumanName() . ' networkConnections:' . $resultNetworkConnections->getResult());
              log::add(__CLASS__, 'error', $this->getHumanName() . ' devices:' . $resultDevices->getResult());
        }
    }

    private function updateParentalStatus() {
        $result = $this->getClient()->GetParentalControlSettings();
        if (!$result->isSuccess()) {
            log::add(__CLASS__, 'error', $this->getHumanName() . ' parentalstatus:' . $result->getResult());
        } else {
            $output = $result->getOutput();
            if (isset($output->isParentalControlEnabled)) {
                $this->checkAndUpdateCmd('parentalstatus', $output->isParentalControlEnabled);
            }
        }
    }

    private function updateGuestStatus() {
        $result = $this->getClient()->GetGuestRadioSettings();
        if (!$result->isSuccess()) {
            log::add(__CLASS__, 'error', $this->getHumanName() . ' gueststatus:' . $result->getResult());
        } else {
            $output = $result->getOutput();
            if (isset($output->isGuestNetworkEnabled)) {
                $this->checkAndUpdateCmd('gueststatus', $output->isGuestNetworkEnabled);
            }
        }
    }

    private function updateWanStatus() {
        $result = $this->getClient()->GetWANStatus();
        if (!$result->isSuccess()) {
            log::add(__CLASS__, 'error', $this->getHumanName() . ' wanstatus:' . $result->getResult());
        } else {
            $output = $result->getOutput();
            if (isset($output->wanStatus)) {
                $this->checkAndUpdateCmd('wanstatus', ($output->wanStatus === 'Connected'));
            }
            if (isset($output->wanConnection->wanType)) {
                $this->checkAndUpdateCmd('wanType', $output->wanConnection->wanType);
            }
        }
    }

    private function updateLEDStatus() {
        $result = $this->getClient()->GetRouterLEDSettings();
        if (!$result->isSuccess()) {
            log::add(__CLASS__, 'error', $this->getHumanName() . ' ledstatus:' . $result->getResult());
        } else {
            $output = $result->getOutput();
            if (isset($output->isSwitchportLEDEnabled)) {
                $this->checkAndUpdateCmd('ledstatus', $output->isSwitchportLEDEnabled);
            }
        }
    }

    private function updateNewfirmware() {
        $result = $this->getClient()->GetFirmwareUpdateStatus();
        if (!$result->isSuccess()) {
            log::add(__CLASS__, 'error', $this->getHumanName() . ' newfirmware:' . $result->getResult());
        } else {
            $output = $result->getOutput();
            if (isset($output->availableUpdate)) {
                $this->checkAndUpdateCmd('newfirmware', $output->availableUpdate);
            }
        }
    }

    public function pullLinksys() {
        log::add(__CLASS__, 'info', 'Refresh ' . $this->getHumanName());

        $this->updateDevicesCounter();

        $this->updateParentalStatus();
        $this->updateGuestStatus();
        $this->updateWanStatus();
        $this->updateLEDStatus();
        $this->updateNewfirmware();
    }

    public function rebootLinksys() {
        log::add(__CLASS__, 'info', 'Reboot ' . $this->getHumanName());
        $response = $this->getClient()->Reboot();

        if (!$response->isSuccess()) {
            log::add(__CLASS__, 'error', $this->getHumanName() . ' core/Reboot:' . $response->getResult());
        } else {
            log::add(__CLASS__, 'debug', $this->getHumanName() . ' Reboot requested');
        }
    }

    public function configParental($onoff) {
        log::add(__CLASS__, 'info', 'set parental status of ' . $this->getHumanName() . ' to ' . $onoff ? 'true' : 'false');

        $response = $this->getClient()->SetParentalControlSettings($onoff);
        if (!$response->isSuccess()) {
            log::add(__CLASS__, 'error', $this->getHumanName() . ' set parentalstatus:' . $response->getResult());
        } else {
            $this->updateParentalStatus();
        }
    }

    public function configGuest($onoff) {
        log::add(__CLASS__, 'info', 'set guest status of ' . $this->getHumanName() . ' to ' . $onoff ? 'true' : 'false');

        $response = $this->getClient()->SetGuestRadioSettings($onoff);
        if (!$response->isSuccess()) {
            log::add(__CLASS__, 'error', $this->getHumanName() . ' set gueststatus:' . $response->getResult());
        } else {
            $this->updateGuestStatus();
        }
    }

    public function configLEDs($onoff) {
        log::add(__CLASS__, 'info', 'set led status of ' . $this->getHumanName() . ' to ' . $onoff ? 'true' : 'false');

        $response = $this->getClient()->SetRouterLEDSettings($onoff);
        if (!$response->isSuccess()) {
            log::add(__CLASS__, 'error', $this->getHumanName() . ' set LEDstatus:' . $response->getResult());
        } else {
            $this->updateLEDStatus();
        }
    }

    public function updateFirmware() {
        log::add(__CLASS__, 'info', 'update firmware of ' . $this->getHumanName());
        $response = $this->getClient()->UpdateFirmware();
        if (!$response->isSuccess()) {
            log::add(__CLASS__, 'error', $this->getHumanName() . ' updateFirmware:' . $response->getResult());
        }
    }

    public function preUpdate() {
        if (empty($this->getConfiguration('ip'))) {
            throw new Exception(__('L\'adresse IP du routeur doit être renseignée', __FILE__));
        }
        if (empty($this->getConfiguration('password'))) {
            throw new Exception(__('Le mot de passe du compte Admin doit être renseigné', __FILE__));
        }
        if (!filter_var($this->getConfiguration('ip'), FILTER_VALIDATE_IP)) {
            throw new Exception(__('L\'adresse IP a un format invalide', __FILE__));
        }

        if (!$this->getClient()->CheckAdminPassword()) {
            throw new Exception(__('Impossible de se connecter au routeur, vérifiez vos paramètres', __FILE__));
        }

        $result = $this->getClient()->GetDeviceInfo();

        if (!$result->isSuccess()) {
            log::add(__CLASS__, 'error', $this->getHumanName() . ' core/GetDeviceInfo:' . $result->getResult());
        } else {
            $output = $result->getOutput();

            $this->setConfiguration('manufacturer', $output->{'manufacturer'});
            $this->setConfiguration('modelNumber', $output->{'modelNumber'});
            $this->setConfiguration('hardwareVersion', $output->{'hardwareVersion'});
            $this->setConfiguration('description', $output->{'description'});
            $this->setConfiguration('serialNumber', $output->{'serialNumber'});
            $this->setConfiguration('firmwareVersion', $output->{'firmwareVersion'});
            $this->setConfiguration('firmwareDate', $output->{'firmwareDate'});
        }
    }

    public function postUpdate() {
        $this->createCommandsFromConfigFile(__DIR__ . '/../config/commands.json', 'linksys');

        if ($this->getIsEnable() == 1) {
            self::executeAsync('pullLinksysAsync', array('eqLogic_id' => intval($this->getId())));
        }
    }

    public function pullLinksysAsync($_options) {
        /** @var linksys $eqLogic */
        $eqLogic = eqLogic::byId($_options['eqLogic_id']);
        $eqLogic->pullLinksys();
    }
}

class linksysCmd extends cmd {

    public function execute($_options = array()) {
        /** @var linksys */
        $eqLogic = $this->getEqLogic();
        log::add('linksys', 'debug', 'Execution de la commande ' . $this->getLogicalId());
        switch ($this->getLogicalId()) {
            case "refresh":
                $eqLogic->pullLinksys();
                break;
            case "reboot":
                $eqLogic->rebootLinksys();
                break;
            case "setparental":
                $eqLogic->configParental(true);
                break;
            case "unsetparental":
                $eqLogic->configParental(false);
                break;
            case "setguest":
                $eqLogic->configGuest(true);
                break;
            case "unsetguest":
                $eqLogic->configGuest(false);
                break;
            case "setleds":
                $eqLogic->configLEDs(true);
                break;
            case "unsetleds":
                $eqLogic->configLEDs(false);
                break;
            case "updatefirmware":
                $eqLogic->updateFirmware();
                break;
        }
    }
}
