	
		
	
	/*
		The version loaded by the plugin is a minified verson of this file. 
		This full version is only loaded for development modifications and debuging
	*/
	
	// localized in version 3.3.0
	//var bluntGA.debug = true;
	//var bluntGA_done = false;
	
	// fix older ie indexOf on array
	if (!Array.prototype.indexOf) {
		Array.prototype.indexOf = function(obj, start) {
			var value = -1;
			var len = this.length;
			for (var i=(start || 0); i<len; i++) {
				if (this[i] === obj) {
					value = i;
				}
			}
			return value;
		} // end method create
	} // end if not indexOf exists
	
	// install GA and link tracking
	if (bluntGA.opt != 'undefined') {
		bluntGA.opt = eval(unescape(bluntGA.opt));
	}
	
	// part of standard GA startup
	var _gaq = _gaq || [];
	
	if (bluntGA.opt && bluntGA.opt['ga_account'] != '') {
		
		if (!bluntGA.debug) {
			_gaq.push(['_setAccount', bluntGA.opt['ga_account']]);
		} else {
			alert('_gaq.push([\'_setAccount\', '+bluntGA.opt['ga_account']+'])')
		}
		
		// set sample rate if not 1
		// make sure it is an int from 2 to 10 before setting
		// this portion of the code is incorrect as this is supposed to be a %
		// disabling this until possibly re-implementing it in the future
		/*
		var theRate = Math.round(bluntGA.opt['sample_rate']);
		if (theRate < 1) {
			theRate = 1;
		} else if (theRate > 10) {
			theRate = 10;
		}
		if (bluntGA.opt['sample_rate'] > 1) {
			_gaq.push(['_setSampleRate', bluntGA.opt['sample_rate']]);
		}
		*/
		
		// get the page location to use for pageview so that we can alter it if needed
		var blundGApageLocation = document.location.pathname;
		if (bluntGA.opt['anchors']['enable']) {
			blundGApageLocation += document.location.hash;
		}
		
		if (!bluntGA.debug) {
			_gaq.push(['_setDomainName', bluntGA.opt['domain']]);
		} else {
			alert('_gaq.push([\'_setDomainName\', '+bluntGA.opt['domain']+']);')
		}
		
		
		if (bluntGA.opt['cross_site']['enable'] && bluntGA.opt['cross_site']['domain'] != '') {
			
			blundGApageLocation = '/'+bluntGA.opt['cross_site']['prefix']+'/'+blundGApageLocation+'/'+bluntGA.opt['cross_site']['sufix']+'/';
			blundGApageLocation = blundGApageLocation.replace(/\/+/g, '/');
		}
		
		if (bluntGA.opt['cross_link']['enable']) {
			if (!bluntGA.debug) {
				_gaq.push(['_setAllowLinker', true]);
			} else {
				alert('_gaq.push([\'_setAllowLinker\', true]);');
			}
		}
		
		var sendPageView = true;
		if (!bluntGA.opt['found']) {
			sendPageView = bluntGA.opt['not_found']['page_view'];
			// send event?
			if (bluntGA.opt['not_found']['enable']) {
				var theCategory = bluntGA.opt['not_found']['category']
				var theMethod = bluntGA.opt['not_found']['method']
				var theAction = document.location.pathname;
				if (bluntGA.opt['cross_site']['enable']) {
					theAction = '/'+bluntGA.opt['cross_site']['prefix']+'/'+theAction+'/'+bluntGA.opt['cross_site']['sufix']+'/';
				}
				var theLabel = document.referrer;
				var theValue = 0;
				var theNonInteract = bluntGA.opt['not_found']['non_interact'];
				if (bluntGA.opt['not_found']['method'] == 'event') {
					if (!bluntGA.debug) {
						_gaq.push(['_trackEvent', theCategory, theAction, theLabel, theValue, theNonInteract]);
					} else {
						alert('_gaq.push([\'_trackEvent\', '+theCategory+', '+theAction+', '+theLabel+', '+theValue+', '+theNonInteract+']);')
					}
				} else {
					var virtualPage = '/'+theCategory+'/'+'?action='+theAction+'&label='+theLabel;
					if (!bluntGA.debug) {
						//alert('send 404 event');
						_gaq.push(['_trackPageview', virtualPage]);
					} else {
						alert('_gaq.push([\'_trackPageview\', '+virtualPage+'])');
					}
				}
			}
		}
		if (sendPageView) {
			if (!bluntGA.debug) {
				//alert('send page view');
				_gaq.push(['_trackPageview', blundGApageLocation]);
			} else {
				alert('_gaq.push([\'_trackPageview\', '+blundGApageLocation+'])');
			}
		}
		
		(function() {
			var ga = document.createElement('script'); 
			ga.type = 'text/javascript';
			ga.async = true;
			ga.src = ('https:' == document.location.protocol ? 'https:/'+'/ssl' : 'http:/'+'/www') + '.google-analytics.com/ga.js';
			var s = document.getElementsByTagName('script')[0];
			s.parentNode.insertBefore(ga, s);
		})();
		// end GA installation
		
		// and event listener
		if (window.addEventListener) {
			window.addEventListener('DOMContentLoaded', bluntGA_addEvents, false);
		}
		// older browsers that do not support DOMContentLoaded
		// add event listener to document to add event tracking
		if (window.addEventListener) {
			window.addEventListener('load', bluntGA_addEvents, false);
		} else if (window.attachEvent) {
			window.attachEvent('onload', bluntGA_addEvents);
		}
		// end add listener
		// *********************************************************************
		
		// if setting up GA see if we should set up sharethis
		/* Removed everything to do with sharethis in 3.2.1
				 may re-add this in the but it isn't used at this time and was never implemented in WP the plugin
		if (bluntGA.opt['sharethis']['enable'] && bluntGA.opt['sharethis']['publisher_id'] != '') {
			var switchTo5x  = bluntGA.opt['sharethis']['switchTo5x'];
			var sharethisEL = document.createElement('script');
			sharethisEL.type = 'text/javascript';
			sharethisEL.async = true;
			sharethisEL.src = 'http:/'+'/w.sharethis.com/button/buttons.js';
			// stlight.options cannot be set until the the sharethis script is loaded
			// adding an onload function to the script ensures it is loaded before trying to access its properties
			if (window.addEventListener) {
				// standards compliant
				sharethisEL.addEventListener('load', bluntGA_sharethisOptions, false);
			} else if (window.attachEvent) {
				// IE
				sharethisEL.attachEvent('onload', bluntGA_sharethisOptions);
			} else {
				// unable to add event listener, extremely rare (I have not seen this happen)
			}
			var ss = document.getElementsByTagName('script')[0];
			ss.parentNode.insertBefore(sharethisEL, s);
		} // end if sharethis
		*/
	}
	
	// *********************************************************************
	// called when the sharethis script load event triggers
	/* All share this funtions removed in version 3.2.1
	function bluntGA_sharethisOptions() {
		stLight.options({publisher:bluntGA.opt['sharethis']['publisher_id']});
	} // end function bluntGA_sharethisOptions
	*/
	// *********************************************************************
	
	// *********************************************************************
	// main function/class
	function bluntGA_addEvents() {
		if (bluntGA.done) {
			if (bluntGA.debug) {
				alert('Already Done');
			}
			return;
		}
		bluntGA.done = true;
		
		var opt = bluntGA.opt;
		
		var theLoc = 'http:/'+'/'+location.host;
		var theLocLen = theLoc.length;
		var theSecLoc = 'https:/'+'/'+location.host;
		var theSecLocLen = theLoc.length;
		
		if (opt['external']['enable'] || 
				opt['mailto']['enable'] || 
				opt['document']['enable'] || 
				opt['anchors']['enable'] ||
				opt['click_to_call']['enable']) {
			addAnchorClickEvents();
		}
		
		if (opt['form']['enable']) {
			addFormSubmitEvents();
		}
		
		if (opt['conversions']['enable']) {
			trackConversion();
		}
		
		function addAnchorClickEvents() {
			var theAnchors = document.getElementsByTagName('a');
			var anchorsLength = theAnchors.length;
			for (var i=0; i<anchorsLength; i++) {
				var theMethod = '';
				var theCategory = '';
				var theAction = '';
				var theLabel = document.location.pathname.replace(/#.*$/, '');
				if (opt['cross_site']['enable']) {
					theLabel = '/'+opt['cross_site']['prefix']+'/'+theLabel+'/'+opt['cross_site']['sufix']+'/';
					theLabel = theLabel.replace(/\/+/g, '/');
				}
				var theValue = 0;
				var theNonInteract = true;
				var theTarget = '';
				var theDelay = 0.0;
				var followLink = true;
				var theHref = theAnchors[i].getAttribute('href');
				// once an event is tracked in a given way this will be set to true additional checks are not made to save time
				var eventTracked = false;
				var addTracking = false;
				if (theHref != '' && theHref != null) {
					theHref = stripThisDomain(theHref);
					// now we have the location without being a fully qualified url to this site
					// check for external/email/document links
					
					// check for special links here, for example a link to twitter
					// this would change the categories/actions/labels/etc to those of the special links
					//***********************************************************************************************
					
					// check for protocol in link
					// means external link
					if (!eventTracked && isExteranlLink(theHref)) {
						// this is an external link
						if (isLinkedDomain(theHref)) {
							followLink = false;
						}
						eventTracked = true;
						// see if we should track external events
						if (opt['external']['enable']) {
							addTracking = true;
							// set values for event tracking
							theMethod = opt['external']['method'];
							theCategory = opt['external']['category'];
							theAction = theHref;
							theNonInteract = opt['external']['non_interact'];
							// added feature, add a target="_blank" to all outbound links
							theDelay = opt['external']['delay'];
							if (opt['external']['new_window']) {
								theTarget = '_blank';
							}
						} // end if external links tracking enabled
					} // end if external
					
					// mailto link
					if (!eventTracked && isMailtoLink(theHref)) {
						// mailto link
						eventTracked = true;
						// see if we are tracking email links
						if (opt['mailto']['enable']) {
							addTracking = true;
							// set values for event tracking
							theMethod = opt['mailto']['method'];
							theCategory = opt['mailto']['category'];
							//theAction = opt['mailto']['action'];
							theAction = theHref.replace(/^mailto:/, '');
							theNonInteract = opt['mailto']['non_interact'];
							// added feature, add a target="_blank" to all outbound links
							theDelay = opt['mailto']['delay'];
							if (opt['mailto']['new_window']) {
								theTarget = '_blank';
							}
						} // end if email links tracked
					} // end if mailto
					
					if (!eventTracked && isPhoneLink(theHref)) {
						// click to call link
						if (opt['click_to_call']['enable']) {
							addTracking = true;
							// set values for event tracking
							theMethod = opt['click_to_call']['method'];
							theCategory = opt['click_to_call']['category'];
							//theAction = opt['mailto']['action'];
							theAction = theHref.replace(/^tel:/, '');
							theNonInteract = opt['click_to_call']['non_interact'];
							// added feature, add a target="_blank" to all outbound links
							theDelay = opt['click_to_call']['delay'];
							if (opt['click_to_call']['new_window']) {
								theTarget = '_blank';
							}
						} // end if phone lings enabled
					} // end if phone link
					
					// download link 
					if (!eventTracked && isDocumentLink(theHref)) {
						// this is a trackable extension for download
						// download link
						eventTracked = true;
						// see if we are tracking document links
						if (opt['document']['enable']) {
							addTracking = true;
							// set values for event tracking
							theMethod = opt['document']['method'];
							theCategory = opt['document']['category'];
							theAction = theHref;
							theNonInteract = opt['document']['non_interact'];
							// added feature, add a target="_blank" to all outbound links
							theDelay = opt['document']['delay'];
							if (opt['document']['new_window']) {
								theTarget = '_blank';
							}
						} // end if tracking document links
					} // end if download
					
					if (!eventTracked && isReferenceLink(theHref)) {
						// a click to an anchor on the same page
						eventTracked = true;
						var theAnchor = getAnchorName(theHref);
						if (opt['anchors']['enable']) {
							addTracking = true;
							theMethod = opt['anchors']['method'];
							theCategory = opt['anchors']['category'];
							theAction = theAnchor;
							theNonInteract = opt['anchors']['non_interact'];
							// added feature, add a target="_blank" to all outbound links
							theDelay = opt['anchors']['delay'];
							if (opt['anchors']['new_window']) {
								theTarget = '_blank';
							}
						}
					} // end if isReferenceLink
					
					// need to allow some way for developers to add tracking events to any element
					// or to links that go to new pages but have javascript to load something
					// using ajax or other things of this nature
					
					
					// add event to link
					if (addTracking) {
						// add event tracking function to anchor
						theAnchors[i].setAttribute('data-ga_method', theMethod);
						theAnchors[i].setAttribute('data-ga_category', theCategory);
						theAnchors[i].setAttribute('data-ga_action', theAction);
						theAnchors[i].setAttribute('data-ga_label', theLabel);
						theAnchors[i].setAttribute('data-ga_value', theValue);
						theAnchors[i].setAttribute('data-ga_nonInteract',theNonInteract);
						theAnchors[i].setAttribute('data-ga_delay', theDelay);
						theAnchors[i].setAttribute('data-ga_link', followLink);
						var currentTarget = theAnchors[i].getAttribute('target');
						if (theTarget != '' && (currentTarget == '' || currentTarget == null)) {
							theAnchors[i].setAttribute('target', theTarget);
						}
						if (window.addEventListener) {
							// most browsers
							// made all events prevent default in version 3.2.0
							//if (followLink) {
							//	theAnchors[i].addEventListener('click', function() {bluntGA_trackEvent(this);}, false);
							//} else {
								theAnchors[i].addEventListener('click', function(e) {bluntGA_trackEvent(this);e.preventDefault();}, false);
							//}
						} else if (window.attachEvent) {
							// windows/IE
							//if (followLink) {
							//	theAnchors[i].attachEvent('onclick', function() {bluntGA_trackEvent(this);});
							//} else {
								theAnchors[i].attachEvent('onclick', function(e) {bluntGA_trackEvent(this);e.preventDefault();});
							//}
						} // end if else
					} // end if addTracking
					
				} // end check for href is defined
			} // end for i = length of anchors
			
		} // end function addAnchorClickEvents
		
		function addFormSubmitEvents() {
			var formCount = document.forms.length;
			if (formCount > 0) {
				var theMethod = opt['form']['method'];
				var theCategory = opt['form']['category'];
				var theValue = 0;
				var theDelay = opt['form']['delay'];
				var theLink = false;
				for (var i=0; i<formCount; i++) {
					var theAction = '';
					var theNonInteract = opt['form']['non_interact'];
					var theFormName = '';
					var theFormFrom = '';
					var theFormTo = '';
					var theLabel = '';
					// to track we need the form name and the place it is submitted to
					// label will be the form name, page it is on and page submitted to
					if (document.forms[i].getAttribute('name')) {
						var theFormName = document.forms[i].getAttribute('name');
					} else if (document.forms[i].getAttribute('id')) {
						var theFormName = document.forms[i].getAttribute('id');
					}
					if (theFormName == '' || theFormName == null) {
						theFormName = 'Form_Number_'+i;
						document.forms[i].setAttribute('name', theFormName);
					}
					var theFormFrom = document.location.pathname.replace(/#.*$/, '');
					if (document.forms[i].getAttribute('action')) {
						var theFormTo = document.forms[i].getAttribute('action');
						theFormTo = theFormTo.replace(/#.*$/, '');
					}
					if (theFormTo != '' && theFormTo != null) {
						theFormTo = stripThisDomain(theFormTo);
						if (isExteranlLink(theFormTo)) {
							theNonInteract = true;
						}
					}
					if (theFormFrom == theFormTo || theFormTo == '') {
						theFormTo = 'self';
					}
					//var theAction = theFormName;
					// version 1.3.0 changed form action to the url of the form action
					var theAction = theFormTo;
					//var theLabel = 'From: '+theFormFrom+'; To: '+theFormTo;
					// version 1.3.0 changed the form label to the same label the current page url
					var theLabel = theFormFrom;
					if (opt['cross_site']['enable']) {
						theLabel = '/'+opt['cross_site']['prefix']+'/'+theLabel+'/'+opt['cross_site']['sufix']+'/';
						theLabel = theLabel.replace(/\/+/g, '/');
					}
					document.forms[i].setAttribute('data-ga_method', theMethod);
					document.forms[i].setAttribute('data-ga_category', theCategory);
					document.forms[i].setAttribute('data-ga_action', theAction);
					document.forms[i].setAttribute('data-ga_label', theLabel);
					document.forms[i].setAttribute('data-ga_value', theValue);
					document.forms[i].setAttribute('data-ga_nonInteract', theNonInteract);
					document.forms[i].setAttribute('data-ga_delay', theDelay);
					document.forms[i].setAttribute('data-ga_link', true);
					if (window.addEventListener) {
						// most browsers
						//document.forms[i].addEventListener('submit', function(){bluntGA_trackEvent(this);}, false);
						document.forms[i].addEventListener('submit', function(e) {bluntGA_trackEvent(this);e.preventDefault();}, false);
					} else if (window.attachEvent) {
						// windows/IE
						document.forms[i].attachEvent('onsubmit', function(e){bluntGA_trackEvent(this);e.preventDefault();});
					}
				} // end for each form
			} // end if count forms > 0
		} // end function addFormSubmitEvents
			
		function stripThisDomain(theUrl) {
			// see if the link is a fully qualified URL back to this domain
			// need to check both secure and non-secure versions
			// if it is, strip off the location host from the link
			theUrl = String(theUrl);
			if (theUrl.indexOf(theLoc) == 0) {
				theUrl = theUrl.substring(theLocLen);
			}
			if (theUrl.indexOf(theSecLoc) == 0) {
				theUrl = theUrl.substring(theSecLocLen);
			}
			return theUrl;
		} // end function stripThisDomain
		
		function isDocumentLink(theUrl) {
			var theReturn = false;
			var theExtension = theUrl.split('.').pop();
			if (opt['document']['extensions'].indexOf(theExtension) != -1) {
				theReturn = true;
			}
			return theReturn;
		} // end function isDocumentLink
		
		function isPhoneLink(theUrl) {
			var theReturn = false;
			if (theUrl.indexOf('tel:') == 0) {
				theReturn = true;
			}
			return theReturn;
		}
		
		function isMailtoLink(theUrl) {
			var theReturn = false;
			if (theUrl.indexOf('mailto:') == 0) {
				theReturn = true;
			}
			return theReturn;
		} // end function isMailtoLink
		
		function isExteranlLink(theUrl) {
			var theReturn = false;
			if (theUrl.indexOf(':/'+'/') != -1) {
				theReturn = true;
			}
			return theReturn;
		} // end function isExteranlLink
		
		function isLinkedDomain(theURL) {
			// see if domeain is in list of linked domains
			var theReturn = false;
			var theDomain = theURL.replace(/^[^\:]*:\/\/([^\/]*).*$/, '$1');
			theDomain = theDomain.toLowerCase();
			if (opt['cross_link']['enable'] && 
					opt['cross_link']['domains'].indexOf(theDomain) != -1) {
				theReturn = true;
			}
			return theReturn;
		} // end function isLinkedDomain
		
		function isReferenceLink(theUrl) {
			var theReturn = false;
			if (theUrl.indexOf('#') != -1) {
				// there is an anchor ref in link, see if it is the same page
				var theLocation = document.location.pathname.replace(/^(.*)#.+$/, '$1');
				theUrl = theUrl.replace(/^(.*)#.+$/, '$1');
				if (theUrl == '' || theUrl == theLocation) {
					theReturn = true;
				}
			}
			return theReturn;
		} // end function isReferenceLink
		
		function getAnchorName(theUrl) {
			return theUrl.replace(/^.*(#.+)$/, '$1');
		} // end function getAnchorName
		
		function trackConversion() {
			// if the current page is in the list of conversion page
			// track the conversion event
			var theLocation = document.location.pathname;
			// remove all query strings values in case this was a get form submission
			theLocation = theLocation.replace(/\?.*$/, '');
			var theHash = document.location.hash;
			if ((opt['conversions']['pages'].indexOf(theLocation) != -1) || 
					(opt['anchors']['enable'] && (opt['conversions']['pages'].indexOf(theHash) != -1))) {
				if (opt['anchors']['enable']) {
					theLocation += theHash;
				}
				var theReferer = document.referrer;
				if (theReferer != '') {
					theReferer = stripThisDomain(theReferer);
					theReferer = theReferer.replace(/\?.*$/, '');
				}
				var theMethod = opt['conversions']['method'];
				var theCategory = opt['conversions']['category'];
				/* altered in 2.0.0 from: var theAction = 'conversion'; */
				var theAction = '';
				/* altered in 2.0.0 from: var theLabel = 'Page='+theLocation+'&From='+theReferer; */
				var theLabel = theReferer;
				if (opt['cross_site']['enable']) {
					theLocation = '/'+opt['cross_site']['prefix']+'/'+theLocation+'/'+opt['cross_site']['sufix']+'/';
					theLocation = theLocation.replace(/\/+/g, '/');
					if (theLabel != '') {
						theLabel = '/'+opt['cross_site']['prefix']+'/'+theLabel+'/'+opt['cross_site']['sufix']+'/';
						theLabel = theLabel.replace(/\/+/g, '/');
					}
				}
				var theValue = 0;
				var theNonInteract = opt['conversions']['non_interact'];
				theAction = theLocation;
				if (theMethod == 'event') {
					if (!bluntGA.debug) {
						_gaq.push(['_trackEvent', theCategory, theAction, theLabel, theValue, theNonInteract]);
					} else {
						alert('_gaq.push([\'_trackEvent\', '+theCategory+', '+theAction+', '+theLabel+', '+theValue+', '+theNonInteract+']);')
					}
				} else {
					// track as virtual page view instead of event
					//theLabel = theLabel.replace('/'+opt['cross_site']['prefix'], '');
					//theLabel = theLabel.replace(opt['cross_site']['sufix']+'/', '');
					var virtualPage = '/'+theCategory+'/'+'?action='+theAction+'&label='+theLabel;
					if (opt['cross_site']['enable']) {
						//virtualPage = '/'+opt['cross_site']['prefix']+'/'+virtualPage+'/'+opt['cross_site']['sufix'];
					}
					//virtualPage = virtualPage.replace(/\/+/g, '/');
					if (!bluntGA.debug) {
						_gaq.push(['_trackPageview', virtualPage]);
					} else {
						alert('_gaq.push([\'_trackPageview\', '+virtualPage+']);');
					}
				}
			} // end is conversion page
			
		} // end function trackConversion
		
	} // end function (class) bluntGA_addEvents
	// end main class
	// *********************************************************************
	
	// *********************************************************************
	// delay, called if a particular type of click needs a delay
	// to allow ping success
	/* This function removed in version 3.2.0, switched to using setTimeout to create correct delay
				This type of delay stopped working, I know it was working, I saw it working
	function bluntGA_eventPause(delay) {
		// ga event tracking will not complete in some cases unless there is a slight pause
		// between the event being sent to google and the document location changing
		var waitUntil = (delay*1000)+new Date().getTime(); // wait until delay seconds from now
		while (new Date().getTime() < waitUntil){
			// do nothing, waiting for delay to expire	
		}
	} // end function bluntGA_eventPause
	*/
	// *********************************************************************
	
	// *********************************************************************
	// this is the actual function that is called when a link is clicked
	// attributes are collected and the event ping is sent to google
	// if _link is enabled, and it is a cross domain link, then the GA linker will be called
	function bluntGA_trackEvent(theLink) {
		// gets the attributes set at start up and sends the event tracking ping
		var opt = bluntGA.opt;
		var theNode = '';
		if (theLink.nodeName) {
			theNode = theLink.nodeName.toLowerCase();
		}
		if (!theLink.nodeName && theNode != 'a' && theNode != 'form') {
			// this must be ie since it did not sent the a element and
			// send an image element inside a link instead
			theLink = bluntGA_getLinkIE(window.event.srcElement);
			if (theLink === null) {
				// error, could not find the link or form element
				// give up, this link will not be tracked (very rare)
				return;
			}
		}
		var category = theLink.getAttribute('data-ga_category');
		if (category) {
			var formAction = false;
			var formName = '';
			var theMethod = theLink.getAttribute('data-ga_method');
			var action = theLink.getAttribute('data-ga_action');
			var label = theLink.getAttribute('data-ga_label');
			var value = parseInt(theLink.getAttribute('data-ga_value'));
			var nonInteract = theLink.getAttribute('data-ga_nonInteract');
			var delay = theLink.getAttribute('data-ga_delay');
			var theHref = theLink.getAttribute('href');
			if (theHref == null) {
				// this is a form
				formAction = true;
				formName = theLink.getAttribute('name');
			}
			var followLink = theLink.getAttribute('data-ga_link');
			if (followLink !== true && followLink !== false) {
				if (followLink == 'true') {
					followLink = true;
				} else {
					followLink = false;
				}
			}
			if (nonInteract === 'true') {
				nonInteract = true;
			} else if (nonInteract === 'false') {
				nonInteract = false;
			}
			var target = theLink.getAttribute('target');
			if (target == null || target == '') {
				target = '';
			}
			//alert(followLink);
			if (followLink || opt['cross_link']['event']) {
				if (theMethod == 'event') {
					if(!bluntGA.debug) {
						//alert('here1');
						//alert(category+':'+action+':'+label+':'+value+':'+nonInteract);
						_gaq.push(['_trackEvent', category, action, label, value, nonInteract]);
					} else {
						alert('_gaq.push([\'_trackEvent\', '+category+', '+action+', '+label+', '+value+', '+nonInteract+'])');
					}
				} else {
					// track as virtual page view instead of event
					var virtualPage = '/'+category+'/'+'?action='+action+'&label='+label+'/';
					if (opt['cross_site']['enable']) {
						virtualPage = '/'+opt['cross_site']['prefix']+'/'+virtualPage+'/'+opt['cross_site']['sufix'];
					}
					virtualPage = virtualPage.replace(/\/+/g, '/');
					if (!bluntGA.debug) {
						_gaq.push(['_trackPageview', virtualPage]);
					} else {
						alert('_gaq.push([\'_trackPageview\', '+virtualPage+'])');
					}
				}
				// removed this delay method in 3.2.0
				//bluntGA_eventPause(delay);
			}
			if (!followLink) {
				// cross domain link
				if (!bluntGA.debug) {
					if (target != '') {
						// add delay and open in new window
						pageTracker = _gat._getTracker(opt['ga_account']);
						var linkerUrl = pageTracker._getLinkerUrl(theHref);
						setTimeout('var win = window.open(\''+linkerUrl+'\',\''+target+'\'); win.focus();', delay*1000);
					} else {
						// doesn't need delay because we are using _gaq.push
						_gaq.push(['_link', theHref]);
					}
				} else {
					alert('_gaq.push([\'_link\', '+theHref+']);');
				}
			} else {
				if (!formAction) {
					if (target != '') {
						// add delay and open in new window
						setTimeout('var win = window.open(\''+theHref+'\',\''+target+'\'); win.focus();', delay*1000);
					} else {
						// add delay and open in this window
						setTimeout('document.location = \''+theHref+'\'', delay*1000); 
					}
				} else {
					// submit form
					// not sure I can add a delay here
					// will need to work this out
					// appears I don't need to do anything
					//alert('form submit');
					//alert(delay);
					// submit form with delay
					//document.forms[formName].submit();
					//alert('1: '+formName);
					setTimeout('bluntGA_submitForm('+formName+');', delay*1000);
				}
			}
		} // end if there is a ga category set
	} // end function blunt_ga_track_event
	// *********************************************************************
	
	function bluntGA_submitForm(formName) {
		//alert(typeof(formName));
		if (typeof(formName) == 'object') {
			formName = formName.getAttribute('name');
		}
		document.forms[formName].submit();
	} // end function bluntGA_submitForm
	
	// *********************************************************************
	// This function is to fix the fact that IE does not pass "this" correctly
	// to the onclick events added to elements when there is an image and some other
	// element types inside the a element
	// and this function gets the real element that the event was attached to
	function bluntGA_getLinkIE(e) {
		var theNode = e.nodeName.toLowerCase();
		while (theNode != 'a' && theNode != 'form' && theNode != 'body') {
			e = e.parentNode;
			theNode = e.nodeName.toLowerCase();
		}
		if (theNode == 'body') {
			e = null;
		}
		return e;
	} // end function bluntGA_getLinkIE
	// *********************************************************************
	