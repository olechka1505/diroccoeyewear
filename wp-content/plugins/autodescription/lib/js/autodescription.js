/**
 * This file holds The SEO Framework plugin's JS code.
 *
 * @author Sybre Waaijer https://cyberwire.nl/
 * @pluginURI https://wordpress.org/plugins/autodescription/
 *
 * @credits StudioPress (http://www.studiopress.com/) for some code.
 */

/**
 * The SEO Framework plugin
 * Copyright (C) 2015 - 2016 Sybre Waaijer, CyberWire (https://cyberwire.nl/)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 3 as published
 * by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

// ==ClosureCompiler==
// @compilation_level SIMPLE_OPTIMIZATIONS
// @output_file_name autodescription.min.js
// @externs_url https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.js
// ==/ClosureCompiler==
// http://closure-compiler.appspot.com/home

/* global autodescription, autodescriptionL10n, docTitles, confirm, escapeStr */

/* Advanced Optimizations don't work well with function tabToggle, nor with the title character length */

/**
 * Holds AutoDescription values in an object to avoid polluting global namespace.
 *
 * @since 2.2.4
 *
 * @constructor
 */
window[ 'autodescription' ] = {

	settingsChanged: false,

	titleTagline: autodescriptionL10n.titleTagline,
	titleAdditions: autodescriptionL10n.titleAdditions,
	titleLocation: autodescriptionL10n.titleLocation,
	blogDescription: autodescriptionL10n.blogDescription,
	siteTitle: autodescriptionL10n.siteTitle,
	titleSeparator: autodescriptionL10n.titleSeparator,
	isRTL: autodescriptionL10n.isRTL,
	isHome: autodescriptionL10n.isHome,
	saveAlert: autodescriptionL10n.saveAlert,
	confirmReset: autodescriptionL10n.confirmReset,

	/**
	 * Cached doctitle function.
	 *
	 * @since 2.3.3
	 *
	 * @function
	 *
	 * @returns {Object} The jQuery doctitle ID's
	 */
	docTitles: function() {
		'use strict';

		var $doctitles = jQuery( '#autodescription_title, #autodescription-meta\\[doctitle\\], #autodescription-site-settings\\[homepage_title\\]' );

		return $doctitles;
	},

	/**
	 * Cached description function.
	 *
	 * @since 2.5.0
	 *
	 * @function
	 *
	 * @returns {Object} The jQuery description ID's
	 */
	docDescriptions: function() {
		'use strict';

		var $descriptions = jQuery( "#autodescription_description, #autodescription-meta\\[description\\], #autodescription-site-settings\\[homepage_description\\]" );

		return $descriptions;
	},

	/**
	 * Helper function for confirming a user action.
	 *
	 * @since 2.2.4
	 *
	 * @function
	 *
	 * @param {String} text The text to display.
	 * @returns {Boolean}
	 */
	confirm: function( text ) {
		'use strict';

		return confirm( text );
	},

	/**
	 * Description length counter.
	 *
	 * @since 2.2.4
	 *
	 * @function
	 *
	 * @return string The counter information
	 */
	updateCharacterCountDescription: function( event ) {
		'use strict';

		var $this = jQuery( event.target ),
			$length = $this.val().length,
			$phLength = $this.attr( 'placeholder' ).length,
			$counter = jQuery( '#' + autodescription.escapeStr( event.target.id ) + '_chars' ),
			$counterClass = '',
			$output = '';

		// Emptied input, get Description placeholder.
		if ( $length === 0 ) {
			//* Output length from placeholder.
			$length = $phLength;
		}

		$output = $length.toString();

		if ( $length < 100 || $length >= 175 ) {
			$counterClass = 'ad-count-bad';
		} else if ( $length < 145 || ( $length > 155 && $length < 175 ) ) {
			$counterClass = 'ad-count-okay';
		} else {
			$counterClass = 'ad-count-good';
		}

		$counter.html( $output ).removeClass().addClass( $counterClass );
	},

	/**
	 * Title length counter, with special characters
	 *
	 * @since 2.2.4
	 *
	 * @function
	 *
	 * @return string The counter information
	 */
	updateCharacterCountTitle: function( event ) {
		'use strict';

		var $this = jQuery( event.target ),
			$additions = autodescription.titleAdditions.length,
			$description = autodescription.blogDescription.length,
			$siteTitle = autodescription.siteTitle.length,
			$titleLength = $this.val().length,
			$placeholder = $this.attr('placeholder').length,
			$tagline = jQuery( '#autodescription-site-settings\\[homepage_title_tagline\\]' ).val(),
			$seplen = 3,
			$counter = jQuery( '#' + autodescription.escapeStr( event.target.id ) + '_chars' ),
			$length = 0,
			$counterClass = '';

		// Tagline removed, remove additions and separator.
		if ( ! autodescription.titleTagline ) {
			$additions = 0;
			$seplen = 0;
		}

		// Emptied input, get Site title.
		if ( $titleLength === 0 ) {
			if ( $siteTitle !== 0 ) {
				$titleLength = $siteTitle;
			} else {
				//* Output length from placeholder.
				$length = $placeholder;
			}
		}

		// Length should be something now.
		if ( $titleLength !== 0 ) {

			if ( $additions !== 0 && typeof $tagline !== 'undefined' ) {
				var $tagLength = $tagline.length;

				// Replace $additions with $tagline is $tagline isn't empty.
				if ( $tagLength !== 0 ) {
					$additions = $tagLength;
				} else {
					$additions = $description;
				}
			}

			// Put it all together
			if ( $additions === 0 ) {
				$length = $titleLength;
			} else {
				$length = $titleLength + $seplen + $additions;
			}
		}

		if ( $length < 25 || $length >= 75 ) {
			$counterClass = 'ad-count-bad';
		} else if ( $length < 42 || ( $length > 55 && $length < 75 ) ) {
			$counterClass = 'ad-count-okay';
		} else {
			$counterClass = 'ad-count-good';
		}

		$counter.html( $length.toString() ).removeClass().addClass( $counterClass );
	},

	/**
	 * Escapes HTML strings
	 *
	 * @since 2.2.4
	 *
	 * @function
	 *
	 * @return {String} HTML to jQuery converted string
	 */
	escapeStr: function( str ) {
		'use strict';

		if ( str )
			return str.replace(/([\[\]\/])/g,'\\$1');

		return str;
	},

	/**
	 * Dynamic Title separator replacement in metabox
	 *
	 * @since 2.2.2
	 *
	 * @function
	 *
	 * @return {Boolean} separator has Changed
	 */
	separatorSwitch: function( event ) {
		'use strict';

		var $sep = jQuery( ".autodescription-sep-js" ),
			$val = jQuery( event.target ).val();

		if ( $val == 'pipe' ) {
			$sep.text( " | " );
		} else if ( $val == 'dash' ) {
			$sep.text( " - " );
		} else {
			$sep.html( " &" + $val + "; " );
		}
	},

	/**
	 * Dynamic Description separator replacement in metabox
	 *
	 * @since 2.3.4
	 *
	 * @function
	 */
	separatorSwitchDesc: function( event ) {
		'use strict';

		var $sep = jQuery( ".autodescription-descsep-js" ),
			$val = jQuery( event.target ).val();

		if ( $val == 'pipe' ) {
			$sep.text(" | ");
		} else if ( $val == 'dash' ) {
			$sep.text(" - ");
		} else {
			$sep.html(" &" + $val + "; ");
		}
	},

	/**
	 * Status bar description output on hover
	 *
	 * @since 2.1.9
	 *
	 * @function
	 *
	 * @return {String} The information balloon
	 */
	statusBarHover: function() {
		'use strict';

		var $wrap = jQuery( '.ad-bar-wrap' ),
			$wrapA = jQuery( '.ad-bar-wrap a' );

		$wrapA.mouseenter( function() {
			var $this = jQuery( this ),
				$thisDesc = $this.attr( 'data-desc' );

			if ( ( $thisDesc !== undefined ) && ( 0 === $this.find( 'div' ).length ) ) {
				$this.append( '<div class="explanation-desc">' + $thisDesc + '<div></div></div>' );

				var $thisHeight = $this.find( 'div.explanation-desc' ).height() + 36;

				$this.find( 'div.explanation-desc' ).css( 'top', ( $this.position().top - $thisHeight ) + 'px' );
			}
		}).mousemove( function( event ) {
			var $mousex = event.pageX - $wrap.offset().left - 10, // 20px width of arrow / 2 = 10 middle
				$arrow = jQuery( 'span.ad-seo .explanation-desc div' ),
				$left = jQuery( 'div.explanation-desc' ).offset().left,
				$width = jQuery( 'div.explanation-desc' ).width(),
				$maxOffset = $left + $width + 20;

			if ( $mousex < 1 ) {
				$arrow.css( 'left', 0 + "px" );
			} else if ( event.pageX > $maxOffset ) {
				$arrow.css( 'left', $width + 10 + "px" );
			} else {
				$arrow.css( 'left', $mousex + "px" );
			}
		}).mouseleave( function() {
			jQuery( this ).find( 'div.explanation-desc' ).remove();
		});

	},

	/**
	 * Remove Status bar desc if clicked outside (touch support)
	 *
	 * @since 2.1.9
	 *
	 * @function
	 */
	removeDesc: function( event ) {
		'use strict';

		var $this = jQuery( event.target ),
			$desc = jQuery('.ad-bar-wrap a');

		if ( ! $this.closest( $desc ).length )
			$desc.find( 'div.explanation-desc' ).remove();
	},

	/**
	 * Refines Styling for the navigation tabs on the settings pages
	 *
	 * @since 2.2.2
	 *
	 * Rewritten
	 * @since 2.2.4
	 *
	 * @function
	 */
	tabToggle: function( event ) {
		'use strict';

		jQuery( 'div.autodescription-metaboxes .nav-tab-wrapper :input' ).each( function() {
			var $this = jQuery( this ),
				$target	= jQuery( event.target ).attr('id');

			// The second 'this' should be invalid? But it works D:
			$this.next().toggleClass( 'nav-tab-active', this.checked );

			if ( typeof $target !== 'undefined' && $target.indexOf( "-tabs-js" ) === -1 ) {
				var $id = $target;

				// Toggle content for the tabs in SEO settings page with the desired ID.
				autodescription.tabContent( $id );
			}

		});
	},

	/**
	 * Sets page output based on clicked input
	 *
	 * @since 2.2.2
	 *
	 * Rewritten
	 * @since 2.2.4
	 *
	 * @function
	 */
	tabContent: function( id ) {
		'use strict';

		if ( typeof id != 'undefined' ) {
			var $tab_id = id,
				$slice = $tab_id.slice( 0, $tab_id.indexOf('-tab') ),
				$hide = jQuery( '.' + $slice + '-tab-content' ),
				$show = jQuery( "#" + $tab_id + '-box');

			$hide.css( 'display', 'none' );
			$show.css( 'display', 'block' );
		}
	},

	/**
	 * Toggle tagline within the Left/Right example for the HomePage Title
	 *
	 * @since 2.2.4
	 *
	 * @function
	 *
	 * @return {Boolean} Title tagline removal
	 */
	taglineToggle: function( event ) {
		'use strict';

		var $this = jQuery( event.target ),
			$tag = jQuery( '.custom-blogname-js' );

		if ( $this.is(':checked') ) {
			$tag.css( 'display', 'inline' );
			autodescription.titleTagline = true;
		} else {
			$tag.css( 'display', 'none' );
			autodescription.titleTagline = false;
		}

		return autodescription.docTitles().trigger( 'keyup', autodescription.updateCharacterCountTitle );
	},

	/**
	 * Toggle tagline within Description example for the Example Description
	 *
	 * @since 2.3.4
	 *
	 * @function
	 */
	taglineToggleDesc: function( event ) {
		'use strict';

		var $this = jQuery( event.target ),
			$tagDesc = jQuery( '.on-blogname-js' );

		if ( $this.is(':checked') ) {
			$tagDesc.css( 'display', 'inline' );
		} else {
			$tagDesc.css( 'display', 'none' );
		}
	},

	/**
	 * Toggle tagline within the Left/Right example for the HomePage Title or Description
	 *
	 * @since 2.2.7
	 *
	 * @function
	 */
	taglineToggleOnload: function( event ) {
		'use strict';

		var $tagbox = jQuery( '#title-tagline-toggle :input' ),
			$tag = jQuery( '.custom-blogname-js' ),
			$tagboxDesc = jQuery( '#description-onblogname-toggle :input' ),
			$tagDesc = jQuery( '.on-blogname-js' ),
			$title = jQuery( '#title-additions-toggle :input' ),
			$titleExample = jQuery( '.title-additions-js' );

		if ( $tagbox.is( ':checked' ) ) {
			$tag.css( 'display', 'inline' );
		} else {
			$tag.css( 'display', 'none' );
		}

		if ( $tagboxDesc.is( ':checked' ) ) {
			$tagDesc.css( 'display', 'inline' );
		} else {
			$tagDesc.css( 'display', 'none' );
		}

		// Reverse option.
		if ( $title.is( ':checked' ) ) {
			$titleExample.css( 'display', 'none' );
		} else {
			$titleExample.css( 'display', 'inline' );
		}
	},

	/**
	 * Change Title based on input of the Custom Title
	 *
	 * @since 2.2.4
	 *
	 * @function
	 */
	titleProp: function( event ) {
		'use strict';

		var $val = jQuery( event.target ).val(),
			$title = jQuery( '.custom-title-js' );

		if ( $val.length === 0 ) {
			$title.text( autodescription.siteTitle );
		} else {
			$title.text( $val );
		}
	},

	/**
	 * Change Title based on input of the Custom Title
	 *
	 * @since 2.3.8
	 *
	 * @function
	 */
	taglineProp: function( event ) {
		'use strict';

		var $val = jQuery( event.target ).val(),
			$floatTag = jQuery( '.custom-tagline-js' ),
			$target = jQuery( '#autodescription-site-settings\\[homepage_title\\]' ),
			$leftRight = jQuery( '#home-title-location input:checked' ).val(),
			$toggle = jQuery( '#autodescription-site-settings\\[homepage_tagline\\]' ),
			$placeholder = autodescriptionL10n.siteTitle,
			$description = autodescription.blogDescription,
			$sep = jQuery( '#title-separator input:checked' ).val(),
			$sepOutput = autodescription.titleSeparator;

		if ( $toggle.is(':checked') ) {

			if ( $val.length !== 0 ) {
				$description = $val;
			}

			if ( $sep.length !== 0 ) {
				if ( $sep == 'pipe' ) {
					$sepOutput = ( "|" );
				} else if ( $sep == 'dash' ) {
					$sepOutput = ( "-" );
				} else {
					// Create a memory div to store the html in, convert to text to append in $placeholder
					$sepOutput = jQuery('<div/>').html( "&" + $sep + ";" ).text();
				}
			}

			if ( $leftRight.length !== 0 && $leftRight == 'left' ) {
				$placeholder = autodescriptionL10n.siteTitle + ' ' + $sepOutput + ' ' + $description;
			} else {
				$placeholder = $description + ' ' + $sepOutput + ' ' + autodescriptionL10n.siteTitle;
			}
		}

		$floatTag.html( $description );
		$target.attr( "placeholder", $placeholder );

		// Notify tagline has changed.
		autodescription.docTitles().trigger( 'keyup', autodescription.updateCharacterCountTitle );
	},

	/**
	 * Trigger Change on Left/Right selection of Home Page Title
	 *
	 * @since 2.5.0
	 *
	 * @function
	 */
	taglinePropTrigger: function() {
		'use strict';

		return jQuery( "#autodescription-site-settings\\[homepage_title_tagline\\]" ).trigger( 'keyup', autodescription.taglineProp );
	},

	/**
	 * Trigger Change on Left/Right selection of Global Title
	 *
	 * @since 2.5.2
	 *
	 * @function
	 */
	titleToggle: function() {
		'use strict';

		var $this = jQuery( event.target ),
			$tagDesc = jQuery( '.title-additions-js' );

		if ( $this.is(':checked') ) {
			$tagDesc.css( 'display', 'none' );
		} else {
			$tagDesc.css( 'display', 'inline' );
		}
	},

	/**
	 * Have all form fields in Genesis metaboxes set a dirty flag when changed.
	 *
	 * @since 2.0.0
	 *
	 * @function
	 */
	attachUnsavedChangesListener: function() {
		'use strict';

		jQuery( 'div.autodescription-metaboxes :input, div#theseoframework-inpost-box .inside :input' ).not('.nav-tab-wrapper :input').change( function() {
			autodescription.registerChange();
		});

		jQuery( 'div.autodescription-metaboxes input[type=text], div.autodescription-metaboxes textarea, div#theseoframework-inpost-box .inside input[type=text], div#theseoframework-inpost-box .inside textarea' ).not('.nav-tab-wrapper :input').on( 'keyup', function() {
			autodescription.registerChange();
		});

		window.onbeforeunload = function(){
			if ( autodescription.settingsChanged ) {
				return autodescription.saveAlert;
			}
		};

		jQuery( 'div.autodescription-metaboxes input[type="submit"], div#publishing-action input[type="submit"], div#save-action input[type="submit"]' ).click( function() {
			window.onbeforeunload = null;
		});
	},

	/**
	 * Set a flag, to indicate form fields have changed.
	 *
	 * @since 2.2.4
	 *
	 * @function
	 */
	registerChange: function() {
		'use strict';

		autodescription.settingsChanged = true;
	},

	/**
	 * Ask user to confirm that settings should now be reset.
	 *
	 * @since 2.2.4
	 *
	 * @function
	 *
	 * @return {Boolean} True if reset should occur, false if not.
	 */
	confirmedReset: function() {
		'use strict';

		return confirm( autodescription.confirmReset );
	},

	/**
	 * Adds dynamic placeholder to Title input based on site settings.
	 *
	 * @since 2.5.0
	 *
	 * @function
	 *
	 * @return {String} the placeholder additions.
	 */
	dynamicPlaceholder: function( event ) {
		'use strict';

		var $hasAdditions = autodescription.titleAdditions.length,
			$placeholder = jQuery( '#autodescription-title-placeholder' );

		// If check is defined, we're on SEO settings page.
		if ( $hasAdditions !== 0 ) {

			var $after = false,
				$check = jQuery( '#home-title-location input:checked' ).val(),
				$rtl = autodescription.isRTL;

			if ( typeof $check !== 'undefined' && $check.length !== 0 ) {
				//* We're in SEO Settings page.

				if ( $rtl === '1' ) {
					if ( $check === 'right' ) {
						$after = true;
					}
				} else {
					if ( $check === 'left' ) {
						$after = true;
					}
				}
			} else {
				//* We're in post/page edit screen.

				var $isHome = autodescription.isHome,
					$titleLocation = autodescription.titleLocation,
					$tagline = autodescription.titleTagline;

				// We're on post/page screen.
				if ( $isHome === '1' ) {
					// Static Front page, switch check.
					if ( $tagline === '1' ) {
						if ( $rtl === '1' ) {
							if ( $titleLocation === 'right' ) {
								$after = true;
							}
						} else if ( $titleLocation === 'left' ) {
							$after = true;
						}
					}
				} else {
					if ( $rtl === '1' ) {
						if ( $titleLocation === 'left' ) {
							$after = true;
						}
					} else if ( $titleLocation === 'right' ) {
						$after = true;
					}
				}
			}
		} else {
			var $this = jQuery( event.target );

			// Empty the placeholder as we can't execute.
			$this.css( 'text-indent', "initial" );
			return $placeholder.empty();
		}

		var $tagbox = jQuery( '#title-tagline-toggle :input' );

		if ( typeof $tagbox !== "undefined" && $tagbox.length > 0 && ! $tagbox.is( ':checked' ) ) {
			//* We're on SEO Settings Page now, and tagline has been disabled.
			var $this = jQuery( event.target );

			$this.css( 'text-indent', "initial" );
			$placeholder.css( 'display', 'none' );
		} else {

			var $this = jQuery( event.target ),
				$inputVal = $this.val(),
				$offsetTest = jQuery( "#autodescription-title-offset" ),
				$offsetWidth = 0,
				$heightPad = ( $this.outerHeight(true) - $this.height() ) / 2,
				$horPad = ( $this.outerWidth() - $this.width() ) / 2,
				$leftOffset = ( $this.outerWidth(true) - $this.width() ) / 2,
				$taglineVal = jQuery( "#autodescription-site-settings\\[homepage_title_tagline\\]" ).val(),
				$pos = 'left';

			if ( $rtl === '1' ) {
				$pos = 'right';
			}

			if ( $after ) {
				var $additions = autodescription.titleSeparator + " " + autodescription.titleAdditions;

				// Exchange the placeholder value of the custom Tagline in the HomePage Metabox
				if ( typeof $taglineVal !== "undefined" && $taglineVal.length > 0 ) {
					$additions = autodescription.titleSeparator + " " + $taglineVal;
				}

				$this.css( 'text-indent', "initial" );
			} else {
				var $additions = autodescription.titleAdditions + " " + autodescription.titleSeparator;

				// Exchange the placeholder value of the custom Tagline in the HomePage Metabox
				if ( typeof $taglineVal !== "undefined" && $taglineVal.length > 0 ) {
					$additions = $taglineVal + " " + autodescription.titleSeparator;
				}
			}

			// Width offset container, copy variables and remain hidden.
			$offsetTest.text( $inputVal );
			$offsetTest.css({
				fontFamily: $this.css( "fontFamily" ),
				fontWeight: $this.css( "fontWeight" ),
				letterSpacing: $this.css( "letterSpacing" ),
				fontSize: $this.css( "fontSize" ),
			});
			$offsetWidth = $offsetTest.width();

			var $maxWidth = $this.width() - $horPad - $offsetWidth;

			if ( $maxWidth < 0 )
				$maxWidth = 0;

			// Moving Placeholder output
			$placeholder.css({
				display: $this.css( "display" ),
				lineHeight: $this.css( "lineHeight" ),
				paddingTop: $heightPad + "px",
				paddingBottom: $heightPad + "px",
				fontFamily: $this.css( "fontFamily" ),
				fontWeight: $this.css( "fontWeight" ),
				fontSize: $this.css( "fontSize" ),
				letterSpacing: $this.css( "letterSpacing" ),
				maxWidth: $maxWidth + "px",
			});

			//* Empty or fill placeholder and offsets.
			if ( typeof $inputVal === "undefined" || $inputVal.length < 1 ) {

				if ( ! $after )
					$this.css( 'text-indent', "initial" );

				$placeholder.empty();
			} else {
				$placeholder.text( $additions );

				// Don't calculate when empty.
				if ( $this.outerWidth() > $leftOffset ) {
					if ( $after ) {
						$placeholder.css( $pos, $horPad + $leftOffset + $offsetTest.width() + "px" );
					} else {
						var $indent = $horPad + $placeholder.width();

						if ( $indent < 0 )
							$indent = 0;

						$placeholder.css( $pos, $leftOffset + "px" );
						$this.css( 'text-indent', $indent + "px" );
					}
				}
			}
		}
	},

	/**
	 * Makes user click act natural by selecting the parent Title text input.
	 *
	 * @since 2.5.0
	 *
	 * @function
	 */
	selectTitleInput: function() {
		'use strict';

		var $input = autodescription.docTitles();

		$input.focus();

		if ( $input.setSelectionRange ) {
			// Go to end times 2 if setSelectionRange exists.
			var $length = $input.val().length * 2;
			$input.setSelectionRange( $length, $length );
		} else {
			// Replace value with itself.
			$input.val( $input.val() ).focus();
		}
	},

	/**
	 * Adds dynamic placeholder to Title input based on site settings on Load.
	 *
	 * @since 2.5.0
	 *
	 * @function
	 */
	dynamicPlaceholderOnLoad: function() {
		'use strict';

		var $input = autodescription.docTitles();

		if ( typeof $input.val() !== "undefined" ) {
			if ( $input.val().length > 0 ) {
				$input.trigger( 'keyup', autodescription.dynamicPlaceholder );
			} else {
				$input.trigger( 'keyup', autodescription.updateCharacterCountTitle );
			}
		}

	},

	/**
	 * Triggers keyup on description input so the counter can colorize.
	 *
	 * @since 2.5.0
	 *
	 * @function
	 */
	triggerDescriptionOnLoad: function() {
		'use strict';

		var $input = autodescription.docDescriptions();

		$input.trigger( 'keyup', autodescription.updateCharacterCountDescription );

	},

	/**
	 * OnLoad changes can affect settings changes. This function reverts those.
	 *
	 * @since 2.5.0
	 *
	 * @function
	 */
	onLoadUnregisterChange: function() {
		'use strict';

		//* Prevent trigger of settings change
		autodescription.settingsChanged = false;
	},

	/**
	 * Initialises all aspects of the scripts.
	 *
	 * Generally ordered with stuff that inserts new elements into the DOM first,
	 * then stuff that triggers an event on existing DOM elements when ready,
	 * followed by stuff that triggers an event only on user interaction. This
	 * keeps any screen jumping from occuring later on.
	 *
	 * @since 2.2.4
	 *
	 * @function
	 */
	ready: function() {
		'use strict';

		// == Before Change listener

		// Move the page updates notices below the top-wrap.
		jQuery( 'div.updated, div.error, div.notice-warning' ).insertAfter( 'div.top-wrap' );

		// Toggle Dynamic Title Placeholder onLoad, also toggles doing it right colors.
		jQuery( document.body ).ready( autodescription.dynamicPlaceholderOnLoad );
		// Toggle Description doing it right colors
		jQuery( document.body ).ready( autodescription.triggerDescriptionOnLoad );

		// Check if the Title Tagline or Description Additions should be removed when page is loaded.
		jQuery( document.body ).ready( autodescription.taglineToggleOnload );

		// Initialize the status bar hover balloon.
		autodescription.statusBarHover();

		// Initialize status bar removal hover for touch screens.
		jQuery( document.body ).on( 'touchstart MSPointerDown', autodescription.removeDesc );

		// Initialise form field changing flag.
		autodescription.attachUnsavedChangesListener();

		// Deregister changes.
		jQuery( document.body ).ready( autodescription.onLoadUnregisterChange );

		// == After Change listener

		// Bind character counters.
		autodescription.docDescriptions().on( 'keydown keyup paste', autodescription.updateCharacterCountDescription );
		autodescription.docTitles().on( 'keydown keyup paste', autodescription.updateCharacterCountTitle );

		// Allow the title separator to be changed dynamically.
		jQuery( '#title-separator input' ).on( 'click', autodescription.separatorSwitch );
		// Allow description separator to be changed dynamically.
		jQuery( '#description-separator input' ).on( 'click', autodescription.separatorSwitchDesc );

		// Bind reset confirmation.
		jQuery( '.autodescription-js-confirm-reset' ).on( 'click.autodescription.autodescription_confirm_reset', autodescription.confirmedReset );

		// Toggle Tabs in the SEO settings page. TODO pull from CDATA - @TODO use CSS and rewrite HTML.
		jQuery( '#social-tabs-js, #robots-tabs-js, #knowledge-tabs-js, #sitemaps-tabs-js' ).on( 'click', autodescription.tabToggle );

		// Toggle Title tagline aditions removal.
		jQuery( '#title-tagline-toggle :input' ).on( 'click', autodescription.taglineToggle );

		// Toggle Description additions removal.
		jQuery( '#description-onblogname-toggle :input' ).on( 'click', autodescription.taglineToggleDesc );

		// Change Home Page Title Example prop on input changes.
		jQuery( '#autodescription-site-settings\\[homepage_title\\]' ).on( 'keydown keyup paste', autodescription.titleProp );
		jQuery( '#home-title-location :input, #title-tagline-toggle :input, #title-separator input' ).on( 'click', autodescription.taglinePropTrigger );
		jQuery( '#autodescription-site-settings\\[homepage_title_tagline\\]' ).on( 'keydown keyup paste', autodescription.taglineProp );

		// Change Global Title Example prop on input changes.
		jQuery( '#autodescription-site-settings\\[title_rem_additions\\]' ).on( 'click', autodescription.titleToggle );

		// Dynamic Placeholder, acts on keydown for a11y, although more cpu intensive. Acts on keyup for perfect output.
		autodescription.docTitles().on( 'keydown keyup paste', autodescription.dynamicPlaceholder );

		// Move click on dynamic additions to focus input behind.
		jQuery( "#autodescription-title-placeholder" ).on( 'click', autodescription.selectTitleInput );

	}

};
jQuery( autodescription.ready );
