<?php
/*
* Facebook Connect - a module for prestashop +1.5
* Copyright (C) 2013 Undershell.
*
* This file is part of FaceTheBook.
*
* FaceTheBook is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* FaceTheBook is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Foobar.  If not, see <http://www.gnu.org/licenses/>.
*/

if (!defined('_PS_VERSION_'))
	exit;

class FacebookConnect extends Module
{
	public function __construct()
	{
		$this->name = 'facebookconnect';
		$this->tab = 'front_office_features';
		$this->version = 0.1;
		$this->author = 'Undershell';

		parent::__construct();

		$this->displayName = $this->l('Facebook Connect');
		$this->description = $this->l('Adds a block for customer to login or register via Facebook Connect');
	}

	public function install()
	{

		if (version_compare( substr(_PS_VERSION_, 0, 3), '1.5', '!=')){
			$this->_errors[] = $this->l('The version of your module is not compliant with your PrestaShop version.');
			return false; 
		}
		

		if(!Db::getInstance()->executes("SHOW COLUMNS FROM `"._DB_PREFIX_."customer` WHERE Field ='fb_uid';"))
		if(!Db::getInstance()->execute("ALTER TABLE `"._DB_PREFIX_."customer` ADD COLUMN `fb_uid` BIGINT DEFAULT 0 AFTER `id_customer`;"))
			return false;

		if (!parent::install() || !$this->registerHook('header') || !$this->registerHook('top') || !$this->registerHook('createaccounttop'))
			return false;

		return true;
	}
	
	public function uninstall()
	{
		Configuration::deleteByName('FB_APPID');
		Configuration::deleteByName('FB_SECRET');		
		return (parent::uninstall());
	}
	
	public function getContent()
	{
		$output = '<h2>'.$this->displayName.'</h2>';
		if (Tools::isSubmit('submitFBConfiguration'))
		{
			$appid = (Tools::getValue('appid'));
			if (!$appid)
				$errors[] = $this->l('Invalid Facebook AppID');
			else
				Configuration::updateValue('FB_APPID', $appid);
				
			$fbsecret = (Tools::getValue('fbsecret'));
			if (!$fbsecret)
				$errors[] = $this->l('Invalid Facebook App Secret');
			else
				Configuration::updateValue('FB_SECRET', $fbsecret);
				
			if (isset($errors) AND count($errors))
				$output .= $this->displayError(implode('<br />', $errors));
			else
				$output .= $this->displayConfirmation($this->l('Settings updated'));
		}
		return $output.$this->displayForm();
	}

	public function displayForm()
	{
		$output = '
		<form action="'.Tools::safeOutput($_SERVER['REQUEST_URI']).'" method="post">
			<fieldset><legend><img src="'.$this->_path.'logo.gif" alt="" title="" />'.$this->l('Settings').'</legend>
				<p>'.$this->l('You need to create a Facebook application to get your <b>AppID</b> and its <b>App Secret</b>').'.</p><br />
				<p>'.$this->l('Your Facebook AppID').'</p><br />
				<label>'.$this->l('Facebook AppID').'</label>
				<div class="margin-form">
					<input type="text" size="20" name="appid" value="'.Tools::getValue('appid', Configuration::get('FB_APPID')).'" />

				</div>

				<p>'.$this->l('Your Facebook App Secret').'</p><br />
				<label>'.$this->l('Facebook App Secret').'</label>
				<div class="margin-form">
					<input type="text" size="40" name="fbsecret" value="'.Tools::getValue('fbsecret', Configuration::get('FB_SECRET')).'" />

				</div>
				<center><input type="submit" name="submitFBConfiguration" value="'.$this->l('Save').'" class="button" /></center>
			</fieldset>
		</form>';
		return $output;
	}

	public function hookTop($params)
	{	
		$this->context->smarty->assign(array(
			'appid' => (Configuration::get('FB_APPID')),
			'logged' => $this->context->customer->isLogged(),
			'FBlogged' => $this->context->cookie->__get("fblogged"),
			'back'=>Tools::getValue('back'),
			'multi_shipping'=>Tools::getValue('multi-shipping'),
		));
		return $this->display(__FILE__, 'views/templates/hook/default.tpl');
	}

}


