<?php 
	
	if (!class_exists('Tld')) {
		require_once(dirname(__FILE__).'/tld/utils.php');
	}
	
	class bluntGA {
		
		private $settings;
		private $errors;
		private $process_error;
		private $version = '4.0.0';
		private $debug = false;
		private $minified = true;
		private $enabled = true;
		
		private static $instance;
		
		public function __construct() {
			bluntGA::$instance = $this;
			add_action('init', array($this, 'init'), 1);
			add_action('admin_menu', array($this, 'adminMenu'));
		} // end public function __construct
		
		public function init() {
			$this->errors = array();
			$this->process_error = false;
			$this->settings = $this->getSettings();
			//$this->settings['users'][0] = strtolower($this->settings['users'][0]);
			//$this->saveSettings();
			//echo '<pre>'; print_r($this->settings); die;
			$this->do_updates();
			wp_enqueue_script('post');
			
			$this->register_script();
			add_action('wp_enqueue_scripts', array($this, 'enqueue_script'));
			$this->check_roles();
		} // end public function init
		
		private function check_roles() {
			// see if the user is logged in, if they are, check there role and
			// see if they should have tracking enabled, if not then disable tracking
			$track = true;
			if (is_user_logged_in()) {
				global $current_user;
				$track_roles = $this->settings['users'];
				$user_roles = $current_user->roles;
				// users can have multiple roles
				// so I only want to track users if all of their roles are to be tracked
				foreach ($user_roles as $role) {
					if (!in_array($role, $track_roles)) {
						// this user role is not in the list
						// turn off tracking and break, no need to look further
						$this->enabled = false;
						break;
					}
				}
			}
		} // end private function check_roles
		
		private function get_roles() {
			global $wp_roles;
			$roles = array();
			$all_roles = $wp_roles->roles;
			if (count($all_roles) > 0) {
				foreach ($all_roles as $role => $array) {
					$roles[$role] = $array['name'];
				}
			}
			return $roles;
		} // end private function get_roles
		
		private function register_script() {
			// register scripts
			$src = plugins_url('blunt.ga.install.v3.min.js', __FILE__);
			if ($this->debug || !$this->minified) {
				$src = plugins_url('blunt.ga.install.v3.js', __FILE__);
			}
			//$src = plugins_url('blunt.ga.install.v3.js', __FILE__);
			
			wp_register_script('blunt-ga', 
												 $src,
												 array(),
												 $this->version);
			add_action('wp', array($this, 'localize_script'));
			//$this->localize_script();
			
		} // end private function register_script
		
		public function localize_script() {
			$settings = $this->settings;
			$sitename = trim(preg_replace('/[^a-z0-9]+/i', '-', get_bloginfo('name')), '-');
			$domain = $_SERVER['HTTP_HOST'];
			$top_level_domain = Tld::getTld('http://'.$_SERVER['HTTP_HOST'].'/');
			$text = array();
			$test['prefix'] = strtolower(trim($settings['cross_site']['prefix']));
			$test['suffix'] = strtolower(trim($settings['cross_site']['sufix']));
			foreach ($test as $key => $value) {
				if ($value == '%%domain%%') {
					$settings['cross_site'][$key] = $domain;
				} elseif ($value == '%%sitename%%') {
					$settings['cross_site'][$key] = $sitename;
				} elseif ($value == '%%top-level-domain%%') {
					$settings['cross_site'][$key] = $top_level_domain;
				}
			} // end foreach test value
			
			switch ($settings['domain']) {
				case 'auto':
				case 'none':
					// do nothing
					break;
				case '%%domain%%':
					$settings['domain'] = $domain;
					break;
				case '%%top-level-domain%%':
					$settings['domain'] = $top_level_domain;
					break;
				case '.%%top-level-domain%%':
					$settings['domain'] = '.'.$top_level_domain;
					break;
				case 'custom':
					$settings['domain'] = $settings['custom_domain'];
					break;
			} // end switch domain settng
			
			$found = true;
			if (is_404()) {
				$found = false;
			}
			
			$settings['found'] = $found;
			
			$options = str_replace('+', '%20', urlencode('('.json_encode($settings).')'));
			
			$settings = array('opt' => $options, 'done' => false);
			if ($this->debug) {
				// only include the debug setting if it is true
				// because it will never be use in the minified version
				$settings['debug'] = true;
			}
			
			wp_localize_script('blunt-ga',
												 'bluntGA', 
												 $settings);
			
		} // end private function localize_script
		
		public function enqueue_script() {
			// enqueue script here
			if (is_admin()) {
				// just in case
				return;
			}
			if ($this->enabled) {
				wp_enqueue_script('blunt-ga');
			}
		} // end public function enqueue_scripts
		
		public function activate() {
			$this->settings = $this->getSettings();
			$this->do_updates();
		} // end public function activate
		
		private function do_updates() {
			if (!$this->settings ||
			    !isset($this->settings['version']) ||
					version_compare($this->settings['version'], '4.0.0', '<')) {
				$this->update_settings();  // update settings to 4.0.0
			}
			// remove unused settings
			
		} // end private function do_updates
		
		public function adminMenu() {
			$plugin_page = add_options_page('Blunt GA Settings', 
																			'Blunt GA Settings', 
																			'manage_options', 
																			'bluntGA_options_page', 
																			array($this, 'showSettingsForm'));
			add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_style'));
		} // end public function adminMenu
		
		public function admin_enqueue_style($hook) {
			if ($hook == 'settings_page_bluntGA_options_page') {
				 wp_enqueue_style('blunt-ga-admin-css',
				 									plugins_url('admin.css', __FILE__),
													array(),
													$this->version);
			}
		} // end public function admin_enqueue_style
		
		public function showSettingsForm() {
			// if form submitted, process it here with $this->processSettingsForm()
			if (isset($_POST['bluntGA'])) {
				$this->processSettingsForm();
			}
			//echo '<pre>'; print_r($_POST); echo '</pre>';
			?>
				<div class="wrap">
					<form id="bga-donate-form" action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
						<input type="hidden" name="cmd" value="_s-xclick">
						<input type="hidden" name="hosted_button_id" value="JHQ6DERKP55VC">
						<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
						<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
					</form>
					<h2>Blunt GA Settings</h2>

					<form method="post" action="" id="bluntGAoptionsForm" name="bluntGAoptionsForm" class="bluntGAoptionsForm">
						<?php 
							$action = 'bluntGAoptions_submit';
							$name = 'bluntGA_nonce';
							$referer = true;
							$echo = true;
							wp_nonce_field($action, $name, $referer, $echo);
						?>
						<div id="poststuff">
							<div id="post-body" class="metabox-holder columns-2">
								<div id="post-body-content">
									<?php 
										if (count($this->errors) > 0) {
											// show error messages here
											?>
												<div id="bluntGAerrorMessage">
													<ul>
														<?php 
															foreach ($this->errors as $error) {
																?><li><?php echo $error; ?></li><?php 
															}
														?>
													</ul>
												</div>
											<?php 
										}
									?>
									<div class="postbox">
										<div class="handlediv" title="Click to toggle"><br /></div>
										<h3 class="hndl"><span>General Documentation</span></h3>
										<div class="inside blunt_ga_help">
											<p>
												This section contains general documentation that applies to all of the
												setting sections below. Toggle this post box closed to hide.
											</p>
											<h4>Tracking Methods</h4>
											<div class="indent">
												<h5>Track as Event</h5>
												<p>
													Track as Event is the default setting for all sections. 
													A detailed explanation of event tracking can be found 
						<a href="https://developers.google.com/analytics/devguides/collection/gajs/eventTrackerGuide"> on the Google Developers site</a>.<br />
													When you set tracking to track as the following code is added to all the links
													of the type for the section:<br />
													
				<code>
					<strong>onclick="_gaq.push(['_trackEvent', category, action, opt_label, opt_value, opt_noninteraction]);" </strong>
				</code>
												</p>
												<p>
													At this time, the only variable that that you can control of the above code 
													is the event category. Each section requires you to enter this value. Default 
													values have been inserted but you can change this to whatever you want to set 
													as the category to appear in your Google Analytics reports. You may enter upper 
													and lower case letters [a-zA-Z], numbers [0-9], the underscore [_], 
													the hyphen [-] and the period or dot [.] characters for this value.
												</p>
												<p>The remainder of the varaibles will be set with the following values:</p>
												<ul>
													<li><strong>action:</strong> The target URL of the link or form. For links this is the href value for forms this is the value of the action attribute.</li>
													<li><strong>opt_label:</strong> This will be set the the URL of the page on your site where the link or form appears.</li>
													<li><strong>opt_value:</strong> 0 (Zero)</li>
													<li><strong>opt_noninteraction:</strong> false</li>
												</ul>
												<h5>Track as Virtual Page View</h5>
												<p>
													Before Google introduced event tracking links like those tracked with this plugin
													would be tracked as virtual page views. What this means is that the code sends
													google the URL of a page that does not actually exist but has special meaning for
													reports.
												</p>
												<p>
													This ability has been kept in this plugin because many people still
													prefer this method. The reason is that with virtual page views all of the
													events appear in the page views results along with all the other real pages
													of your site. This makes it easier to find these events without looking
													into a seperate report and to include them in graphs with regular page views.
												</p>
												<p>
													The page view that is sent to Google Analytics will look like the following
													URL where the values of the variable sent will correspond to the values
													that would normally be sent for event tracking:<br />
													<code>
														<strong>/<em style="font-weight: normal">CATEGORY</em>/?action=<em style="font-weight: normal">ACTION</em>&amp;label=<em style="font-weight: normal">OPT_LABEL</em></strong>
													</code>
											</div>
											<div class="clear"></div>
										</div>
									</div>
									<input type="submit" name="bluntGAsubmit" value="Save Changes" class="button" />
									<div class="postbox">
										<div class="handlediv" title="Click to toggle"><br /></div>
										<h3 class="hndl"><span>Basic Settings</span></h3>
										<div class="inside basic_settings">
											<fieldset>
												<label for="bluntGA_ga_account">
													<span class="label">Google Analyitics Account #: </span>
													<input type="text" name="bluntGA[ga_account]" id="bluntGA_ga_account" value="<?php 
															if (isset($_POST['bluntGA'])) {
																echo $_POST['bluntGA']['ga_account'];
															} else {
																echo $this->settings['ga_account'];
															}
														?>" />
													<span class="notes">Example: UA-12345678-1</span>
												</label><br />
												<label for="bluntGA_set_domain">
													<span class="label">Domain: </span>
													<select name="bluntGA[domain]" id="bluntGA_domain">
														<?php 
															$options = array('auto' => 'auto',
																							 'none' => 'none',
																							 'domain' => '%%domain%%',

																							 'top level domain' => '%%top-level-domain%%',
																							 'top level domain w/preceding dot' => '.%%top-level-domain%%',
																							 'custom' => 'custom');
															foreach ($options as $label => $value) {
																$selected = false;
																if (isset($_POST['bluntGA']) && $_POST['bluntGA']['domain'] == $value) {
																	$selected = true;
																} elseif (!isset($_POST['bluntGA']) && $this->settings['domain'] == $value) {
																	$selected = true;
																}
																?>
																	<option value="<?php echo $value; ?>"<?php 
																			if ($selected) {
																				?> selected="selected"<?php 
																			}
																		?>><?php echo $label; ?></option>
																<?php 
															} // end foreach option
														?>
													</select>
												</label><br />
												<label for="bluntGA_custom_domain">
													<span class="label">Custom Domaan: </span>
													<input type="text" name="bluntGA[custom_domain]" id="bluntGA_custom_domain" value="<?php 
															if (isset($_POST['bluntGA'])) {
																echo $_POST['bluntGA']['custom_domain'];
															} else {
																echo $this->settings['custom_domain'];
															}
														?>" />
												</label><br />
											</fieldset>
											<div class="blunt_ga_help">
												<p>
													For details on setting the proper domain setting to use please see the Google Documentation on
													<a href="https://developers.google.com/analytics/devguides/collection/gajs/methods/gaJSApiDomainDirectory#_gat.GA_Tracker_._setDomainName">_setDomainName()</a> and <a href="https://developers.google.com/analytics/devguides/collection/gajs/gaTrackingSite">Cross Site Tracking</a>.
												</p>
												<p>
													You have 6 options of the domain name that will be set when GA is installed:
												</p>
												<ul>
													<li><strong>auto:</strong> The auto setting is the default setting that Google Analytics uses. The auto setting will allow Google Analytics to determine the value based on the URL viewed in the browser.</li>
													<li><strong>none:</strong> The none setting will make it so that cookie values cannot be shared accross domains and sub-domains or by setting up Cross Site Linking. This setting is not recommended and only included for completeness.</li>
													<li><strong>domain:</strong> This setting is the same as the auto setting except that Blunt GA will force the domain name to be used rather than allow GA to attempt to determine the domain name. The domain name used will be the full domain name as viewed in the browser.</li>
													<li><strong>top level domain:</strong> This setting will set the domain to the your top level domain. For example if your site is www.mysite.com or blog.mysite.com this setting will set the domain to <strong>mysite.com</strong></li>
													<li><strong>top level domain w/preceding dot:</strong> This is the default setting for this plugin. This is the same as the top level domain setting above except the it adds a preceding dot (.) to the domain so that it will be set as <strong>.mydomain.com</strong>. This is the default setting chosen for this plugin becuase this is the correct way according to the cookie documentation to share cookie values across sub-domains.</li>
													<li><strong>custom: </strong> If you do not wish to use one of the automatically generated values you can choose custom and enter the domain value you wish to be set into the <strong>Custom Domain</strong> value.</li>
												</ul>
											</div>
											<div class="clear"></div>
										</div>
									</div>
									<input type="submit" name="bluntGAsubmit" value="Save Changes" class="button" />
									<div class="postbox">
										<div class="handlediv" title="Click to toggle"><br /></div>
										<h3 class="hndl"><span>User Settings</span></h3>
										<div class="inside user_settings">
											<fieldset>
												<p>Select the User Roles that tracking should be enabled for:</p>
												<?php 
													$roles = $this->get_roles();
													if (count($roles) > 0) {
														//echo '<pre>'; print_r($roles); echo '</pre>';
														?>
															<ul>
																<?php 
																	$count = 0;
																	foreach ($roles as $role => $name) {
																		$count++;
																		$id = 'bluntGA_users_'.$count;
																		?>
																			<li>
																				<input type="checkbox" name="bluntGA[users][]" id="<?php 
																					echo $id; ?>" value="<?php echo $role; ?>"<?php 
																						if (in_array($role, $this->settings['users'])) {
																							?> checked="checked"<?php 
																						}
																					?> />
																				<label for="<?php echo $id; ?>">
																					<?php echo $name; ?>
																				</label>
																			</li>
																		<?php 
																	}
																?>
															</ul>
														<?php 
													} else {
														?>
															<p style="color: #F00; font-weight: bold;">
																ERROR: No User Roles found in your WordPress Installation!
															</p>
														<?php 
													}
												?>
											</fieldset>
											<div class="blunt_ga_help">
												<p>
													The User Roles listed here are those found on your site. Select the users you want to enable tracking for from this list.
												</p>
												<p>
													Those roles that are not selected will not generate page views or events while they are logged in. This is accomplished by not including the Google Analytics script on any pages of the site, this asures that no tracking can be done.
												</p>
												<p>
													Please note that in WordPress a user may have multiple User Roles depending on what other plugins you are running. For a user to have tracking turned on all of their roles must be enabled for tracking.
												</p>
											</div>
											<div class="clear"></div>
										</div>
									</div>
									<input type="submit" name="bluntGAsubmit" value="Save Changes" class="button" />
									<div class="postbox"><!-- closed -->
										<div class="handlediv" title="Click to toggle"><br /></div>
										<h3 class="hndl"><span>Outbound Link Tracking</span></h3>
										<div class="inside outbound_settings">
											<fieldset>
												<label for="bluntGA_external_enable">
													<span class="label">Enable Outbound Link Tracking: </span>
													<select name="bluntGA[external][enable]" id="bluntGA_external_enable">
														<?php 
															$options = array(1 => 'Yes', 0 => 'No');
															foreach ($options as $value => $label) {
																$selected = false;
																if (isset($_POST['bluntGA']) && $_POST['bluntGA']['external']['enable'] == $value) {
																	$selected = true;
																} elseif (!isset($_POST['bluntGA']) && $this->settings['external']['enable'] == $value) {
																	$selected = true;
																}
																?>
																	<option value="<?php echo $value; ?>"<?php 
																			if ($selected) {
																				?> selected="selected"<?php 
																			}
																		?>><?php echo $label; ?></option>
																<?php 
															} // end foreach option
														?>
													</select>
												</label><br />
												<label for="bluntGA_external_method">
													<span class="label">Tracking Method: </span>
													<select name="bluntGA[external][method]" id="bluntGA_external_method">
														<?php 
															$options = array('event' => 'Track as Events',
																							 'virtual' => 'Track as Virtual Page Views');
															foreach ($options as $value => $label) {
																$selected = false;
																if (isset($_POST['bluntGA']) && $_POST['bluntGA']['external']['method'] == $value) {
																	$selected = true;
																} elseif (!isset($_POST['bluntGA']) && $this->settings['external']['method'] == $value) {
																	$selected = true;
																}
																?>
																	<option value="<?php echo $value; ?>"<?php 
																			if ($selected) {
																				?> selected="selected"<?php 
																			}
																		?>><?php echo $label; ?></option>
																<?php 
															} // end foreach option
														?>
													</select>
												</label><br />
												<label for="bluntGA_external_category">
													<span class="label">GA Event Category: </span>
													<input type="text" name="bluntGA[external][category]" id="bluntGA_external_category" value="<?php 
															if (isset($_POST['bluntGA'])) {
																echo $_POST['bluntGA']['external']['category'];
															} else {
																echo $this->settings['external']['category'];
															}
														?>" />
												</label><br />
												<label for="bluntGA_external_new_window">
													<span class="label">Open in New Window: </span>
													<select name="bluntGA[external][new_window]" id="bluntGA_external_new_window">
														<?php 
															$options = array(0 => 'No',
																							 1 => 'Yes');
															foreach ($options as $value => $label) {
																$selected = false;
																if (isset($_POST['bluntGA']) && $_POST['bluntGA']['external']['new_window'] == $value) {
																	$selected = true;
																} elseif (!isset($_POST['bluntGA']) && $this->settings['external']['new_window'] == $value) {
																	$selected = true;
																}
																?>
																	<option value="<?php echo $value; ?>"<?php 
																			if ($selected) {
																				?> selected="selected"<?php 
																			}
																		?>><?php echo $label; ?></option>
																<?php 
															} // end foreach option
														?>
													</select>
												</label><br />
											</fieldset>
											<div class="blunt_ga_help">
												<p>
													Outbound links are all links that leave this site and go to another site. Tracking outbound links allows you to see where visitors are leaving your site. Enabling outbound link tracking adds an event to all outbound links.
												</p>
												<p>
													For the purposes of this plugin, all of the following would be considered links to external site is the current domain name of this site was <strong>www.mydomain.com</strong>.
												</p>
												<ul>
													<li>blog.mydomain.com</li>
													<li>www.someothersite.com</li>
												</ul>
												<p>A link to mydomain.com (non-www) would not be considered an outbound link.</p>
												<p>
													As an added bonus, you can have Blunt GA automatically open all outbound links in a new browser window or tab, depending on the visitors browser and/or browser settings.
												</p>
											</div>
											<div class="clear"></div>
										</div>
									</div>
									<input type="submit" name="bluntGAsubmit" value="Save Changes" class="button" />
									<div class="postbox"><!-- closed -->
										<div class="handlediv" title="Click to toggle"><br /></div>
										<h3 class="hndl"><span>Mailto Link Tracking</span></h3>
										<div class="inside mailto_settings">
											<fieldset>
												<label for="bluntGA_mailto_enable">
													<span class="label">Enable Mailto Link Tracking: </span>
													<select name="bluntGA[mailto][enable]" id="bluntGA_mailto_enable">
														<?php 
															$options = array(1 => 'Yes', 0 => 'No');
															foreach ($options as $value => $label) {
																$selected = false;
																if (isset($_POST['bluntGA']) && $_POST['bluntGA']['mailto']['enable'] == $value) {
																	$selected = true;
																} elseif (!isset($_POST['bluntGA']) && $this->settings['mailto']['enable'] == $value) {
																	$selected = true;
																}
																?>
																	<option value="<?php echo $value; ?>"<?php 
																			if ($selected) {
																				?> selected="selected"<?php 
																			}
																		?>><?php echo $label; ?></option>
																<?php 
															} // end foreach option
														?>
													</select>
												</label><br />
												<label for="bluntGA_mailto_method">
													<span class="label">Tracking Method: </span>
													<select name="bluntGA[mailto][method]" id="bluntGA_mailto_method">
														<?php 
															$options = array('event' => 'Track as Events',
																							 'virtual' => 'Track as Virtual Page Views');
															foreach ($options as $value => $label) {
																$selected = false;
																if (isset($_POST['bluntGA']) && $_POST['bluntGA']['mailto']['method'] == $value) {
																	$selected = true;
																} elseif (!isset($_POST['bluntGA']) && $this->settings['mailto']['method'] == $value) {
																	$selected = true;
																}
																?>
																	<option value="<?php echo $value; ?>"<?php 
																			if ($selected) {
																				?> selected="selected"<?php 
																			}
																		?>><?php echo $label; ?></option>
																<?php 
															} // end foreach option
														?>
													</select>
												</label><br />
												<label for="bluntGA_mailto_category">
													<span class="label">GA Event Category: </span>
													<input type="text" name="bluntGA[mailto][category]" id="bluntGA_mailto_category" value="<?php 
															if (isset($_POST['bluntGA'])) {
																echo $_POST['bluntGA']['mailto']['category'];
															} else {
																echo $this->settings['mailto']['category'];
															}
														?>" />
												</label><br />
											</fieldset>
											<div class="blunt_ga_help">
												<p>
													Mailto links are links to email addresses and normally appear something like:<br />
													<code>&lt;a href="mailto:youremailaddress@somewhere.com"&gt;email me&lt;/a&gt;</code>
												</p>
											</div>
											<div class="clear"></div>
										</div>
									</div>
									<input type="submit" name="bluntGAsubmit" value="Save Changes" class="button" />
				<div class="postbox"><!-- closed -->
					<div class="handlediv" title="Click to toggle"><br /></div>
					<h3 class="hndl"><span>Click to Call Link Tracking</span></h3>
					<div class="inside click_to_call_settings">
						<fieldset>
							<label for="bluntGA_click_to_call_enable">
								<span class="label">Click to Call Link Tracking: </span>
								<select name="bluntGA[click_to_call][enable]" id="bluntGA_click_to_call_enable">
									<?php 
										$options = array(1 => 'Yes', 0 => 'No');
										foreach ($options as $value => $label) {
											$selected = false;
											if (isset($_POST['bluntGA']) && $_POST['bluntGA']['click_to_call']['enable'] == $value) {
												$selected = true;
											} elseif (!isset($_POST['bluntGA']) && $this->settings['click_to_call']['enable'] == $value) {
												$selected = true;
											}
											?>
												<option value="<?php echo $value; ?>"<?php 
														if ($selected) {
															?> selected="selected"<?php 
														}
													?>><?php echo $label; ?></option>
											<?php 
										} // end foreach option
									?>
								</select>
							</label><br />
							<label for="bluntGA_click_to_call_method">
								<span class="label">Tracking Method: </span>
								<select name="bluntGA[click_to_call][method]" id="bluntGA_click_to_call_method">
									<?php 
										$options = array('event' => 'Track as Events',
																		 'virtual' => 'Track as Virtual Page Views');
										foreach ($options as $value => $label) {
											$selected = false;
											if (isset($_POST['bluntGA']) && $_POST['bluntGA']['click_to_call']['method'] == $value) {
												$selected = true;
											} elseif (!isset($_POST['bluntGA']) && $this->settings['click_to_call']['method'] == $value) {
												$selected = true;
											}
											?>
												<option value="<?php echo $value; ?>"<?php 
														if ($selected) {
															?> selected="selected"<?php 
														}
													?>><?php echo $label; ?></option>
											<?php 
										} // end foreach option
									?>
								</select>
							</label><br />
							<label for="bluntGA_click_to_call_category">
								<span class="label">GA Event Category: </span>
								<input type="text" name="bluntGA[click_to_call][category]" id="bluntGA_click_to_call_category" value="<?php 
										if (isset($_POST['bluntGA'])) {
											echo $_POST['bluntGA']['click_to_call']['category'];
										} else {
											echo $this->settings['click_to_call']['category'];
										}
									?>" />
							</label><br />
						</fieldset>
						<div class="blunt_ga_help">
							<p>
								Click to call links are links that are for telephone numbers that appear something like:<br />
								<code>&lt;a href="tel:+18005551212"&gt;+1-800-555-1212&lt;/a&gt;</code>
							</p>
						</div>
						<div class="clear"></div>
					</div>
				</div>
									<input type="submit" name="bluntGAsubmit" value="Save Changes" class="button" />
									<div class="postbox"><!-- closed -->
										<div class="handlediv" title="Click to toggle"><br /></div>
										<h3 class="hndl"><span>Document/File Link Tracking</span></h3>
										<div class="inside document_settings">
											<fieldset>
												<label for="bluntGA_document_enable">
													<span class="label">Enable Document/File Link Tracking: </span>
													<select name="bluntGA[document][enable]" id="bluntGA_document_enable">
														<?php 
															$options = array(1 => 'Yes', 0 => 'No');
															foreach ($options as $value => $label) {
																$selected = false;
																if (isset($_POST['bluntGA']) && $_POST['bluntGA']['document']['enable'] == $value) {
																	$selected = true;
																} elseif (!isset($_POST['bluntGA']) && $this->settings['document']['enable'] == $value) {
																	$selected = true;
																}
																?>
																	<option value="<?php echo $value; ?>"<?php 
																			if ($selected) {
																				?> selected="selected"<?php 
																			}
																		?>><?php echo $label; ?></option>
																<?php 
															} // end foreach option
														?>
													</select>
												</label><br />
												<label for="bluntGA_document_method">
													<span class="label">Tracking Method: </span>
													<select name="bluntGA[document][method]" id="bluntGA_document_method">
														<?php 
															$options = array('event' => 'Track as Events',
																							 'virtual' => 'Track as Virtual Page Views');
															foreach ($options as $value => $label) {
																$selected = false;
																if (isset($_POST['bluntGA']) && $_POST['bluntGA']['document']['method'] == $value) {
																	$selected = true;
																} elseif (!isset($_POST['bluntGA']) && $this->settings['document']['method'] == $value) {
																	$selected = true;
																}
																?>
																	<option value="<?php echo $value; ?>"<?php 
																			if ($selected) {
																				?> selected="selected"<?php 
																			}
																		?>><?php echo $label; ?></option>
																<?php 
															} // end foreach option
														?>
													</select>
												</label><br />
												<label for="bluntGA_document_category">
													<span class="label">GA Event Category: </span>
													<input type="text" name="bluntGA[document][category]" id="bluntGA_document_category" value="<?php 
															if (isset($_POST['bluntGA'])) {
																echo $_POST['bluntGA']['document']['category'];
															} else {
																echo $this->settings['document']['category'];
															}
														?>" />
												</label><br />
												<label for="bluntGA_document_new_window">
													<span class="label">Open in New Window: </span>
													<select name="bluntGA[document][new_window]" id="bluntGA_document_new_window">
														<?php 
															$options = array(0 => 'No',
																							 1 => 'Yes');
															foreach ($options as $value => $label) {
																$selected = false;
																if (isset($_POST['bluntGA']) && $_POST['bluntGA']['document']['new_window'] == $value) {
																	$selected = true;
																} elseif (!isset($_POST['bluntGA']) && $this->settings['document']['new_window'] == $value) {
																	$selected = true;
																}
																?>
																	<option value="<?php echo $value; ?>"<?php 
																			if ($selected) {
																				?> selected="selected"<?php 
																			}
																		?>><?php echo $label; ?></option>
																<?php 
															} // end foreach option
														?>
													</select>
												</label><br />
												<label for="bluntGA_document_extensions">
													<span class="label">Document Extensions to Track: </span>
													<input type="text" name="bluntGA[document][extensions]" id="bluntGA_document_extensions" value="<?php 
															if (isset($_POST['bluntGA'])) {
																echo $_POST['bluntGA']['document']['extensions'];
															} else {
																$value = implode(', ', $this->settings['document']['extensions']);
																echo $value;
															}
														?>" class="wide" />
												</label><br />
											</fieldset>
											<div class="blunt_ga_help">
												<p>
													Documents and files are all files on your site that are not considered web pages where GA code cannot normally be installed, for example, pdf files or word documents.
												</p>
												<p>
													Enter a comma seperated list of all file extensions that should be considered document/file links and should have events added to their links in the <strong>Document Extensions to Track</strong> field.
												</p>
												<p>
													As an added bonus, you can have Blunt GA automatically open document links in a new browser window or tab, depending on the visitors browser.
												</p>
											</div>
											<div class="clear"></div>
										</div>
									</div>
									<input type="submit" name="bluntGAsubmit" value="Save Changes" class="button" />
									<div class="postbox"><!-- closed -->
										<div class="handlediv" title="Click to toggle"><br /></div>
										<h3 class="hndl"><span>Anchor Link Tracking</span></h3>
										<div class="inside anchors_settings">
											<fieldset>
												<label for="bluntGA_anchors_enable">
													<span class="label">Enable Anchor Link Tracking: </span>
													<select name="bluntGA[anchors][enable]" id="bluntGA_anchors_enable">
														<?php 
															$options = array(1 => 'Yes', 0 => 'No');
															foreach ($options as $value => $label) {
																$selected = false;
																if (isset($_POST['bluntGA']) && $_POST['bluntGA']['anchors']['enable'] == $value) {
																	$selected = true;
																} elseif (!isset($_POST['bluntGA']) && $this->settings['anchors']['enable'] == $value) {
																	$selected = true;
																}
																?>
																	<option value="<?php echo $value; ?>"<?php 
																			if ($selected) {
																				?> selected="selected"<?php 
																			}
																		?>><?php echo $label; ?></option>
																<?php 
															} // end foreach option
														?>
													</select>
												</label><br />
												<label for="bluntGA_anchors_method">
													<span class="label">Tracking Method: </span>
													<select name="bluntGA[anchors][method]" id="bluntGA_anchors_method">
														<?php 
															$options = array('event' => 'Track as Events',
																							 'virtual' => 'Track as Virtual Page Views');
															foreach ($options as $value => $label) {
																$selected = false;
																if (isset($_POST['bluntGA']) && $_POST['bluntGA']['anchors']['method'] == $value) {
																	$selected = true;
																} elseif (!isset($_POST['bluntGA']) && $this->settings['anchors']['method'] == $value) {
																	$selected = true;
																}
																?>
																	<option value="<?php echo $value; ?>"<?php 
																			if ($selected) {
																				?> selected="selected"<?php 
																			}
																		?>><?php echo $label; ?></option>
																<?php 
															} // end foreach option
														?>
													</select>
												</label><br />
												<label for="bluntGA_anchors_category">
													<span class="label">GA Event Category: </span>
													<input type="text" name="bluntGA[anchors][category]" id="bluntGA_anchors_category" value="<?php 
															if (isset($_POST['bluntGA'])) {
																echo $_POST['bluntGA']['anchors']['category'];
															} else {
																echo $this->settings['anchors']['category'];
															}
														?>" />
												</label><br />
											</fieldset>
											<div class="blunt_ga_help">
												<p>
													Anchor links are links within a page and similar to: <br />
													<code>&lt;a href="#section-2"&gt;Section 2&lt;/a&gt;</code>
												</p>
												<p>
													Anchor link tracking must be enabled if you want to use anchors
													for conversion tracking below.
												</p>
											</div>
											<div class="clear"></div>
										</div>
									</div>
									<input type="submit" name="bluntGAsubmit" value="Save Changes" class="button" />
									<div class="postbox">
										<div class="handlediv" title="Click to toggle"><br /></div>
										<h3 class="hndl"><span>404 Tracking</span></h3>
										<div class="inside not_found">
											<fieldset>
												<label for="bluntGA_not_found_enable">
													<span class="label">Enable 404 Tracking: </span>
													<select name="bluntGA[not_found][enable]" id="bluntGA_not_found_enable">
														<?php 
															$options = array(1 => 'Yes', 0 => 'No');
															foreach ($options as $value => $label) {
																$selected = false;
																if (isset($_POST['bluntGA']) && $_POST['bluntGA']['not_found']['enable'] == $value) {
																	$selected = true;
																} elseif (!isset($_POST['bluntGA']) && $this->settings['not_found']['enable'] == $value) {
																	$selected = true;
																}
																?>
																	<option value="<?php echo $value; ?>"<?php 
																			if ($selected) {
																				?> selected="selected"<?php 
																			}
																		?>><?php echo $label; ?></option>
																<?php 
															} // end foreach option
														?>
													</select>
												</label><br />
												<label for="bluntGA_not_found_method">
													<span class="label">Tracking Method: </span>
													<select name="bluntGA[not_found][method]" id="bluntGA_not_found_method">
														<?php 
															$options = array('event' => 'Track as Events',
																							 'virtual' => 'Track as Virtual Page Views');
															foreach ($options as $value => $label) {
																$selected = false;
																if (isset($_POST['bluntGA']) && $_POST['bluntGA']['not_found']['method'] == $value) {
																	$selected = true;
																} elseif (!isset($_POST['bluntGA']) && $this->settings['not_found']['method'] == $value) {
																	$selected = true;
																}
																?>
																	<option value="<?php echo $value; ?>"<?php 
																			if ($selected) {
																				?> selected="selected"<?php 
																			}
																		?>><?php echo $label; ?></option>
																<?php 
															} // end foreach option
														?>
													</select>
												</label><br />
												<label for="bluntGA_not_found_category">
													<span class="label">GA Event Category: </span>
													<input type="text" name="bluntGA[not_found][category]" id="bluntGA_not_found_category" value="<?php 
															if (isset($_POST['bluntGA'])) {
																echo $_POST['bluntGA']['not_found']['category'];
															} else {
																echo $this->settings['not_found']['category'];
															}
														?>" />
												</label><br />
												<label for="bluntGA_not_found_page_view">
													<span class="label">Record Page View: </span>
													<select name="bluntGA[not_found][page_view]" id="bluntGA_not_found_page_view">
														<?php 
															$options = array(1 => 'Yes', 0 => 'No');
															foreach ($options as $value => $label) {
																$selected = false;
																if (isset($_POST['bluntGA']) && $_POST['bluntGA']['not_found']['page_view'] == $value) {
																	$selected = true;
																} elseif (!isset($_POST['bluntGA']) && $this->settings['not_found']['page_view'] == $value) {
																	$selected = true;
																}
																?>
																	<option value="<?php echo $value; ?>"<?php 
																			if ($selected) {
																				?> selected="selected"<?php 
																			}
																		?>><?php echo $label; ?></option>
																<?php 
															} // end foreach option
														?>
													</select>
												</label><br />
											</fieldset>
											<div class="blunt_ga_help">
												<p>
													404 Tracking allows you to track 404 error pages as either events or virtual page views and to turn off the generation of the normal page view of a page.
												</p>
												<p>
													The <strong>action</strong> of this event will be the page url that generated the 404 error and the <strong>label</strong> will be set to the referring URL.
												</p>
												<p>
													You can disable tracking page views of 404 pages whether or not us turn on 404 event tracking.
												</p>
											</div>
											<div class="clear"></div>
										</div>
									</div>
									<input type="submit" name="bluntGAsubmit" value="Save Changes" class="button" />
									<div class="postbox"><!-- closed -->
										<div class="handlediv" title="Click to toggle"><br /></div>
										<h3 class="hndl"><span>Form Submission Tracking</span></h3>
										<div class="inside form_settings">
											<fieldset>
												<label for="bluntGA_form_enable">
													<span class="label">Enable Form Submission Tracking: </span>
													<select name="bluntGA[form][enable]" id="bluntGA_form_enable">
														<?php 
															$options = array(1 => 'Yes', 0 => 'No');
															foreach ($options as $value => $label) {
																$selected = false;
																if (isset($_POST['bluntGA']) && $_POST['bluntGA']['form']['enable'] == $value) {
																	$selected = true;
																} elseif (!isset($_POST['bluntGA']) && $this->settings['form']['enable'] == $value) {
																	$selected = true;
																}
																?>
																	<option value="<?php echo $value; ?>"<?php 
																			if ($selected) {
																				?> selected="selected"<?php 
																			}
																		?>><?php echo $label; ?></option>
																<?php 
															} // end foreach option
														?>
													</select>
												</label><br />
												<label for="bluntGA_form_method">
													<span class="label">Tracking Method: </span>
													<select name="bluntGA[form][method]" id="bluntGA_form_method">
														<?php 
															$options = array('event' => 'Track as Events',
																							 'virtual' => 'Track as Virtual Page Views');
															foreach ($options as $value => $label) {
																$selected = false;
																if (isset($_POST['bluntGA']) && $_POST['bluntGA']['form']['method'] == $value) {
																	$selected = true;
																} elseif (!isset($_POST['bluntGA']) && $this->settings['form']['method'] == $value) {
																	$selected = true;
																}
																?>
																	<option value="<?php echo $value; ?>"<?php 
																			if ($selected) {
																				?> selected="selected"<?php 
																			}
																		?>><?php echo $label; ?></option>
																<?php 
															} // end foreach option
														?>
													</select>
												</label><br />
												<label for="bluntGA_form_category">
													<span class="label">GA Event Category: </span>
													<input type="text" name="bluntGA[form][category]" id="bluntGA_form_category" value="<?php 
															if (isset($_POST['bluntGA'])) {
																echo $_POST['bluntGA']['form']['category'];
															} else {
																echo $this->settings['form']['category'];
															}
														?>" />
												</label><br />
											</fieldset>
											<div class="blunt_ga_help">
												<p>
													Form submmision tracking will record an event every time a form is submitted on your site. However, this does not tell you if the form submission was successful or not, only that the form was submitted.
												</p>
												<p>
													The data that can be collected by detecting that forms are submitted is questionable and this feature is only included for completeness. For an accurate indication of forms that are submitted successfully, conversion tracking (below) is a better alternative. This could be usefull, however, for forms that submit to an external site. For example, if you have a signup for a newsletter provided be a third party, you could track these submission. But be aware that this is an all or nothing thing, you cannot selectively track specific forms at this time. To do that you would need to manually add the event tracking code to the submit event of the form you wish to track. If you can do this is would be a better alternative to tracking all form submissions on your site (especially a wordpress site unless you want to track all of the comment submission to your blog articles)
												</p>
												<p>
													Note: Action value will be &quot;self&quot; if the form submits to the same page that it is located on. 
												</p>
											</div>
											<div class="clear"></div>
										</div>
									</div>
									<input type="submit" name="bluntGAsubmit" value="Save Changes" class="button" />
									<div class="postbox"><!-- closed -->
										<div class="handlediv" title="Click to toggle"><br /></div>
										<h3 class="hndl"><span>Conversion Tracking</span></h3>
										<div class="inside conversions_settings">
											<fieldset>
												<label for="bluntGA_conversions_enable">
													<span class="label">Enable Conversion Tracking: </span>
													<select name="bluntGA[conversions][enable]" id="bluntGA_conversions_enable">
														<?php 
															$options = array(1 => 'Yes', 0 => 'No');
															foreach ($options as $value => $label) {
																$selected = false;
																if (isset($_POST['bluntGA']) && $_POST['bluntGA']['conversions']['enable'] == $value) {
																	$selected = true;
																} elseif (!isset($_POST['bluntGA']) && $this->settings['conversions']['enable'] == $value) {
																	$selected = true;
																}
																?>
																	<option value="<?php echo $value; ?>"<?php 
																			if ($selected) {
																				?> selected="selected"<?php 
																			}
																		?>><?php echo $label; ?></option>
																<?php 
															} // end foreach option
														?>
													</select>
												</label><br />
												<label for="bluntGA_conversions_method">
													<span class="label">Tracking Method: </span>
													<select name="bluntGA[conversions][method]" id="bluntGA_conversions_method">
														<?php 
															$options = array('event' => 'Track as Events',
																							 'virtual' => 'Track as Virtual Page Views');
															foreach ($options as $value => $label) {
																$selected = false;
																if (isset($_POST['bluntGA']) && $_POST['bluntGA']['conversions']['method'] == $value) {
																	$selected = true;
																} elseif (!isset($_POST['bluntGA']) && $this->settings['conversions']['method'] == $value) {
																	$selected = true;
																}
																?>
																	<option value="<?php echo $value; ?>"<?php 
																			if ($selected) {
																				?> selected="selected"<?php 
																			}
																		?>><?php echo $label; ?></option>
																<?php 
															} // end foreach option
														?>
													</select>
												</label><br />
												<label for="bluntGA_conversions_category">
													<span class="label">GA Event Category: </span>
													<input type="text" name="bluntGA[conversions][category]" id="bluntGA_conversions_category" value="<?php 
															if (isset($_POST['bluntGA'])) {
																echo $_POST['bluntGA']['conversions']['category'];
															} else {
																echo $this->settings['conversions']['category'];
															}
														?>" />
												</label><br />
												<label for="bluntGA_conversions_pages">
													<span class="label">Conversion Pages: </span>
													<textarea name="bluntGA[conversions][pages]" id="bluntGA_conversions_pages" cols="40" rows="5" class="list1"><?php 
															if (isset($_POST['bluntGA'])) {
																echo $_POST['bluntGA']['conversions']['pages'];
															} else {
																$value = implode("\r\n", $this->settings['conversions']['pages']);
																echo $value;
															}
														?></textarea>
												</label><br />
											</fieldset>
											<div class="blunt_ga_help">
												<p>
													Conversion tracking allows you to specify a list of pages that you consider as your &quot;Conversion&quot; pages. The only way that a visitor should be able reach these pages should be by performing some action, for example; successfully submitting a form.
												</p>
												<p>
													Event variable values are different for conversion events and at the same time they are the same. The varaibles that are different are:
												</p>
												<ul>
													<li><strong>action:</strong> The URL of the conversion page.</li>
													<li><strong>opt_label:</strong> The URL of the referring page.</li>
												</ul>
												<p>
													For the Conversion Pages input field, enter each page that is to be considered a conversion page on a seperate line. The URL for each page should be relative from the root of your website. An example of some conversion pages:
												</p>
												<ul>
													<li>/thank-you/</li>
													<li>/contant-us/thank-you/</li>
												</ul>
												<p>
													Conversion pages may also be in the form of an anchor hash (examples: #converted, #thank-you). The hash (#) Must be the first character. All URLs that have the anchor will be considered a conversion no matter what appears before the anchor, for example /#thank-you and /about-us/#thank-you would both record a conversion event if the hash tag #thank-you is added as a conversion page.
												</p>
											</div>
											<div class="clear"></div>
										</div>
									</div>
									<input type="submit" name="bluntGAsubmit" value="Save Changes" class="button" />
									
									
									<div class="postbox"><!-- closed -->
										<div class="handlediv" title="Click to toggle"><br /></div>
										<h3 class="hndl"><span>Cross Site Tracking Prefix</span></h3>
										<div class="inside cross_site_settings">
											<fieldset>
												<label for="bluntGA_cross_site_enable">
													<span class="label">Enable Cross Site Prefix: </span>
													<select name="bluntGA[cross_site][enable]" id="bluntGA_cross_site_enable">
														<?php 
															$options = array(1 => 'Yes', 0 => 'No');
															foreach ($options as $value => $label) {
																$selected = false;
																if (isset($_POST['bluntGA']) && $_POST['bluntGA']['cross_site']['enable'] == $value) {
																	$selected = true;
																} elseif (!isset($_POST['bluntGA']) && $this->settings['cross_site']['enable'] == $value) {
																	$selected = true;
																}
																?>
																	<option value="<?php echo $value; ?>"<?php 
																			if ($selected) {
																				?> selected="selected"<?php 
																			}
																		?>><?php echo $label; ?></option>
																<?php 
															} // end foreach option
														?>
													</select>
												</label><br />
												<!--
												<label for="bluntGA_cross_site_domain">
													<span class="label">Cross Site Domain: </span>
													<input type="text" name="bluntGA[cross_site][domain]" id="bluntGA_cross_site_domain" value="<?php 
															if (isset($_POST['bluntGA'])) {
																echo $_POST['bluntGA']['cross_site']['domain'];
															} else {
																echo $this->settings['cross_site']['domain'];
															}
														?>" class="wide" />
												</label><br />
												-->
												<label for="bluntGA_cross_site_prefix">
													<span class="label">Cross Site Prefix: </span>
													<input type="text" name="bluntGA[cross_site][prefix]" id="bluntGA_cross_site_prefix" value="<?php 
															if (isset($_POST['bluntGA'])) {
																echo $_POST['bluntGA']['cross_site']['prefix'];
															} else {
																echo $this->settings['cross_site']['prefix'];
															}
														?>" />
												</label><br />
											</fieldset>
											<div class="blunt_ga_help">
												<p>
													Cross site tracking gives you the ability to track your site under the same GA account as another site. For example, if you have a main site that is www.my-main-site.com and you have a related site or sites, for example www.my-blog-site.com or www.my-store-site.com, rather than track each site seperately you can track all page views as if they are located at www.my-main-site.com.
												</p>
												<p><strong>Using a Cross Site Prefix</strong></p>
												<p>
													One on the main disadvantages of cross site tracking is that if you have two sites and both of these sites have an "About Us" page with identicaly URIs (http://www.my-main-site.com/about-us/ and http://www.my-blog-site.com/about-us/) then views of both pages will be recorded together in your GA reports as /about-us/. 
												</p>
												<p>
												
													Adding a cross site prefix to pages of your site enables you to tell on what site a page is actually located. Adding a prefix to the pages of the second site allows these page views to be tracked seperately. This is done in the same way that virtual page views are recorded. 
												</p>
												<p>
													The page prefix can contain letters, numbers, underscores (_), hyphens (-) and dots (.) and it will be prepended to all URI's for the site.</p>
												<p>
													Additionally, there are 2 variables that can be used as the cross site prefix:
												</p>
												<ul>
													<li><strong>%%domain%%</strong> This variable will be replaced with the complete domain name of the site as viewd in a browser (example: www.my-blog-site.com) </li>
													<!--<li><strong>%%top-level-domain%%:</strong> This setting will be replaced by the top level domain of the site. For example if your site is www.mysite.com or blog.mysite.com this setting will set the domain to <strong>mysite.com</strong></li>-->
													<li><strong>%%sitename%%</strong> This variable will be replaced with the title of your site as set your general site setting. Your site title will first be cleaned of any special characters so that anycharacters that would not normally be allowed in this field will be replaced by hyphens (-).</li>
												</ul>
												<p>
													Please note that if you choose to use one of these variables that you can only use one of them and that the varaible must be the only thing that appears in this field.
												</p>
											</div>
											<div class="clear"></div>
										</div>
									</div>
									<input type="submit" name="bluntGAsubmit" value="Save Changes" class="button" />
									
									
									<div class="postbox"><!-- closed -->
										<div class="handlediv" title="Click to toggle"><br /></div>
										<h3 class="hndl"><span>Cross Site Linking</span></h3>
										<div class="inside cross_site_settings">
											<fieldset>
												<label for="bluntGA_cross_link_enable">
													<span class="label">Enable Cross Site Linking: </span>
													<select name="bluntGA[cross_link][enable]" id="bluntGA_cross_link_enable">
														<?php 
															$options = array(1 => 'Yes', 0 => 'No');
															foreach ($options as $value => $label) {
																$selected = false;
																if (isset($_POST['bluntGA']) && $_POST['bluntGA']['cross_link']['enable'] == $value) {
																	$selected = true;
																} elseif (!isset($_POST['bluntGA']) && $this->settings['cross_link']['enable'] == $value) {
																	$selected = true;
																}
																?>
																	<option value="<?php echo $value; ?>"<?php 
																			if ($selected) {
																				?> selected="selected"<?php 
																			}
																		?>><?php echo $label; ?></option>
																<?php 
															} // end foreach option
														?>
													</select>
												</label><br />
												<label for="bluntGA_cross_link_event">
													<span class="label">Enable Cross Site Linking Events: </span>
													<select name="bluntGA[cross_link][event]" id="bluntGA_cross_link_event">
														<?php 
															$options = array(1 => 'Yes', 0 => 'No');
															foreach ($options as $value => $label) {
																$selected = false;
																if (isset($_POST['bluntGA']) && $_POST['bluntGA']['cross_link']['event'] == $value) {
																	$selected = true;
																} elseif (!isset($_POST['bluntGA']) && $this->settings['cross_link']['event'] == $value) {
																	$selected = true;
																}
																?>
																	<option value="<?php echo $value; ?>"<?php 
																			if ($selected) {
																				?> selected="selected"<?php 
																			}
																		?>><?php echo $label; ?></option>
																<?php 
															} // end foreach option
														?>
													</select>
												</label><br />
												<label for="bluntGA_cross_link_domains">
													<span class="label">Cross Linked Domains: </span>
													<textarea name="bluntGA[cross_link][domains]" id="bluntGA_cross_link_domains" cols="40" rows="5" class="list1"><?php 
															if (isset($_POST['bluntGA'])) {
																echo $_POST['bluntGA']['cross_link']['domains'];
															} else {
																$value = implode("\r\n", $this->settings['cross_link']['domains']);
																echo $value;
															}
														?></textarea>
												</label><br />
											</fieldset>
											<div class="blunt_ga_help">
												<p>
													Normally, cookies are not shared across domains so it is difficult to track visitors across multiple top level domains, even with cross site tracking enabled. However, it is possible to share tracking information by enabling cross site linking. With cross site linking enabled JavaScript is used to load a page from a linked site and tranfer tracking information to the second site. Cross site linking allows for the continuous tracking of the visitors session across all linked domains.
												</p>
												<p>
													<strong>Important Note:</strong> Enabling Cross Site Linking will disable the ability to open links in a new window or tab for outbound links that take the visitor to a linked domain.
												</p>
												<p>
													<strong>Cross Site Linking Events:</strong> Normally, when cross site linking is used outbound link events will not be sent when the link is to one of the linked domains. This is because you will be recording the page views of both sites. However, If you would like to send these events anyway so that you can track them then enable this setting. These events will appear in GA in whatever way you have set outbound links to be tracked.
												</p>
												<p>
													<strong>Cross Linked Domains:</strong> Enter a list of all sites that should be linked together, one domain per line. This list must have at least one domain. All domains that this domain can be linked to should be include. This plugin considers all domains and sub-domains to be unique. The reason for this is so that event tracking can be turned on across sub-domains. So, for instance, if I have two domains: www.mysite.com and blog.mysite.com, I would include all of the following domains in this list:
												</p>
												<ul>
													<li>mydomain.com</li>
													<li>www.mydomain.com</li>
													<li>blog.mydomain.com</li>
												</ul>
											</div>
											<div class="clear"></div>
										</div>
									</div>
									<input type="submit" name="bluntGAsubmit" value="Save Changes" class="button" />
								</div>
								<div id="postbox-container-1" class="postbox-container"></div>
							</div>
						</div>
					</form>
				</div>
			<?php 
		} // end public function showSettingsForm
		
		public function processSettingsForm() {
			/*
							$action = 'bluntGAoptions_submit';
							$name = 'bluntGA_nonce';
							$referer = true;
							$echo = true;
							wp_nonce_field($action, $name, $referer, $echo);
			*/
			$nonce = $_POST['bluntGA_nonce'];
			if (!wp_verify_nonce($nonce, 'bluntGAoptions_submit') ||
					$_POST['_wp_http_referer'] != $_SERVER['REQUEST_URI']) {
				$this->errors['security'] = 'Invalid Security Check';
				return;
			}
			
			
			$section_text = array('anchors' => 'Anchor Link Tracking',
														'external' => 'Outbound Link Tracking',
														'mailto' => 'Mailto Link Tracking',
														'document' => 'Document/File Link Tracking',
														'form' => 'Form Submission Tracking',
														'cross_site' => 'Cross Site Tracking',
														'cross_link' => 'Cross Site Linking',
														'conversions' => 'Conversion Tracking');
			//echo '<pre>'; print_r($_POST); echo '</pre>'; die;
			
			// clear user settings becuase if none are selected they will not be changed
			$this->settings['users'] = array();
			
			foreach ($_POST['bluntGA'] as $section => $options) {
				if (!is_array($options) || $section == 'users') {
					$error = false;
					$setting = $section;
					if ($setting != 'users') {
						$value = trim($options);
					} else {
						$value = $options;
					}
					switch ($setting) {
						case 'users':
							$this->settings[$setting] = $value;
							break;
						case 'ga_account':
							// check for valid ga account?
							// can be empty, basically turns off ga tracking
							if ($value != '') {
								if (!preg_match('/^UA-[0-9]{4,16}-[0-9]{1,4}$/', $value)) {
									// invalid GA #
									$message = 'The Google Analytics Account # Entered Does Not Appear to be Valid.';
									$this->errors[$setting] = $message;
									$this->process_error = true;
									$error = true;
								}
							} else {
								// warning, tracking off without a valid GA #
								$message = 'Warning: No Google Analytics Account # Given. Tracking Was Not Enabled.';
								$this->errors[$setting] = $message;
							}
							if (!$error) {
								$this->settings[$setting] = $value;
							}
							break;
						case 'domain':
							$this->settings[$setting] = $value;
							break;
						case 'custom_domain':
							if ($_POST['bluntGA']['domain'] == 'custom') {
								if (!$this->isValidDomain($value)) {
									$message = 'You selected custom for Domain but did not supply a valid domain name.';
									$this->errors[$setting] = $message;
								} else {
									$this->settings[$setting] = $value;
								}
							} else {
								$this->settings[$setting] = '';
							}
							break;
						default:
							// do nothing
							break;
					}
				} else {
					foreach ($options as $setting => $value) {
						$error = false;
						switch ($setting) {
							case 'enable':
							case 'new_window':
							case 'non_interact':
							case 'event':
							case 'page_view':
								// true/false
								if ($value == 1) {
									$value = true;
								} else {
									$value = false;
								}
								$this->settings[$section][$setting] = $value;
								break;								
							case 'method':
								// event/virtual
								if ($value != 'virtual') {
									$value = 'event';
								}
								$this->settings[$section][$setting] = $value;
								break;								
							case 'category':
								// text value, required if section is enabled
								// only allows letters, numbers, hyphen and underscore
								$value = trim($value);
								if ($value != '') {
									if (preg_match('/[^-_.a-z0-9]/i', $value)) {
										$message = '';
										$message .= 'GA Event Category for '.$section_text[$section].' Contains Invalid Characters.<br />';
										$message .= 'This field may only contain letters, numbers, _ (underscore), - (hyphen) and . (dot).';
										$this->errors[$section.'_'.$setting] = $message;
										$this->process_error = true;
										$error = true;
									}
								} else {
									// value is empty, required if section is enabled
									if ($_POST['bluntGA'][$section]['enable']) {
										$this->errors[$section.'_'.$setting] = 'GA Event Category is Required to Enable '.$section_text[$section];
										$this->process_error = true;
										$error = true;
									}
								}
								if (!$error) {
									$this->settings[$section][$setting] = $value;
								}
								break;
							case 'prefix':
							case 'suffix':
								// text value, not required
								// only allows letters, numbers, hyphen and underscore, dot
								$value = trim(trim($value), '/');
								if ($value != '' && 
										strtolower($value) != '%%domain%%' && 
										strtolower($value) != '%%sitename%%' //&& strtolower($value) != '%%top-level-domain%%'
										) {
									if (preg_match('/[^-_.a-z0-9]/i', $value)) {
										$message = '';
										$message .= $section_text[$section].' '.ucwords($setting).' Contains Invalid Characters.<br />';
										$message .= 'This field may only contain letters, numbers, _ (underscore), - (hyphen) and . (dot). or one of the varaibles %%domain%% or %%sitename%%';
										$this->errors[$section.'_'.$setting] = $message;
										$this->process_error = true;
										$error = true;
									}
								}
								if (!$error) {
									$this->settings[$section][$setting] = $value;
								}
								break;
							case 'extensions':
								// extention list
								// comma seperated list (?? , ; :), . is optional and removed
								// allows letters, numbers, underscore, hyphen, dot
								// does not allow valid web page extensions
								// requires at least one extension if document tracking enabled
								$extensions = preg_split('/\s*[,;]\s*\.*/', $value);
								foreach ($extensions as $index => $extension) {
									if ($extension == '') {
										unset($extensions[$index]);
									} else {
										if (preg_match('/[^-_.a-z0-9]/i', $extension)) {
											$message = 'Invalid Extension in Document Extensions List.';
											$this->errors[$section.'_'.$setting] = $message;
											$this->process_error = true;
											$error = true;
										}
									}
								} // end foreach extension
								$extensions = array_values($extensions);
								$_POST['bluntGA'][$section][$setting] = implode(', ', $extensions); 
								if (count($extensions) == 0) {
									if ($_POST['bluntGA'][$section]['enable']) {
										// document tracking enabled but no extensions given
										$message = 'At Least One Document Extension is Required to Enable '.$section_text[$section];
										$this->errors[$section.'_'.$setting] = $message;
										$this->process_error = true;
										$error = true;
									}
								}
								if (!$error) {
									$this->settings[$section][$setting] = $extensions;
								}
								break;
							case 'delay':
								// dealy time
								$value = floatval($value);
								$this->settings[$section][$setting] = $value; 
								break;
							case 'pages':
								// list of local pages, one per line
								// requires at least one if conversion tracking is enabled
								$pages = $this->splitList($value);
								if (count($pages) > 0) {
									$pages = preg_replace('#^(https?://)?'.preg_quote($_SERVER['HTTP_HOST']).'#i', '', $pages);
									$_POST['bluntGA'][$section][$option] = implode("\r\n", $pages);
									foreach ($pages as $page) {
										if (preg_match('#^https?://#', $page)) {
											$message = 'Conversion Pages For '.$section_text[$section].' May Only Contain Pages On This Domain.';
											$this->errors[$section.'_'.$setting] = $message;
											$this->process_error = true;
											$error = true;
											break;
										} elseif (!preg_match('#^/#', $page) && !preg_match('/^#/', $page)) {
												$message = 'Conversion Pages For '.$section_text[$section].' Must Be Absolute From Site Root (start with a slash /) or be an anchor begining with a hash (#).';
											$this->errors[$section.'_'.$setting] = $message;
											$this->process_error = true;
											$error = true;
											break;
										}
									} // end foreach page
								} else {
									if ($_POST['bluntGA'][$section]['enable']) {
										// enabled but no conversion pages given
										$message = 'You Must Specify At Lease One Conversion Page to Enable '.$section_text[$section].'.';
										$this->errors[$section.'_'.$setting] = $message;
										$this->process_error = true;
										$error = true;
									}
								}
								if (!$error) {
									$this->settings[$section][$setting] = $pages;
								}
								break;
							case 'domain':
								// single domain name, connont be this domain
								// required if cross site tracking is enabled
								$value = preg_replace('#^((https?:/)?/)?([^/]*)(/.*)?$#i', '\3', trim($value));
								if ($value == '') {
									// no domain given, check enable, required to enable
									if ($_POST['bluntGA']['cross_site']['enable']) {
										$message = 'No Domain Given for '.$section_text[$section].'.';
										$this->errors[$section.'_'.$setting] = $message;
										$this->process_error = true;
										$error = true;
									}
								} else {
									// domain given, check it is a valid looking domain name
									// and that it is not this domain name
									if ($value == $_SERVER['HTTP_HOST']) {
										// cannot specify this domain
										$message = 'The Domain Entered for '.$section_text[$section].' is This Domain.<br />';
										$message .= 'You must enter a different domain to enable '.$section_text[$section].'.';
										$this->errors[$section.'_'.$setting] = $message;
										$this->process_error = true;
										$error = true;
									} elseif (!$this->isValidDomain($value)) {
										// invalid domain name
										$message = 'The Domain Entered for '.$section_text[$section].' Does Not Appear to be Valid.';
										$this->errors[$section.'_'.$setting] = $message;
										$this->process_error = true;
										$error = true;
									}
								}
								$_POST['bluntGA']['cross_site']['domain'] = $value;
								if (!$error) {
									$this->settings[$section][$setting] = $value;
								}
								break;
							case 'domains':
								// list of domains
								// requires at least one if cross site linking is enabled
								// the domain given in cross site tracking must be included
								$domains = $this->splitList($value);
								$domains = preg_replace('#^((https?:/)?/)?([^/]*)(/.*)?$#i', '\3', $domains);
								if ($_POST['bluntGA']['cross_site']['domain'] != '' &&
										!in_array($_POST['bluntGA']['cross_site']['domain'], $domains) &&
										$_POST['bluntGA']['cross_site']['domain'] != $_SERVER['HTTP_HOST']) {
									$domains[] = $_POST['bluntGA']['cross_site']['domain'];
								}
								if (count($domains) > 0) {
									foreach ($domains as $domain) {
										if (!$this->isValidDomain($domain)) {
											// invalid domain name
											$message = 'A Domain Entered for '.$section_text[$section].' Does Not Appear to be Valid.';
											$this->errors[$section.'_'.$setting] = $message;
											$this->process_error = true;
											$error = true;
											break;
										}
									} // end foreach domain
								} else {
									// no domain given for cross site linking
									// if enabled then there must be at least one
									if ($_POST['bluntGA']['cross_link']['enable']) {
										$message = 'No Domains Entered for '.$section_text[$section].'.<br />';
										$message .= 'You must enter at least one valid domain to enable '.$section_text[$section].'.';
										$this->errors[$section.'_'.$setting] = $message;
										$this->process_error = true;
										$error = true;
										break;
									}
								}
								if (!$error) {
									$this->settings[$section][$setting] = $domains;
								}
								break;
							default:
								// do nothing
								break;
						} // end switch $setting
					} // end foreach $options
				} // end if array else
			} // end foreach $_POST['bluntGA']
			
			// if no processing errors occur
			// save new settings and clear posted values
			// set error message to display successful save
			if (!$this->process_error) {
				//echo '<pre>'; print_r($this->settings); echo '</pre>'; die;
				$this->errors['success'] = 'All Settings Saved.';
				//echo 'XXX<br>';
				$this->saveSettings();
				$_POST = array();
			}
		} // end public function processChanges
		
		private function isValidDomain($value) {
			$valid = true;
			if ((!preg_match('/^[-0-9a-z]+(\.[-0-9a-z]+)+$/i', $value) && 
					 !preg_match('/^[0-9]+(\.[0-9]+){3}$/', $value)) || 
					strlen($value) > 64) {
				$valid = false;
			}
			return $valid;
		} // end private function isValidDomain
		
		private function splitList($value) {
			$value = trim($value);
			//$value = preg_replace('/\s*[,]\s*/', '\n', $value);
			$value = str_replace("\r", "\n", $value);
			$value = preg_replace('/\n+/', "\n", $value);
			$array = preg_split('/\s*\n\s*/', $value);
			foreach ($array as $index => $value) {
				if ($value == '') {
					unset($array[$index]);
				}
			}
			$array = array_values($array);
			return $array;
		} // end private function splitList
		
		private function update_settings() {
			$settings = $this->defaultSettings();
			if ($this->settings === false) {
				$this->settings = array();
			} else {
				// this preserves the equivilant of how the plugin worked before the domain setting was added
				$this->settings['domain'] = 'auto';
			}
			$this->settings = $this->merge_new_settings($this->settings, $settings);
			//unset($this->settings['found']);
			if (isset($this->settings['sharethis'])) {
				unset($this->settings['sharethis']);
			}
			$this->settings['version'] = '4.0.0';
			$this->saveSettings();
		} // end private function update_settings
		
		private function merge_new_settings($array1, $array2) {
			// recursive function to merge new keys/values
			// that exist in array2 into array1
			foreach ($array2 as $key => $value) {
				if ((is_array($value) && isset($array1[$key])) && $key != 'users') {
					// recurse
					$array1[$key] = $this->merge_new_settings($array1[$key], $value);
				} elseif (!isset($array1[$key])) {
					// add the value
					$array1[$key] = $value;
				}
			} // end foreach $array2
			return $array1;
		} // end private function merger_new_settings
		
		private function defaultSettings() {
			$settings = array('version' => $this->version,
												'ga_account' => '',
												'domain' => '.%%top-level-domain%%',
												'custom_domain' => '',
												'anchors' => array('enable' => false,   // links to # on same page
																					 'method' => 'event',  // valid values are event and virtual (virtual == virtual page view)
																					 'category' => 'anchor',
																					 'non_interact' => false,
																					 'new_window' => false,
																					 'delay' => 0.0),
												'external' => array('enable' => true,
																						'method' => 'event',
																						'category' => 'outbound',
																						'non_interact' => true,
																						'new_window' => true,
																						'delay' => 0.1),
												'mailto' => array('enable' => true,
																					'method' => 'event',
																					'category' => 'mailto',
																					'non_interact' => false,
																					'new_window' => false,
																					'delay' => 0.1),
												'click_to_call' => array('enable' => true,
																								 'method' => 'event',
																								 'category' => 'click_to_call',
																								 'non_interact' => false,
																								 'new_window' => false,
																								 'delay' => 0.1),
												'document' => array('enable' => true,
																						'method' => 'event',
																						'category' => 'document',
																						'non_interact' => false,
																						'new_window' => true,
																						'delay' => 0.1,
																						'extensions' => array('pdf',
																																	'doc',
																																	'docx',
																																	'xls',
																																	'csv',
																																	'jpg',
																																	'gif',
																																	'mp3',
																																	'swf',
																																	'txt',
																																	'ppt',
																																	'zip',
																																	'gz',
																																	'dmg',
																																	'xml')),
												'form' => array('enable' => false,
																				'method' => 'event',
																				'category' => 'form_submit',
																				'non_interact' => false,
																				'delay' => 0.1),
												'cross_site' => array('enable' => false,
																							'prefix' => '',
																							'sufix' => ''),
												'cross_link' => array('enable' => false,
																							'event' => false,
																							'domains' => array()),
												'conversions' => array('enable' => false,				// pages for conversions must be abs from doc root
																							 'method' => 'event',
																							 'category' => 'conversion',
																							 'non_interact' => false,
																							 'pages' => array()),
												'users' => array('subscriber'),
												'not_found' => array('enable' => false,
																						 'method' => 'event',
																						 'category' => '404',
																						 'non_interact' => false,
																						 'delay' => 0.0,
																						 'page_view' => false));
			return $settings;
		} // end private function defaultSettings
		
		private function saveSettings() {
			//echo '<pre>'; print_r($this->settings); echo '</pre>';die;
			$settings = json_encode($this->settings);
			$option = 'bluntGA_settings';
			update_option($option, $settings);
		} // end private fuction saveSettings
		
		private function getSettings() {
			$option = 'bluntGA_settings';
			$settings = false;
			if (($settings = get_option($option, false)) !== false) {
				$settings = json_decode($settings, true);
			}
			return $settings;
		} // end private function getSettings
		
		public static function disable() {
			self::$instance->enabled = false;
		} // end public static function disable
		
	} // end class bluntGA
	
?>