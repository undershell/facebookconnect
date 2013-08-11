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

class Tools extends ToolsCore
{
	/*for ajax*/
	public static function redirectMe($url, $base_uri = __PS_BASE_URI__, Link $link = null)
	{
		if (!$link)
			$link = Context::getContext()->link;

		if (strpos($url, 'http://') === false && strpos($url, 'https://') === false && $link)
		{
			if (strpos($url, $base_uri) === 0)
				$url = substr($url, strlen($base_uri));
			if (strpos($url, 'index.php?controller=') !== false && strpos($url, 'index.php/') == 0)
			{
				$url = substr($url, strlen('index.php?controller='));
				if (Configuration::get('PS_REWRITING_SETTINGS'))
					$url = Tools::strReplaceFirst('&', '?', $url);
			}

			$explode = explode('?', $url);
			// don't use ssl if url is home page
			// used when logout for example
			$use_ssl = !empty($url);
			$url = $link->getPageLink($explode[0], $use_ssl);
			if (isset($explode[1]))
				$url .= '?'.$explode[1];
		}


		return $url;
	}
}

