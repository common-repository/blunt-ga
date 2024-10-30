<?php 
	
	/*
		Plugin Name: Blunt GA (Google Analytics)
		Plugin URI: http://wordpress.org/plugins/blunt-ga/
		Description: Installs Google Analytics. Allows for event tracking of document, email, click to call and outbound links as well as event tracking of form submissions and form and other conversion pages. Includes ability and settings to enable cross site tracking and linking.
		Version: 4.0.0 
		Author: John A. Huebner II, hube02@earthlink.net
		Author URI: 
		License: GPL v2 or later
		
		Blunt GA Plugin
		Copyright (C) 2012, John A. Huebner II, hube02@earthlink.net
		
		This program is free software: you can redistribute it and/or modify
		it under the terms of the GNU General Public License as published by
		the Free Software Foundation, either version 2 of the License, or
		(at your option) any later version.
		
		This program is distributed in the hope that it will be useful,
		but WITHOUT ANY WARRANTY; without even the implied warranty of
		MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
		GNU General Public License for more details http://www.gnu.org/licenses
		
	*/
	
	require_once(dirname(__FILE__).'/blunt.ga.class.php');
	$bluntGA = new bluntGA();
	register_activation_hook(__FILE__, array($bluntGA, 'activate'));
	
 
?>