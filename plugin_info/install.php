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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

// Fonction exécutée automatiquement après l'installation du plugin
  function linksys_install() {

  }

// Fonction exécutée automatiquement après la mise à jour du plugin
  function linksys_update() {
    foreach (eqLogic::byType('linksys') as $eqLogic) {
        
        $eqLogic->setDisplay('height','380px');
        
        $cmd = $eqLogic->getCmd(null, 'wanstatus');
        if ( ! is_object($cmd)) {
            $cmd = new linksysCmd();            
            $cmd->setLogicalId('wanstatus');
            $cmd->setEqLogic_id($eqLogic->getId());
            $cmd->setName('Connexion WAN');
            $cmd->setType('info');
            $cmd->setSubType('binary');
            $cmd->setEventOnly(1);
            $cmd->setIsHistorized(0);
            $cmd->setTemplate('mobile', 'line');
            $cmd->setTemplate('dashboard', 'line');
            $cmd->setOrder(3);
            $cmd->save();
        }
        
        $cmd = $eqLogic->getCmd(null, 'ledstatus');
        if (!is_object($cmd))
          {
            $cmd = new linksysCmd();
            $cmd->setLogicalId('ledstatus');
            $cmd->setEqLogic_id($eqLogic->getId());
            $cmd->setName('LEDs');
            $cmd->setType('info');
            $cmd->setSubType('binary');
            $cmd->setEventOnly(1);
            $cmd->setIsHistorized(0);
            $cmd->setTemplate('mobile', 'line');
            $cmd->setTemplate('dashboard', 'line');
            $cmd->setOrder(14);
            $cmd->save();
        }

        $cmd = $eqLogic->getCmd(null, 'setleds');
        if (!is_object($cmd))
        {
            $cmd = new linksysCmd();
            $cmd->setLogicalId('setleds');
            $cmd->setEqLogic_id($eqLogic->getId());
            $cmd->setName('Allumer LEDs');
            $cmd->setType('action');
            $cmd->setSubType('other');
            $cmd->setEventOnly(1);
            $cmd->setOrder(15);
            $cmd->save();
        }

        $cmd = $eqLogic->getCmd(null, 'unsetleds');
        if (!is_object($cmd))
        {
            $cmd = new linksysCmd();
            $cmd->setLogicalId('unsetleds');
            $cmd->setEqLogic_id($eqLogic->getId());
            $cmd->setName('Eteindre LEDs');
            $cmd->setType('action');
            $cmd->setSubType('other');
            $cmd->setEventOnly(1);
            $cmd->setOrder(16);
            $cmd->setDisplay('forceReturnLineAfter', 1);
            $cmd->save();
        }
        
        $cmd = $eqLogic->getCmd(null, 'newfirmware');
        if (!is_object($cmd))
        {
            $cmd = new linksysCmd();
            $cmd->setLogicalId('newfirmware');
            $cmd->setEqLogic_id($eqLogic->getId());
            $cmd->setName('Nouveau Firmware');
            $cmd->setType('info');
            $cmd->setSubType('binary');
            $cmd->setEventOnly(1);
            $cmd->setIsHistorized(0);
            $cmd->setTemplate('mobile', 'line');
            $cmd->setTemplate('dashboard', 'line');
            $cmd->setOrder(17);
            $cmd->save();
        }

        $cmd = $eqLogic->getCmd(null, 'updatefirmware');
        if (!is_object($cmd))
        {
            $cmd = new linksysCmd();
            $cmd->setLogicalId('updatefirmware');
            $cmd->setEqLogic_id($eqLogic->getId());
            $cmd->setName('Mise à jour Firmware');
            $cmd->setType('action');
            $cmd->setSubType('other');
            $cmd->setEventOnly(1);
            $cmd->setOrder(18);
            $cmd->setDisplay('forceReturnLineAfter', 1);
            $cmd->save();
        }
        
        
        
        $cmd = $eqLogic->getCmd(null, 'reboot');
        if (is_object($cmd)) {
            $cmd->setDisplay('forceReturnLineAfter', 1);
            $cmd->save();
        }
    
        $eqLogic->save();
    }
  }

// Fonction exécutée automatiquement après la suppression du plugin
  function linksys_remove() {

  }

?>
