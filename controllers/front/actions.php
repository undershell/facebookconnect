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

class FacebookconnectActionsModuleFrontController extends ModuleFrontController
{
	public $user_profile = array();
	
	public function init()
	{
		parent::init();
	}

	public function postProcess()
	{
		if(Tools::getValue('process') == 'login')
			$this->processLogin();
		//else ...
	}

 
	/**
	 * Login if the user has an account else signup
	 */
	public function processLogin()
	{
		global $smarty, $cookie;
		$back = Tools::getValue('back');
		$key = Tools::safeOutput(Tools::getValue('key'));
		if (!empty($key))
			$back .= (strpos($back, '?') !== false ? '&' : '?').'key='.$key;


		// User is logged
		if ($this->context->customer->isLogged()){
			die(Tools::jsonEncode(array('status'=>'redirect','message'=>'Vous êtes déjà connecté.', 'url'=>'')));	//lui recharger la page
		}

		// User is not logged
		require dirname(__FILE__).'/../../classes/fb-sdk/facebook.php';

		$facebook = new Facebook(array(
		  'appId'  => Configuration::get('FB_APPID'),
		  'secret' => Configuration::get('FB_SECRET'),
		));

		// See if there is a user from a cookie
		$user = $facebook->getUser();
		$access_token = $facebook->getAccessToken();
		
		if ($user) {
		  try {
			$permissions = $facebook->api('/me/permissions', 'get', array('access_token'=>$access_token));
			//die(print_r($permissions, true));
			
			if(empty($permissions['data'][0]['installed'])){
				die(Tools::jsonEncode(array('status'=>'error','message'=>'User has not installed the application.')));
			}

			if(empty($permissions['data'][0]['email'])){
				die(Tools::jsonEncode(array('status'=>'error','message'=>'User does not have the email permission.')));
			}
			
			if(empty($permissions['data'][0]['user_birthday'])){
				die(Tools::jsonEncode(array('status'=>'error','message'=>'User does not have the user_birthday permission.')));
			}
			
			if(empty($permissions['data'][0]['user_about_me'])){
				die(Tools::jsonEncode(array('status'=>'error','message'=>'User does not have the user_about_me permission.')));
			}
			
			// Proceed knowing you have a logged in user who's authenticated.	vérifier l'id du connecteur?
			$this->user_profile = $facebook->api('/me');

		
		  } catch (FacebookApiException $e) {
				error_log($e);
				$user = null;
				die(Tools::jsonEncode(array('status'=>'error','message'=>'Veuillez réessayer !')));
		  }
		}
		
		if ($user){	// User connected		
			 //die(print_r($this->user_profile, true));
			// If Email User existe in DB => login
			if (Customer::customerExists($this->user_profile['email'])){			//vérifier ces informations ?

				$customer = new Customer();
				$authentication = $customer->getByEmail($this->user_profile['email']);
				/* Handle brute force attacks */
				sleep(1);

				//si il y a un compte avec fb_id==0 on lui adapte son compte sinon le log
				if($customer->fb_uid == 0){
					$customer->fb_uid = $this->user_profile['id'];
					$customer->setFb_uid();
				}

				if ($this->user_profile['id'] != $customer->fb_uid)
					die(Tools::jsonEncode(array('status'=>'redirect','message'=>'fb login authentication failed', 'url'=>'')));
				else
				{	
					$cookie->id_compare = isset($cookie->id_compare) ? $cookie->id_compare: CompareProduct::getIdCompareByIdCustomer($customer->id);
					$cookie->id_customer = (int)$customer->id;
					$cookie->customer_lastname = $customer->lastname;
					$cookie->customer_firstname = $customer->firstname;
					//$cookie->fblogged = 1;
					$cookie->__set("fblogged", '1');
					$cookie->logged = 1;
					$customer->logged = 1;
					$customer->fblogged = 1;
					$cookie->is_guest = $customer->isGuest();
					$cookie->passwd = $customer->passwd;
					$cookie->email = $customer->email;
					
					
					// Add customer to the context
					$this->context->customer = $customer;
					//die($this->context->customer->isFBLogged()?"tre":"fls");
					if (Configuration::get('PS_CART_FOLLOWING') && (empty($cookie->id_cart) || Cart::getNbProducts($cookie->id_cart) == 0))
						$cookie->id_cart = (int)Cart::lastNoneOrderedCart($this->context->customer->id);
				
					// Update cart address
					$this->context->cart->id = $this->context->cookie->id_cart;
					$this->context->cart->setDeliveryOption(null);
					$this->context->cart->id_address_delivery = Address::getFirstCustomerAddressId((int)($customer->id));
					
					$this->context->cart->id_address_invoice = Address::getFirstCustomerAddressId((int)($customer->id));
					$this->context->cart->secure_key = $customer->secure_key;
					$this->context->cart->update();
					$this->context->cart->autosetProductAddress();

					Hook::exec('actionAuthentication');

					//if (count($this->context->cart->getProducts(true)) > 0)
						//die(Tools::jsonEncode(array('is'=>1, 'status'=>'redirect','url'=>Tools::jsonEncode(Tools::redirectMe('index.php?controller=order&multi-shipping='.(int)Tools::getValue('multi-shipping'))))));
					if ($back = Tools::getValue('back'))
						die(Tools::jsonEncode(array('is'=>1, 'status'=>'redirect','url'=>Tools::redirectMe(html_entity_decode($back)))));
					die(Tools::jsonEncode(array('is'=>1, 'status'=>'redirect','url'=>Tools::redirectMe('my-account.php'))));
				}
			}else{
			//if User does not existe on BD => add => send a mail
				/*tester les param*/
				$customer = new Customer();
				$customer_birthday = explode('/',$this->user_profile['birthday']);
				$customer->birthday = intval($customer_birthday[2]).'-'.intval($customer_birthday[0]).'-'.intval($customer_birthday[1]);
				if ($this->user_profile['gender'] == "male")
					$_POST['id_gender'] = 1;
				else if ($this->user_profile['gender'] == "female")
					$_POST['id_gender'] = 2;
				else
					$_POST['id_gender'] = 9;
				$_POST['lastname'] = $this->user_profile['last_name'];
				$_POST['firstname'] = $this->user_profile['first_name'];
				$_POST['passwd'] = Tools::passwdGen(10);
				$_POST['email'] = $this->user_profile['email'];
				$customer->fb_uid = $this->user_profile['id'];
				
				$customer->newsletter = true;
				
				$errors = $customer->validateController();
				
				if (!sizeof($errors))	//erreur du mot de passe
				{
					if ($customer->newsletter){
						$customer->ip_registration_newsletter = pSQL(Tools::getRemoteAddr());
						$customer->newsletter_date_add = pSQL(date('Y-m-d H:i:s'));

						if($module_newsletter = Module::getInstanceByName('blocknewsletter'))
						if($module_newsletter->active)
							$module_newsletter->confirmSubscription(Tools::getValue('email'));
					}
					
					$customer->active = 1;
					
					// New Guest customer
					$customer->is_guest = 0;
						
					if (!$customer->add())
						die(Tools::jsonEncode(array('status'=>'error','message'=>'Une erreur est survenue lors de la création du compte !')));
					else
					{
						$customer->cleanGroups();
						// we add the customer in the default customer group
						$customer->addGroups(array((int)Configuration::get('PS_CUSTOMER_GROUP')));
						
						if(!Mail::Send(
							$this->context->language->id,
							'account',
							Mail::l('Welcome!'),
							array(
								'{firstname}' => $customer->firstname,
								'{lastname}' => $customer->lastname,
								'{email}' => $customer->email,
								'{passwd}' => Tools::getValue('passwd')),
							$customer->email,
							$customer->firstname.' '.$customer->lastname
						))
							die(Tools::jsonEncode(array('status'=>'error','message'=>'Cannot send e-mail')));
							
						$smarty->assign('confirmation', 1);
						//facebook logged
						$customer->fblogged = 1;
						$cookie->__set("fblogged", '1');
						$customer->logged = 1;
						$cookie->id_customer = (int)$customer->id;
						$cookie->customer_lastname = $customer->lastname;
						$cookie->customer_firstname = $customer->firstname;
						$cookie->passwd = $customer->passwd;
						$cookie->logged = 1;
						$cookie->email = $customer->email;
						$cookie->is_guest = 0;
						// Update cart address
						$this->context->cart->secure_key = $customer->secure_key;
						$this->context->cart->update();
						Hook::exec('actionCustomerAccountAdd', array(
								'_POST' => $_POST,
								'newCustomer' => $customer
						));

						// redirection: if cart is not empty : redirection to the cart
						if (count($this->context->cart->getProducts(true)) > 0)
							die(Tools::jsonEncode(array('is'=>1, 'status'=>'redirect','url'=>Tools::jsonEncode(Tools::redirectMe('index.php?controller=order&multi-shipping='.(int)Tools::getValue('multi-shipping'))))));
						if ($back = Tools::getValue('back'))
							die(Tools::jsonEncode(array('is'=>1, 'status'=>'redirect','url'=>Tools::redirectMe(html_entity_decode($back)))));
						// else : redirection to the account
						die(Tools::jsonEncode(array('is'=>1, 'status'=>'redirect','url'=>Tools::redirectMe('index.php?controller=my-account'))));
					}
				}else
					die(Tools::jsonEncode(array('status'=>'error','message'=>'Une erreur est survenue lors de la création du compte !')));
			  }
        }else{	// User is not connected
			die(Tools::jsonEncode(array('status'=>'error','message'=>'Veuillez réessayer !')));
		}
			
	}
}

?>
