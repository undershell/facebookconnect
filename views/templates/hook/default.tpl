{*
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
*}

<div id="fb-root"></div>

<script type="text/javascript">
  // Additional JS functions here
	window.fbAsyncInit = function() {
	    FB.init({
	      appId      : '{$appid}', // App ID
	      status     : true, // check login status
	      cookie     : true, // enable cookies to allow the server to access the session
	      xfbml      : true,  // parse XFBML
	      oauth	 : true
	    });
	    //return;
	
		FB.getLoginStatus(function(response) {
		   if (response.status == 'connected') {
		      {if $FBlogged}
		      avatar();
		      {*$('#header_user_info .logout').attr({ 'onclick':'logout();return false;' });*}
		      {/if}
			} else if (response.status === 'not_authorized') {
				// not_authorized
			} else {
				// not_logged_in
				{*{if $FBlogged}logout();{/if}*}
			}
		});

	};

	// Load the SDK Asynchronously
	(function(d){
		var js, id = 'facebook-jssdk', ref = d.getElementsByTagName('script')[0];
		if (d.getElementById(id)) { return; }
		js = d.createElement('script'); js.id = id; js.async = true;
		js.src = "//connect.facebook.net/fr_FR/all.js";
		ref.parentNode.insertBefore(js, ref);
	}(document));
      
	{if $logged == 1}
	{if $FBlogged == 1}
	function avatar(){
	   FB.api('/me', function(response) {
			var src = 'https://graph.facebook.com/'+response.id+'/picture';
			$('#header_user_info span').append('<img style="margin-left:5px" height="20" src="'+src+'"/>');
			//login();
		});
	}
	
	function logout(){
		FB.logout(function(response) {
			window.location.href= $('#header_user_info .logout').attr('href');
		});
	}
	{/if}
	
	{else}
	function login(){ 
		$.post('{$link->getModuleLink('facebookconnect', 'actions', ['process' => 'login'])}',
					{ {*document.referrer*}
						{if $back!=""}back:"{$back}"{else}back:location.href{/if} {*$back!="" &&*}{if $multi_shipping!=""},{/if}
						{if $multi_shipping!=""}'multi-shipping':"{$multi_shipping}"{/if}
					}, 
		function (data) {
			if (data.status == 'redirect') {
				//if(data.message)
					//alert(data.message);
					window.location.href= data.url;	
			} else {
				//alert(data.message);
			}
		}, 'json');
	}
	
   function fblogin(){
	
		FB.login(function(response) {
			if (response.status == 'connected') {
				login();
			} else if (response.status === 'not_authorized') {
				// not_authorized
				//alert("Veuillez réessayer, l'application doit etre autorisé !");
			} else {
				// not logged in
				//alert("Veuillez réessayer, vous n'etes pas connecté")
				//loggout !
			}
		}, { scope:'user_about_me, email, user_birthday' });
		return false;
	}
	
	$(function(){
		{if $page_name == authentication}
		$('#login_form').after('<div class="wrap" style="text-align:center">'+
		 '<a href="javascript:void(0)" onclick="return fblogin();" title="Connectez vous grâce à Facebook">'+
		   '<img src="{$module_template_dir}img/connect-with-fb.png" alt="Facebook connect" style="margin-top:12px"  />'+
		 '<\/a>'+
		'<\/div>');
		{/if}
		
		$('#header_user_info').append('&nbsp;<a href="javascript:void(0)" onclick="return fblogin();" title="Connectez vous grâce à Facebook" style="padding:0 0 0 10px;height: 18px;">'+
		   '<img src="{$module_template_dir}img/fb-connect.png" alt="Facebook connect" width="80" height="18"/>'+
		 '<\/a>&nbsp;');
	});
	{/if}
</script>
