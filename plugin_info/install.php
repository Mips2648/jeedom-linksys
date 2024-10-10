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

function InstallComposerDependencies(string $pluginId) {
    log::add($pluginId, 'info', 'Install composer dependencies');
    $cmd = 'cd ' . __DIR__ . '/../;export COMPOSER_ALLOW_SUPERUSER=1;export COMPOSER_HOME="/tmp/composer";' . system::getCmdSudo() . 'composer install --no-ansi --no-dev --no-interaction --no-plugins --no-progress --no-scripts --optimize-autoloader;' . system::getCmdSudo() . ' chown -R www-data:www-data *';
    shell_exec($cmd);
}

function linksys_install() {
    $pluginId = 'linksys';
    InstallComposerDependencies($pluginId);
}

function linksys_update() {
    $pluginId = 'linksys';
    InstallComposerDependencies($pluginId);
}

function linksys_remove() {
}
