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
    /*     * *************************Attributs****************************** */

  /*
   * Permet de définir les possibilités de personnalisation du widget (en cas d'utilisation de la fonction 'toHtml' par exemple)
   * Tableau multidimensionnel - exemple: array('custom' => true, 'custom::layout' => false)
	public static $_widgetPossibility = array();
   */

    /*     * ***********************Methode static*************************** */

  public static function cron5()
  {
    $eqLogics = self::byType(__CLASS__, true);

    foreach ($eqLogics as $eqLogic)
    {
        $eqLogic->pullLinksys();
    }
  }

    /*     * *********************Méthodes d'instance************************* */

    public function pullLinksys()
    {
      log::add(__CLASS__, 'debug', $this->getHumanName() . ' Execution of pullLinksys');
    
      $result = $this->executeLinksysCommand("core/GetDeviceInfo");
      $obj = json_decode($result);  
      
      if (!isset($obj->result) || $obj->result <> "OK") {
          log::add(__CLASS__, 'error', $this->getHumanName() . ' core/GetDeviceInfo:' . $obj->result);
      }
      else {
          if (isset($obj->output->manufacturer) && isset($obj->output->modelNumber)) {
              log::add(__CLASS__, 'debug', $this->getHumanName() . ' model: ' . $obj->output->manufacturer . ' ' . $obj->output->modelNumber);
              $cmd = $this->getCmd(null, 'model');
              $cmd->event($obj->output->manufacturer . ' ' . $obj->output->modelNumber);
          }
          if (isset($obj->output->firmwareVersion))) {
              log::add(__CLASS__, 'debug', $this->getHumanName() . ' firmware: ' . $obj->output->firmwareVersion);
              $cmd = $this->getCmd(null, 'firmware');
              $cmd->event($obj->output->firmwareVersion);
          }
      }
          
      $result = $this->executeLinksysCommand("devicelist/GetDevices3");
      $obj = json_decode($result);  
      
      if (!isset($obj->result) || $obj->result <> "OK") {
          log::add(__CLASS__, 'error', $this->getHumanName() . ' devicelist/GetDevices3:' . $obj->result);
      } 
      else {      
          $devices = $obj->output->devices;

          $wifi24 = 0;
          $wifi5 = 0;
          $wired = 0;

          foreach($devices as $device) {
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

          log::add(__CLASS__, 'debug', $this->getHumanName() . ' pullLinksys: wifi24: ' . $wifi24 . ', wifi5: ' . $wifi5 . ', wired: ' . $wired);

          $cmd = $this->getCmd(null, 'wifi24');
          $cmd->event($wifi24);
          $cmd = $this->getCmd(null, 'wifi5');
          $cmd->event($wifi5);
          $cmd = $this->getCmd(null, 'wired');
          $cmd->event($wired);
      }
        
      $result = $this->executeLinksysCommand("parentalcontrol/GetParentalControlSettings");
      $obj = json_decode($result);  
      
      if (!isset($obj->result) || $obj->result <> "OK") {
          log::add(__CLASS__, 'error', $this->getHumanName() . ' parentalcontrol/GetParentalControlSettings:' . $obj->result);
      }
      else {
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
      }
      else {
          if (isset($obj->output->isGuestNetworkEnabled)) {
              log::add(__CLASS__, 'debug', $this->getHumanName() . ' gueststatus: ' . $obj->output->isGuestNetworkEnabled);
              $cmd = $this->getCmd(null, 'gueststatus');
              $cmd->event($obj->output->isGuestNetworkEnabled);
          }
      }
    
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
 
 // Fonction exécutée automatiquement avant la création de l'équipement
    public function preInsert() {
      $this->setDisplay('height','332px');
      $this->setDisplay('width', '192px');
      $this->setIsEnable(1);
      $this->setIsVisible(1);
    }

 // Fonction exécutée automatiquement avant la mise à jour de l'équipement
    public function preUpdate() {
      if (empty($this->getConfiguration('ip'))) {
        throw new Exception(__('L\'adresse IP du routeur doit être renseignée',__FILE__));
      }
      if (empty($this->getConfiguration('login'))) {
        throw new Exception(__('L\'identifiant du compte Admin doit être renseigné',__FILE__));
      }
      if (empty($this->getConfiguration('password'))) {
        throw new Exception(__('Le mot de passe du compte Admin doit être renseigné',__FILE__));
      }
      if (!filter_var($this->getConfiguration('ip'), FILTER_VALIDATE_IP)) {
        throw new Exception(__('L\'adresse IP a un format invalide',__FILE__));
      }
      $result = $this->executeFullLinksysCommand($this->getConfiguration('ip'), $this->getConfiguration('login'), $this->getConfiguration('password'), "core/CheckAdminPassword");
      $obj = json_decode($result);  
      if (!isset($obj->result) || $obj->result <> "OK") {
          throw new Exception(__('Impossible de se connecter au routeur, vérifiez vos paramètres',__FILE__));
      }
    }

 // Fonction exécutée automatiquement après la mise à jour de l'équipement
    public function postUpdate() {
      $cmdInfos = [
    		'wifi24' => 'Wifi 2.4GHz',
    		'wifi5' => 'Wifi 5GHz',
            'wired' => 'Ethernet'
    	];

      foreach ($cmdInfos as $logicalId => $name)
      {
        $cmd = $this->getCmd(null, $logicalId);
        if (!is_object($cmd))
        {
          log::add(__CLASS__, 'debug', $this->getHumanName() . ' Création commande :'.$logicalId.'/'.$name);
  		  $cmd = new linksysCmd();
          $cmd->setLogicalId($logicalId);
          $cmd->setEqLogic_id($this->getId());
          $cmd->setIsHistorized(1);
          $cmd->setDisplay('showStatsOndashboard', 0);
          $cmd->setDisplay('showStatsOnmobile', 0);
          $cmd->setTemplate('dashboard','tile');
          $cmd->setTemplate('mobile','tile');
          $cmd->setName($name);
          $cmd->setType('info');
          $cmd->setSubType('numeric');
          $cmd->save();
        }
      }
      
      $cmd = $this->getCmd(null, 'refresh');
      if (!is_object($cmd))
      {
        log::add(__CLASS__, 'debug', $this->getHumanName() . ' Création commande : refresh/Rafraichir');
  		$cmd = new linksysCmd();
        $cmd->setLogicalId('refresh');
        $cmd->setEqLogic_id($this->getId());
        $cmd->setName('Rafraichir');
        $cmd->setType('action');
        $cmd->setSubType('other');
        $cmd->setEventOnly(1);
        $cmd->save();
      }

      $cmd = $this->getCmd(null, 'reboot');
      if (!is_object($cmd))
      {
        log::add(__CLASS__, 'debug', $this->getHumanName() . ' Création commande : reboot/Reboot');
  		$cmd = new linksysCmd();
        $cmd->setLogicalId('reboot');
        $cmd->setEqLogic_id($this->getId());
        $cmd->setName('Reboot');
        $cmd->setType('action');
        $cmd->setSubType('other');
        $cmd->setEventOnly(1);
        $cmd->save();
      }
      
      $cmd = $this->getCmd(null, 'gueststatus');
      if (!is_object($cmd))
      {
        log::add(__CLASS__, 'debug', $this->getHumanName() . ' Création commande : gueststatus/Réseau Invités');
  		$cmd = new linksysCmd();
        $cmd->setLogicalId('gueststatus');
        $cmd->setEqLogic_id($this->getId());
        $cmd->setName('Réseau Invités');
        $cmd->setType('info');
        $cmd->setSubType('binary');
        $cmd->setEventOnly(1);
        $cmd->setIsHistorized(0);
        $cmd->save();
      }
      
      $cmd = $this->getCmd(null, 'parentalstatus');
      if (!is_object($cmd))
      {
        log::add(__CLASS__, 'debug', $this->getHumanName() . ' Création commande : parentalstatus/Contrôle Parental');
  		$cmd = new linksysCmd();
        $cmd->setLogicalId('parentalstatus');
        $cmd->setEqLogic_id($this->getId());
        $cmd->setName('Contrôle Parental');
        $cmd->setType('info');
        $cmd->setSubType('binary');
        $cmd->setEventOnly(1);
        $cmd->setIsHistorized(0);
        $cmd->save();
      }
      
      $cmd = $this->getCmd(null, 'setparental');
      if (!is_object($cmd))
      {
        log::add(__CLASS__, 'debug', $this->getHumanName() . ' Création commande : setparental/Activer Contrôle Parental');
  		$cmd = new linksysCmd();
        $cmd->setLogicalId('setparental');
        $cmd->setEqLogic_id($this->getId());
        $cmd->setName('Activer Contrôle Parental');
        $cmd->setType('action');
        $cmd->setSubType('other');
        $cmd->setEventOnly(1);
        $cmd->save();
      }
        
      $cmd = $this->getCmd(null, 'unsetparental');
      if (!is_object($cmd))
      {
        log::add(__CLASS__, 'debug', $this->getHumanName() . ' Création commande : unsetparental/Désactiver Contrôle Parental');
  		$cmd = new linksysCmd();
        $cmd->setLogicalId('unsetparental');
        $cmd->setEqLogic_id($this->getId());
        $cmd->setName('Désactiver Contrôle Parental');
        $cmd->setType('action');
        $cmd->setSubType('other');
        $cmd->setEventOnly(1);
        $cmd->save();
      }
        
      $cmd = $this->getCmd(null, 'setguest');
      if (!is_object($cmd))
      {
        log::add(__CLASS__, 'debug', $this->getHumanName() . ' Création commande : setguest/Activer Réseau Invités');
  		$cmd = new linksysCmd();
        $cmd->setLogicalId('setguest');
        $cmd->setEqLogic_id($this->getId());
        $cmd->setName('Activer Réseau Invités');
        $cmd->setType('action');
        $cmd->setSubType('other');
        $cmd->setEventOnly(1);
        $cmd->save();
      }
        
      $cmd = $this->getCmd(null, 'unsetguest');
      if (!is_object($cmd))
      {
        log::add(__CLASS__, 'debug', $this->getHumanName() . ' Création commande : unsetguest/Désactiver Réseau Invités');
  		$cmd = new linksysCmd();
        $cmd->setLogicalId('unsetguest');
        $cmd->setEqLogic_id($this->getId());
        $cmd->setName('Désactiver Réseau Invités');
        $cmd->setType('action');
        $cmd->setSubType('other');
        $cmd->setEventOnly(1);
        $cmd->save();
      }

      $cmd = $this->getCmd(null, 'model');
      if (!is_object($cmd))
      {
        log::add(__CLASS__, 'debug', $this->getHumanName() . ' Création commande : model/Modèle');
  		$cmd = new linksysCmd();
        $cmd->setLogicalId('model');
        $cmd->setEqLogic_id($this->getId());
        $cmd->setName('Modèle');
        $cmd->setType('info');
        $cmd->setSubType('other');
        $cmd->setEventOnly(1);
        $cmd->setIsHistorized(0);
        $cmd->save();
      }

      $cmd = $this->getCmd(null, 'firmware');
      if (!is_object($cmd))
      {
        log::add(__CLASS__, 'debug', $this->getHumanName() . ' Création commande : firmware/Firmware');
  		$cmd = new linksysCmd();
        $cmd->setLogicalId('firmware');
        $cmd->setEqLogic_id($this->getId());
        $cmd->setName('Firmware');
        $cmd->setType('info');
        $cmd->setSubType('other');
        $cmd->setEventOnly(1);
        $cmd->setIsHistorized(0);
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
                "X-JNAP-Authorization: Basic " . base64_encode($login.":".$password),
				"X-JNAP-Action: http://linksys.com/jnap/" . $action)
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
    /*     * *************************Attributs****************************** */

    /*
      public static $_widgetPossibility = array();
    */

    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */

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
       }
     }

    /*     * **********************Getteur Setteur*************************** */
}
