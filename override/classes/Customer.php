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

class Customer extends CustomerCore
{

	/** @var string FB User ID */
	public $fb_uid = NULL;
	
	/** @var boolean is the customer logged in */
	public $fblogged = 0;
	
	/**
	 * Add 'fb_uid' to the fields of ObjectModel::$definition
	 */
	public function __construct($id = null)
	{
		$definition['fields']['fb_uid'] = array('type' => self::TYPE_INT, 'copy_post' => false);
		parent::__construct($id);
	}

	public function add($autodate = true, $null_values = true)
	{
		$this->id_shop = ($this->id_shop) ? $this->id_shop : Context::getContext()->shop->id;
		$this->id_shop_group = ($this->id_shop_group) ? $this->id_shop_group : Context::getContext()->shop->id_shop_group;
		$this->birthday = (empty($this->years) ? $this->birthday : (int)$this->years.'-'.(int)$this->months.'-'.(int)$this->days);
		$this->secure_key = md5(uniqid(rand(), true));
		$this->last_passwd_gen = date('Y-m-d H:i:s', strtotime('-'.Configuration::get('PS_PASSWD_TIME_FRONT').'minutes'));
		if($this->fb_uid==NULL)
			$this->fb_uid = 0;
		
		if ($this->newsletter && !Validate::isDate($this->newsletter_date_add))
			$this->newsletter_date_add = date('Y-m-d H:i:s');
			
		if ($this->id_default_group == _PS_DEFAULT_CUSTOMER_GROUP_)
			if ($this->is_guest)
				$this->id_default_group = Configuration::get('PS_GUEST_GROUP');
			else
				$this->id_default_group = Configuration::get('PS_CUSTOMER_GROUP');

		/* Can't create a guest customer, if this feature is disabled */
		if ($this->is_guest && !Configuration::get('PS_GUEST_CHECKOUT_ENABLED'))
			return false;
	 	$success = parent::add($autodate, $null_values);
		$this->updateGroup($this->groupBox);
		return $success;
	}
	
	/**
	 * Check the FB status of the customer and return customer validity
	 *
	 * @param boolean $with_guest
	 * @return boolean customer validity
	 */
	public function isFBLogged($with_guest = false)
	{
		if (!$with_guest && $this->is_guest == 1)
			return false;

		/* Customer is valid only if it can be load and if object password is the same as database one */
		if ($this->fblogged == 1 && $this->logged == 1 && $this->id && Validate::isUnsignedId($this->id) && Customer::checkPassword($this->id, $this->passwd)){
			return true;
		}
		return false;
	}
	
	/**
	 * Logout
	 *
	 * @since 1.5.0
	 */
	public function logout()
	{
		if (isset(Context::getContext()->cookie))
			Context::getContext()->cookie->logout();
		$this->logged = 0;
		$this->fblogged = 0;
		Context::getContext()->cookie->__set("fblogged", 0);
	}

	/**
	 * Soft logout, delete everything links to the customer
	 * but leave there affiliate's informations
	 *
	 * @since 1.5.0
	 */
	public function mylogout()
	{
		if (isset(Context::getContext()->cookie))
			Context::getContext()->cookie->mylogout();
		$this->logged = 0;
		$this->fblogged = 0;
		Context::getContext()->cookie->__set("fblogged", 0);
	}

	public function setFb_uid()
	{	
		$sql = 'UPDATE `'._DB_PREFIX_.'customer`
			SET `fb_uid` = '.(int)$this->fb_uid.'
			WHERE  `id_customer` = '.(int)$this->id;
		return Db::getInstance()->execute($sql);
	}
}

