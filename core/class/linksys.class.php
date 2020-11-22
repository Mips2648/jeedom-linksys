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
      $result = $this->executeLinksysCommand("devicelist/GetDevices3");
      $obj = json_decode($result);  
      
      if (!isset($obj->result) || $obj->result <> "OK") {
          log::add(__CLASS__, 'error', $this->getHumanName() . ' devicelist/GetDevices3:' . $obj->result);
          return;
      }
      
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
       log::add('linksys', 'debug', 'Execution de la commande' . $this->getLogicalId());
       switch ($this->getLogicalId()) {
           case "refresh":
               $eqLogic->pullLinksys();
               break;
           case "reboot":
               $eqLogic->rebootLinksys();
               break;
       }
     }

    /*     * **********************Getteur Setteur*************************** */
}
