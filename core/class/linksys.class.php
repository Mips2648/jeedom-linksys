<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once __DIR__  . '/../../../../core/php/core.inc.php';

class linksys extends eqLogic {

    public static function cron5() {
        $eqLogics = self::byType(__CLASS__, true);

        foreach ($eqLogics as $eqLogic) {
            $eqLogic->pullLinksys();
        }
    }

    public function pullLinksys() {
        log::add(__CLASS__, 'debug', $this->getHumanName() . ' Execution of pullLinksys');

        $result = $this->executeLinksysCommand("core/GetDeviceInfo");
        $obj = json_decode($result);

        if (!isset($obj->result) || $obj->result <> "OK") {
            log::add(__CLASS__, 'error', $this->getHumanName() . ' core/GetDeviceInfo:' . $obj->result);
        } else {
            if (isset($obj->output->manufacturer) && isset($obj->output->modelNumber)) {
                log::add(__CLASS__, 'debug', $this->getHumanName() . ' model: ' . $obj->output->manufacturer . ' ' . $obj->output->modelNumber);
                $cmd = $this->getCmd(null, 'model');
                $cmd->event($obj->output->manufacturer . ' ' . $obj->output->modelNumber);
            }
            if (isset($obj->output->firmwareVersion)) {
                log::add(__CLASS__, 'debug', $this->getHumanName() . ' firmware: ' . $obj->output->firmwareVersion);
                $cmd = $this->getCmd(null, 'firmware');
                $cmd->event($obj->output->firmwareVersion);
            }
        }

        $pullMethodRecorded = true;
        $pullMethod = $this->getConfiguration('pullMethod', '');
        if ($pullMethod == '') {
            log::add(__CLASS__, 'info', $this->getHumanName() . ' No recorded method for pull');
            $pullMethodRecorded = false;
            $pullMethod = "devicelist/GetDevices3";
        }

        $result = $this->executeLinksysCommand($pullMethod);
        $obj = json_decode($result);

        $pullResult = false;

        if (!isset($obj->result) || $obj->result <> "OK") {
            if ($pullMethodRecorded) {
                log::add(__CLASS__, 'error', $this->getHumanName() . ' ' . $pullMethod . ': ' . $obj->result);
            } else {
                $pullMethod = "networkconnections/GetNetworkConnections";
                $result = $this->executeLinksysCommand($pullMethod);
                $obj = json_decode($result);
                if (!isset($obj->result) || $obj->result <> "OK") {
                    log::add(__CLASS__, 'error', $this->getHumanName() . ' ' . $pullMethod . ': ' . $obj->result);
                } else {
                    $pullResult = true;
                }
            }
        } else {
            $pullResult = true;
        }

        if ($pullResult) {
            log::add(__CLASS__, 'debug', $this->getHumanName() . ' pull method used: ' . $pullMethod);
            if (!$pullMethodRecorded) {
                $this->setConfiguration('pullMethod', $pullMethod);
                $this->save();
                log::add(__CLASS__, 'info', $this->getHumanName() . ' pull method recorded: ' . $pullMethod);
            }
            if ($pullMethod == "devicelist/GetDevices3") {
                $parsing = $this->parseDeviceListResults($obj);
            } else {
                $parsing = $this->parseNetworkConnectionsResults($obj);
            }

            log::add(__CLASS__, 'debug', $this->getHumanName() . ' pullLinksys: wifi24: ' . $parsing["wifi24"] . ', wifi5: ' . $parsing["wifi5"] . ', wired: ' . $parsing["wired"]);

            $cmd = $this->getCmd(null, 'wifi24');
            $cmd->event($parsing["wifi24"]);
            $cmd = $this->getCmd(null, 'wifi5');
            $cmd->event($parsing["wifi5"]);
            $cmd = $this->getCmd(null, 'wired');
            $cmd->event($parsing["wired"]);
        }

        $result = $this->executeLinksysCommand("parentalcontrol/GetParentalControlSettings");
        $obj = json_decode($result);

        if (!isset($obj->result) || $obj->result <> "OK") {
            log::add(__CLASS__, 'error', $this->getHumanName() . ' parentalcontrol/GetParentalControlSettings:' . $obj->result);
        } else {
            if (isset($obj->output->isParentalControlEnabled)) {
                log::add(__CLASS__, 'debug', $this->getHumanName() . ' parentalstatus: ' . $obj->output->isParentalControlEnabled);
                $cmd = $this->getCmd(null, 'parentalstatus');
                $cmd->event($obj->output->isParentalControlEnabled);
            }
        }

        $result = $this->executeLinksysCommand("guestnetwork/GetGuestRadioSettings");
        $obj = json_decode($result);

        if (!isset($obj->result) || $obj->result <> "OK") {
            log::add(__CLASS__, 'error', $this->getHumanName() . ' guestnetwork/GetGuestRadioSettings:' . $obj->result);
        } else {
            if (isset($obj->output->isGuestNetworkEnabled)) {
                log::add(__CLASS__, 'debug', $this->getHumanName() . ' gueststatus: ' . $obj->output->isGuestNetworkEnabled);
                $cmd = $this->getCmd(null, 'gueststatus');
                $cmd->event($obj->output->isGuestNetworkEnabled);
            }
        }

        $result = $this->executeLinksysCommand("router/GetWANStatus");
        $obj = json_decode($result);

        if (!isset($obj->result) || $obj->result <> "OK") {
            log::add(__CLASS__, 'error', $this->getHumanName() . ' router/GetWANStatus:' . $obj->result);
        } else {
            if (isset($obj->output->wanStatus)) {
                log::add(__CLASS__, 'debug', $this->getHumanName() . ' wanstatus: ' . $obj->output->wanStatus);
                $wanok = false;
                if ($obj->output->wanStatus == "Connected") {
                    $wanok = true;
                }
                $cmd = $this->getCmd(null, 'wanstatus');
                $cmd->event($wanok);
            }
        }

        $result = $this->executeLinksysCommand("routerleds/GetRouterLEDSettings");
        $obj = json_decode($result);

        if (!isset($obj->result) || $obj->result <> "OK") {
            log::add(__CLASS__, 'error', $this->getHumanName() . ' routerleds/GetRouterLEDSettings:' . $obj->result);
        } else {
            if (isset($obj->output->isSwitchportLEDEnabled)) {
                log::add(__CLASS__, 'debug', $this->getHumanName() . ' ledstatus: ' . $obj->output->isSwitchportLEDEnabled);
                $cmd = $this->getCmd(null, 'ledstatus');
                $cmd->event($obj->output->isSwitchportLEDEnabled);
            }
        }

        $result = $this->executeLinksysCommand("firmwareupdate/GetFirmwareUpdateStatus");
        $obj = json_decode($result);

        if (!isset($obj->result) || $obj->result <> "OK") {
            log::add(__CLASS__, 'error', $this->getHumanName() . ' firmwareupdate/GetFirmwareUpdateStatus:' . $obj->result);
        } else {
            $cmd = $this->getCmd(null, 'newfirmware');
            if (isset($obj->output->availableUpdate)) {
                log::add(__CLASS__, 'debug', $this->getHumanName() . ' ledstatus: ' . $obj->output->availableUpdate);
                $cmd->event(true);
            } else {
                $cmd->event(false);
            }
        }
    }

    public function parseNetworkConnectionsResults($obj) {
        $connections = $obj->output->connections;
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
        return array("wired" => $wired, "wifi24" => $wifi24, "wifi5" => $wifi5);
    }

    public function parseDeviceListResults($obj) {
        $devices = $obj->output->devices;
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
        return array("wired" => $wired, "wifi24" => $wifi24, "wifi5" => $wifi5);
    }

    public function rebootLinksys() {
        log::add(__CLASS__, 'debug', $this->getHumanName() . ' Execution of rebootLinksys');
        $result = $this->executeLinksysCommand("core/Reboot");
        $obj = json_decode($result);
        if (!isset($obj->result) || $obj->result <> "OK") {
            log::add(__CLASS__, 'error', $this->getHumanName() . ' core/Reboot:' . $obj->result);
        } else {
            log::add(__CLASS__, 'debug', $this->getHumanName() . ' Reboot requested');
        }
    }

    public function configParental($onoff) {
        log::add(__CLASS__, 'debug', $this->getHumanName() . ' configParental ' . $onoff);
        $result = $this->executeLinksysCommand("parentalcontrol/GetParentalControlSettings");
        $obj = json_decode($result);
        if (!isset($obj->result) || $obj->result <> "OK") {
            log::add(__CLASS__, 'error', $this->getHumanName() . ' parentalcontrol/GetParentalControlSettings:' . $obj->result);
        } else {
            $rules = $obj->output->rules;
            $arr = array('isParentalControlEnabled' => $onoff, 'rules' => $rules);
            $json = json_encode($arr);
            $result = $this->executeLinksysCommand("parentalcontrol/SetParentalControlSettings", $json);
            $obj = json_decode($result);
            if (!isset($obj->result) || $obj->result <> "OK") {
                log::add(__CLASS__, 'error', $this->getHumanName() . ' parentalcontrol/SetParentalControlSettings:' . $obj->result);
            } else {
                log::add(__CLASS__, 'debug', $this->getHumanName() . ' Parental Control: ' . $onoff);
                $this->pullLinksys();
            }
        }
    }

    public function configGuest($onoff) {
        log::add(__CLASS__, 'debug', $this->getHumanName() . ' configGuest ' . $onoff);
        $result = $this->executeLinksysCommand("guestnetwork/GetGuestRadioSettings");
        $obj = json_decode($result);
        if (!isset($obj->result) || $obj->result <> "OK") {
            log::add(__CLASS__, 'error', $this->getHumanName() . ' guestnetwork/GetGuestRadioSettings:' . $obj->result);
        } else {
            $radios = $obj->output->radios;
            $max = $obj->output->maxSimultaneousGuests;
            $arr = array('isGuestNetworkEnabled' => $onoff, 'maxSimultaneousGuests' => $max, 'radios' => $radios);
            $json = json_encode($arr);
            $result = $this->executeLinksysCommand("guestnetwork/SetGuestRadioSettings", $json);
            $obj = json_decode($result);
            if (!isset($obj->result) || $obj->result <> "OK") {
                log::add(__CLASS__, 'error', $this->getHumanName() . ' guestnetwork/SetGuestRadioSettings:' . $obj->result);
            } else {
                log::add(__CLASS__, 'debug', $this->getHumanName() . ' Guest Control: ' . $onoff);
                $this->pullLinksys();
            }
        }
    }

    public function configLEDs($onoff) {
        log::add(__CLASS__, 'debug', $this->getHumanName() . ' configLEDs ' . $onoff);
        $arr = array('isSwitchportLEDEnabled' => $onoff);
        $json = json_encode($arr);
        $result = $this->executeLinksysCommand("routerleds/SetRouterLEDSettings", $json);
        $obj = json_decode($result);
        if (!isset($obj->result) || $obj->result <> "OK") {
            log::add(__CLASS__, 'error', $this->getHumanName() . ' routerleds/SetRouterLEDSettings:' . $obj->result);
        } else {
            log::add(__CLASS__, 'debug', $this->getHumanName() . ' LEDs Status: ' . $onoff);
            $this->pullLinksys();
        }
    }

    public function updateFirmware() {
        log::add(__CLASS__, 'debug', $this->getHumanName() . ' updateFirmware');
        $result = $this->executeLinksysCommand("firmwareupdate/UpdateFirmwareNow");
        $obj = json_decode($result);
        if (!isset($obj->result) || $obj->result <> "OK") {
            log::add(__CLASS__, 'error', $this->getHumanName() . ' firmwareupdate/UpdateFirmwareNow:' . $obj->result);
        } else {
            $this->pullLinksys();
        }
    }

    // Fonction exécutée automatiquement avant la création de l'équipement
    public function preInsert() {
        $this->setDisplay('height', '350px');
        $this->setDisplay('width', '384px');
        $this->setIsEnable(1);
        $this->setIsVisible(1);
    }

    // Fonction exécutée automatiquement avant la mise à jour de l'équipement
    public function preUpdate() {
        if (empty($this->getConfiguration('ip'))) {
            throw new Exception(__('L\'adresse IP du routeur doit être renseignée', __FILE__));
        }
        if (empty($this->getConfiguration('login'))) {
            throw new Exception(__('L\'identifiant du compte Admin doit être renseigné', __FILE__));
        }
        if (empty($this->getConfiguration('password'))) {
            throw new Exception(__('Le mot de passe du compte Admin doit être renseigné', __FILE__));
        }
        if (!filter_var($this->getConfiguration('ip'), FILTER_VALIDATE_IP)) {
            throw new Exception(__('L\'adresse IP a un format invalide', __FILE__));
        }
        $result = $this->executeFullLinksysCommand($this->getConfiguration('ip'), $this->getConfiguration('login'), $this->getConfiguration('password'), "core/CheckAdminPassword");
        $obj = json_decode($result);
        if (!isset($obj->result) || $obj->result <> "OK") {
            throw new Exception(__('Impossible de se connecter au routeur, vérifiez vos paramètres', __FILE__));
        }
    }

    // Fonction exécutée automatiquement après la mise à jour de l'équipement
    public function postUpdate() {

        $cmd = $this->getCmd(null, 'model');
        if (!is_object($cmd)) {
            log::add(__CLASS__, 'debug', $this->getHumanName() . ' Création commande : model/Modèle');
            $cmd = new linksysCmd();
            $cmd->setLogicalId('model');
            $cmd->setEqLogic_id($this->getId());
            $cmd->setName('Modèle');
            $cmd->setType('info');
            $cmd->setSubType('string');
            $cmd->setIsHistorized(0);
            $cmd->setOrder(1);
            $cmd->save();
        }

        $cmd = $this->getCmd(null, 'firmware');
        if (!is_object($cmd)) {
            log::add(__CLASS__, 'debug', $this->getHumanName() . ' Création commande : firmware/Firmware');
            $cmd = new linksysCmd();
            $cmd->setLogicalId('firmware');
            $cmd->setEqLogic_id($this->getId());
            $cmd->setName('Firmware');
            $cmd->setType('info');
            $cmd->setSubType('string');
            $cmd->setIsHistorized(0);
            $cmd->setDisplay('forceReturnLineAfter', 1);
            $cmd->setOrder(2);
            $cmd->save();
        }

        $cmd = $this->getCmd(null, 'wanstatus');
        if (!is_object($cmd)) {
            log::add(__CLASS__, 'debug', $this->getHumanName() . ' Création commande : wanstatus/Connexion WAN');
            $cmd = new linksysCmd();
            $cmd->setLogicalId('wanstatus');
            $cmd->setEqLogic_id($this->getId());
            $cmd->setName('Connexion WAN');
            $cmd->setType('info');
            $cmd->setSubType('binary');
            $cmd->setIsHistorized(0);
            $cmd->setTemplate('mobile', 'line');
            $cmd->setTemplate('dashboard', 'line');
            $cmd->setOrder(3);
            $cmd->save();
        }

        $cmd = $this->getCmd(null, 'wifi24');
        if (!is_object($cmd)) {
            log::add(__CLASS__, 'debug', $this->getHumanName() . ' Création commande : wifi24/Wifi 2.4GHz');
            $cmd = new linksysCmd();
            $cmd->setLogicalId('wifi24');
            $cmd->setEqLogic_id($this->getId());
            $cmd->setIsHistorized(1);
            $cmd->setDisplay('showStatsOndashboard', 0);
            $cmd->setDisplay('showStatsOnmobile', 0);
            $cmd->setTemplate('dashboard', 'tile');
            $cmd->setTemplate('mobile', 'tile');
            $cmd->setName('Wifi 2.4GHz');
            $cmd->setType('info');
            $cmd->setSubType('numeric');
            $cmd->setOrder(4);
            $cmd->save();
        }

        $cmd = $this->getCmd(null, 'wifi5');
        if (!is_object($cmd)) {
            log::add(__CLASS__, 'debug', $this->getHumanName() . ' Création commande : wifi5/Wifi 5GHz');
            $cmd = new linksysCmd();
            $cmd->setLogicalId('wifi5');
            $cmd->setEqLogic_id($this->getId());
            $cmd->setIsHistorized(1);
            $cmd->setDisplay('showStatsOndashboard', 0);
            $cmd->setDisplay('showStatsOndashboard', 0);
            $cmd->setDisplay('showStatsOnmobile', 0);
            $cmd->setTemplate('dashboard', 'tile');
            $cmd->setTemplate('mobile', 'tile');
            $cmd->setName('Wifi 5GHz');
            $cmd->setType('info');
            $cmd->setSubType('numeric');
            $cmd->setOrder(5);
            $cmd->save();
        }

        $cmd = $this->getCmd(null, 'wired');
        if (!is_object($cmd)) {
            log::add(__CLASS__, 'debug', $this->getHumanName() . ' Création commande : wired/Ethernet');
            $cmd = new linksysCmd();
            $cmd->setLogicalId('wired');
            $cmd->setEqLogic_id($this->getId());
            $cmd->setIsHistorized(1);
            $cmd->setDisplay('showStatsOndashboard', 0);
            $cmd->setDisplay('showStatsOndashboard', 0);
            $cmd->setDisplay('showStatsOnmobile', 0);
            $cmd->setTemplate('dashboard', 'tile');
            $cmd->setTemplate('mobile', 'tile');
            $cmd->setName('Ethernet');
            $cmd->setType('info');
            $cmd->setSubType('numeric');
            $cmd->setDisplay('forceReturnLineAfter', 1);
            $cmd->setOrder(6);
            $cmd->save();
        }

        $cmd = $this->getCmd(null, 'parentalstatus');
        if (!is_object($cmd)) {
            log::add(__CLASS__, 'debug', $this->getHumanName() . ' Création commande : parentalstatus/Contrôle Parental');
            $cmd = new linksysCmd();
            $cmd->setLogicalId('parentalstatus');
            $cmd->setEqLogic_id($this->getId());
            $cmd->setName('Contrôle Parental');
            $cmd->setType('info');
            $cmd->setSubType('binary');
            $cmd->setIsHistorized(0);
            $cmd->setTemplate('mobile', 'line');
            $cmd->setTemplate('dashboard', 'line');
            $cmd->setOrder(7);
            $cmd->save();
        }

        $cmd = $this->getCmd(null, 'setparental');
        if (!is_object($cmd)) {
            log::add(__CLASS__, 'debug', $this->getHumanName() . ' Création commande : setparental/Activer Contrôle Parental');
            $cmd = new linksysCmd();
            $cmd->setLogicalId('setparental');
            $cmd->setEqLogic_id($this->getId());
            $cmd->setName('Activer Contrôle Parental');
            $cmd->setType('action');
            $cmd->setSubType('other');
            $cmd->setOrder(8);
            $cmd->save();
        }

        $cmd = $this->getCmd(null, 'unsetparental');
        if (!is_object($cmd)) {
            log::add(__CLASS__, 'debug', $this->getHumanName() . ' Création commande : unsetparental/Désactiver Contrôle Parental');
            $cmd = new linksysCmd();
            $cmd->setLogicalId('unsetparental');
            $cmd->setEqLogic_id($this->getId());
            $cmd->setName('Désactiver Contrôle Parental');
            $cmd->setType('action');
            $cmd->setSubType('other');
            $cmd->setDisplay('forceReturnLineAfter', 1);
            $cmd->setOrder(9);
            $cmd->save();
        }

        $cmd = $this->getCmd(null, 'gueststatus');
        if (!is_object($cmd)) {
            log::add(__CLASS__, 'debug', $this->getHumanName() . ' Création commande : gueststatus/Réseau Invités');
            $cmd = new linksysCmd();
            $cmd->setLogicalId('gueststatus');
            $cmd->setEqLogic_id($this->getId());
            $cmd->setName('Réseau Invités');
            $cmd->setType('info');
            $cmd->setSubType('binary');
            $cmd->setIsHistorized(0);
            $cmd->setTemplate('mobile', 'line');
            $cmd->setTemplate('dashboard', 'line');
            $cmd->setOrder(10);
            $cmd->save();
        }

        $cmd = $this->getCmd(null, 'setguest');
        if (!is_object($cmd)) {
            log::add(__CLASS__, 'debug', $this->getHumanName() . ' Création commande : setguest/Activer Réseau Invités');
            $cmd = new linksysCmd();
            $cmd->setLogicalId('setguest');
            $cmd->setEqLogic_id($this->getId());
            $cmd->setName('Activer Réseau Invités');
            $cmd->setType('action');
            $cmd->setSubType('other');
            $cmd->setOrder(11);
            $cmd->save();
        }

        $cmd = $this->getCmd(null, 'unsetguest');
        if (!is_object($cmd)) {
            log::add(__CLASS__, 'debug', $this->getHumanName() . ' Création commande : unsetguest/Désactiver Réseau Invités');
            $cmd = new linksysCmd();
            $cmd->setLogicalId('unsetguest');
            $cmd->setEqLogic_id($this->getId());
            $cmd->setName('Désactiver Réseau Invités');
            $cmd->setType('action');
            $cmd->setSubType('other');
            $cmd->setOrder(12);
            $cmd->setDisplay('forceReturnLineAfter', 1);
            $cmd->save();
        }

        $cmd = $this->getCmd(null, 'reboot');
        if (!is_object($cmd)) {
            log::add(__CLASS__, 'debug', $this->getHumanName() . ' Création commande : reboot/Reboot');
            $cmd = new linksysCmd();
            $cmd->setLogicalId('reboot');
            $cmd->setEqLogic_id($this->getId());
            $cmd->setName('Reboot');
            $cmd->setType('action');
            $cmd->setSubType('other');
            $cmd->setOrder(13);
            $cmd->save();
        }

        $cmd = $this->getCmd(null, 'refresh');
        if (!is_object($cmd)) {
            log::add(__CLASS__, 'debug', $this->getHumanName() . ' Création commande : refresh/Rafraichir');
            $cmd = new linksysCmd();
            $cmd->setLogicalId('refresh');
            $cmd->setEqLogic_id($this->getId());
            $cmd->setName('Rafraichir');
            $cmd->setType('action');
            $cmd->setSubType('other');
            $cmd->setOrder(20);
            $cmd->save();
        }

        $cmd = $this->getCmd(null, 'ledstatus');
        if (!is_object($cmd)) {
            log::add(__CLASS__, 'debug', $this->getHumanName() . ' Création commande : ledstatus/LEDs');
            $cmd = new linksysCmd();
            $cmd->setLogicalId('ledstatus');
            $cmd->setEqLogic_id($this->getId());
            $cmd->setName('LEDs');
            $cmd->setType('info');
            $cmd->setSubType('binary');
            $cmd->setIsHistorized(0);
            $cmd->setTemplate('mobile', 'line');
            $cmd->setTemplate('dashboard', 'line');
            $cmd->setOrder(14);
            $cmd->save();
        }

        $cmd = $this->getCmd(null, 'setleds');
        if (!is_object($cmd)) {
            log::add(__CLASS__, 'debug', $this->getHumanName() . ' Création commande : setleds/Allumer LEDs');
            $cmd = new linksysCmd();
            $cmd->setLogicalId('setleds');
            $cmd->setEqLogic_id($this->getId());
            $cmd->setName('Allumer LEDs');
            $cmd->setType('action');
            $cmd->setSubType('other');
            $cmd->setOrder(15);
            $cmd->save();
        }

        $cmd = $this->getCmd(null, 'unsetleds');
        if (!is_object($cmd)) {
            log::add(__CLASS__, 'debug', $this->getHumanName() . ' Création commande : unsetguest/Eteindre LEDs');
            $cmd = new linksysCmd();
            $cmd->setLogicalId('unsetleds');
            $cmd->setEqLogic_id($this->getId());
            $cmd->setName('Eteindre LEDs');
            $cmd->setType('action');
            $cmd->setSubType('other');
            $cmd->setOrder(16);
            $cmd->setDisplay('forceReturnLineAfter', 1);
            $cmd->save();
        }

        $cmd = $this->getCmd(null, 'newfirmware');
        if (!is_object($cmd)) {
            log::add(__CLASS__, 'debug', $this->getHumanName() . ' Création commande : newfirmware/Nouveau Firmware');
            $cmd = new linksysCmd();
            $cmd->setLogicalId('newfirmware');
            $cmd->setEqLogic_id($this->getId());
            $cmd->setName('Nouveau Firmware');
            $cmd->setType('info');
            $cmd->setSubType('binary');
            $cmd->setIsHistorized(0);
            $cmd->setTemplate('mobile', 'line');
            $cmd->setTemplate('dashboard', 'line');
            $cmd->setOrder(17);
            $cmd->save();
        }

        $cmd = $this->getCmd(null, 'updatefirmware');
        if (!is_object($cmd)) {
            log::add(__CLASS__, 'debug', $this->getHumanName() . ' Création commande : updatefirmware/Update Firmware');
            $cmd = new linksysCmd();
            $cmd->setLogicalId('updatefirmware');
            $cmd->setEqLogic_id($this->getId());
            $cmd->setName('Mise à jour Firmware');
            $cmd->setType('action');
            $cmd->setSubType('other');
            $cmd->setOrder(18);
            $cmd->setDisplay('forceReturnLineAfter', 1);
            $cmd->save();
        }

        if ($this->getIsEnable() == 1) {
            $this->pullLinksys();
        }
    }

    public function executeFullLinksysCommand($ip, $login, $password, $action, $params = '{}') {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://" . $ip . "/JNAP/",
            CURLOPT_HEADER  => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $params,
            CURLOPT_HTTPHEADER => array(
                "User-Agent: Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Mobile Safari/537.36",
                "Accept-Encoding: gzip, deflate, br",
                "Accept: */*",
                "Content-Type: application/json",
                "X-JNAP-Authorization: Basic " . base64_encode($login . ":" . $password),
                "X-JNAP-Action: http://linksys.com/jnap/" . $action
            )
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    public function executeLinksysCommand($action, $params = '{}') {
        $ip = $this->getConfiguration('ip');
        $login = $this->getConfiguration('login');
        $password = $this->getConfiguration('password');
        return $this->executeFullLinksysCommand($ip, $login, $password, $action, $params);
    }
}

class linksysCmd extends cmd {

    // Exécution d'une commande
    public function execute($_options = array()) {
        $eqLogic = $this->getEqLogic();
        if (!is_object($eqLogic) || $eqLogic->getIsEnable() != 1) {
            throw new Exception(__('Equipement desactivé impossible d\éxecuter la commande : ' . $this->getHumanName(), __FILE__));
        }
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
