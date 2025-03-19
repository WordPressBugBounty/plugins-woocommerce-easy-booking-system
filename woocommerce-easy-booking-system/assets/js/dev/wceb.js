"use strict";

( function( $, window ) {

	window.EasyBooking = window.EasyBooking || {};

	EasyBooking.calcMode      = EASYBOOKING.calc_mode; // Days or Nights
	EasyBooking.maxOption     = new Date( EASYBOOKING.last_date + 'T00:00:00' ); // December 31st of max year
	EasyBooking.firstWeekday  = EASYBOOKING.first_weekday !== '0' ? 'monday' : 'sunday'; // Sunday or Monday
	EasyBooking.allowDisabled = EASYBOOKING.allow_disabled; // Allow disabled dates inside booking period
	EasyBooking.ajaxUrl       = location.protocol === 'https:' ? 'https:' : 'http:' + EASYBOOKING.ajax_url; // Fix to force http/https for ajax requests.
	
	EasyBooking.Helper = {

		/**
		* Format price with currency symbol, decimal and thousand separators
		* @param {number} price
		* @return {string}
		**/
		formatPrice: function( price ) {

			return accounting.formatMoney( price, {
				symbol 		: EASYBOOKING.currency_format_symbol,
				decimal 	: EASYBOOKING.currency_format_decimal_sep,
				thousand	: EASYBOOKING.currency_format_thousand_sep,
				precision 	: EASYBOOKING.currency_format_num_decimals,
				format		: EASYBOOKING.currency_format
			} );

		}

	}
	
	EasyBooking.DateHelper = {

		/**
		* Check if date is a Date object
		* @param {mixed} date
		* @return {boolean}
		**/
		isDate: function( date ) {
			return ( date instanceof Date );
		},

		/**
		* Check if date is a weekday (e.g. 1, 2, 3, 4, 5, 6, 7)
		* @param {mixed} date
		* @return {boolean}
		**/
		isDay: function( date ) {
			return ( ! isNaN( date ) && ( date >= 1 && date <= 7 ) );
		},

		/**
		* Check if date is an array (e.g. [2025,1,1])
		* @param {mixed} date
		* @return {boolean}
		**/
		isArray: function( date ) {
			return ( date instanceof Array );
		},

		/**
		* Check if date is an object (e.g. {from: [2025,1,1], to: [2025,1,1]})
		* @param {mixed} date
		* @return {boolean}
		**/
		isObject: function( date ) {
			return ( ( typeof date === 'object' ) && ! ( date instanceof Date ) );
		},

		/**
		* Create date object for pickadate.js
		* @param {mixed} date
		* @return {object}
		**/
		createDateObject: function( date ) {

			let dateObject = {};

			// If no date, get current date
			if ( ! date ) {
				date = new Date();
			}

			// Create infinity object
			if ( date === 'infinity' ) {

				dateObject = {
					date : Infinity,
					day  : Infinity,
					month: Infinity,
					obj  : Infinity,
					pick : Infinity,
					year : Infinity
				}

				return dateObject;

			}

			// Check if is valid date
			if ( ! EasyBooking.DateHelper.isDate( date ) ) {
				return dateObject;
			}

			// Set date to 00:00
			date.setHours( 0,0,0,0 );

			// Create date object
			dateObject = {
				date : date.getDate(),
				day  : date.getDay(),
				month: date.getMonth(),
				obj  : date,
				pick : date.getTime(),
				year : date.getFullYear()
			}

			return dateObject;

		},

		/**
		* Check if date is disabled
		* @param {array} disabled array of disabled dates from current datepicker
		* @param {date} dateToCheck 
		* @return {boolean}
		**/
		isDisabled: function( disabled, dateToCheck ) {

			if ( typeof disabled === 'undefined' || ! EasyBooking.DateHelper.isDate( dateToCheck ) ) {
				return false;
			}

			// Set date to 00:00
			dateToCheck.setHours( 0,0,0,0 );

			const timeToCheck = dateToCheck.getTime();
			const dayToCheck  = dateToCheck.getDay();

			return disabled.some( function( dateObject ) {

				// [year, month, date, type]
				if ( EasyBooking.DateHelper.isArray( dateObject ) ) {

					dateObject = new Date( dateObject[0], dateObject[1], dateObject[2] );

					if ( timeToCheck === dateObject.getTime() ) {
						return true;
					}

				// { from: [year, month, date], to: [year, month, date], type: type }
				} else if ( EasyBooking.DateHelper.isObject( dateObject ) ) {

					let start = new Date( dateObject['from'][0], dateObject['from'][1], dateObject['from'][2] );
					let end   = new Date( dateObject['to'][0], dateObject['to'][1], dateObject['to'][2] );
					
					if ( timeToCheck >= start && dateToCheck <= end ) {
						return true;
					}

				// 1, 2, 3, 4, 5, 6, 7
				} else if ( EasyBooking.DateHelper.isDay( dateObject ) ) {
					
					let day = dayToCheck;
					
					// If first weekday is Sunday, add 1 day (because date object day starts at 0 and JS calendar start at 1)
					if ( EasyBooking.firstWeekday === 'monday' && day === 0 ) {
						day = 7;
					} else if ( EasyBooking.firstWeekday === 'sunday' ) { 
						day += 1;
					}
					
					if ( dateObject === day ) {
						return true;
					}

				// Date object
				} else if ( EasyBooking.DateHelper.isDate( dateObject ) ) { 

					if ( timeToCheck === dateObject.getTime() ) {
						return true;
					}

				}

			} );

		},

		/**
		* Add days to a date
		* @param {date} date
		* @param {number} days 
		* @return {date}
		**/
		addDays: function( date, days ) {

			let newDate = new Date( date );

			newDate.setDate( newDate.getDate() + days );

			// Set date to 00:00
			newDate.setHours( 0,0,0,0 );

			return newDate;

		},

		/**
		* Remove days from date
		* @param {date} date
		* @param {number} days 
		* @return {date}
		**/
		removeDays: function( date, days ) {

			let newDate = new Date( date );

			newDate.setDate( newDate.getDate() - days );

			// Set date to 00:00
			newDate.setHours( 0,0,0,0 );

			return newDate;

		}

	}

	EasyBooking.Datepickers = ( function() {

		class Datepickers {

			/**
			* Datepicker abstract class
			* @constructor
			* @param {object} $cart
			**/
			constructor( $cart ) {

				// Make sure we don't instatiate this class, as it needs to be extended for each product type
				if ( new.target === Datepickers ) {
					throw new Error('You cannot instantiate an abstract class!');
				}

				if ( ! $cart.length ) {
					return false;
				}

				this.$cart               = $cart;
				this.$picker_wrap        = this.$cart.find('.wceb_picker_wrap');
				this.$reset_dates        = this.$cart.find('a.reset_dates');
				this.$booking_price      = this.$cart.find('.booking_price');
				this.$add_to_cart_button = this.$cart.find('.single_add_to_cart_button');
				this.$qty_input          = this.$cart.find('input[name="quantity"]');

				// Create corresponding product object
				this.createProduct();

				// Product Add-Ons compatibility
				this.PAO_form = typeof WC_PAO !== 'undefined' ? WC_PAO.initialized_forms.find( item => item.$el[0].isEqualNode( this.$cart[0] ) ) : undefined;
				this.addons   = {};

				if ( typeof this.product.id !== 'undefined' ) {

					// Start picker
					this.$inputStart = this.$cart.find('.wceb_datepicker_start').pickadate();

					// End picker
					this.$inputEnd = this.$cart.find('.wceb_datepicker_end').pickadate();

					// Create pickers
					this.createPickers();

					// Bind other picker object to each picker
					this.StartPicker.otherPicker = this.EndPicker;
					this.EndPicker.otherPicker   = this.StartPicker;

					this.events();

					this.init();

				}

			}

			/**
			* Create product object
			**/
			createProduct() {
				this.product = new Product( this );
			}

			/**
			* Create pickers objects
			**/
			createPickers() {
				this.StartPicker = new Picker( this, 'start' );
				this.EndPicker   = new Picker( this, 'end' );
			}

			/**
			* Datepicker events
			**/
			events() {

				var self = this;

				// Reset dates button
				self.$reset_dates.on(
					'click',
					function (e) {
						e.preventDefault();
						self.init();
					}
				).hide();

				// Quantity change
				self.$qty_input.on(
					'change',
					function (e) {

						self.updateTotals();
						e.stopPropagation();

					}

				);

				self.$cart.on(
					'clear_start_date clear_end_date',
					function() {
						self.clearBookingPrice();
					}
				);

				// WooCommerce Product Add-ons compatibility
				self.$cart.on( 'updated_addons', function() {
					
					if ( typeof self.PAO_form === 'undefined' ) {
						return;
					}

					// Event is triggered even if addon selection hasn't changed so we need to compare before and after values to prevent multiple ajax requests.
					if ( JSON.stringify( self.addons ) === JSON.stringify( self.PAO_form.totals.addons_price_data ) ) {
						return;
					}

					// Store previously selected items
					self.addons = Object.assign( [], self.PAO_form.totals.addons_price_data );
					
					self.updateTotals();
					
				});

				self.$add_to_cart_button.on( 'click', function(e) {
		
					if ( $(this).is( '.disabled, .date-selection-needed' ) && ! $(this).hasClass( 'wc-variation-selection-needed' ) && ! $(this).hasClass( 'wc-variation-is-unavailable' ) ) {
		
						e.preventDefault();
						window.alert( self.product.select_dates_message );
						e.stopPropagation();
		
					}
		
				});

				// pickadate.js events for start datepicker
				self.StartPicker.pickerObject.on({
					
					before_render: function () {
						
						if ( self.product.booking_dates === 'two' && self.EndPicker.isSet() ) {	
							self.StartPicker.applyBookingDuration( true );
						}

					},
					after_render: function () {},
					render: function () {

						self.StartPicker.display();

						// Reset disabled dates
						if ( self.product.booking_dates === 'two' ) {
							self.StartPicker.pickerItem.disable = self.StartPicker.getDisabled();
						}

					},
					set: function ( data ) {
						
						self.StartPicker.set( data );

						if ( typeof data.select !== 'undefined' && data.select !== null && self.hasSelectedDates() ) {
							self.calcBookingPrice(); // Ajax request to calculate price and store session data
						}

					},
					close: function () {
						self.StartPicker.close();
					}

				});

				// pickadate.js events for end datepicker
				self.EndPicker.pickerObject.on({

					before_render: function () {
						
						if ( self.product.booking_dates === 'two' && self.StartPicker.isSet() ) {
							self.EndPicker.applyBookingDuration();
						}

					},
					render: function () {

						self.EndPicker.display();

						// Reset disabled dates
						if ( self.product.booking_dates === 'two' ) {
							self.EndPicker.pickerItem.disable = self.EndPicker.getDisabled(); // Reset disabled dates
						}

					},
					after_render: function () {},
					set: function ( data ) {

						self.EndPicker.set( data );

						// If both pickers are set
						if ( typeof data.select !== 'undefined' && data.select !== null && self.hasSelectedDates() ) {
							self.calcBookingPrice(); // Ajax request to calculate price and store session data
						}

					},
					close: function () {
						self.EndPicker.close();
					}

				});

			}

			/**
			* Reset everything
			* After clicking 'Reset dates' button or when updating product selection (variable, grouped, bundle products)
			**/
			init() {

				// Reset pickers
				this.initPickers();

				// Clear session
				this.clearBookingPrice();

				// Hide reset dates button
				this.$reset_dates.hide();

			}

			/**
			* Reset datepickers
			**/
			initPickers() {

				this.$cart.trigger( 'pickers_init', this );

				// We must clear each picker seperately before resetting to default values
				this.StartPicker.clear();
				this.EndPicker.clear();

				this.StartPicker.reset();
				this.EndPicker.reset();

				this.$cart.trigger( 'after_pickers_init', this );

			}

			/**
			* Clear booking price and booking details
			**/
			clearBookingPrice() {

				this.$booking_price.find( '.price' ).html('');

				this.$cart.find( '.booking_details' ).html('');
				this.$add_to_cart_button.addClass( 'date-selection-needed' );

			}
			
			/**
			* Calculate booking price
			* Ajax request to calculate price and get booking details
			**/
			calcBookingPrice() {

				var self = this;

				let data = {
					security       : document.getElementsByName('_wceb_nonce')[0].value,
					product_id     : self.product.id,
					quantity       : self.$qty_input.val(),
					variation_id   : self.product.variation_id,
					children       : self.product.selectedIDs,
					start_format   : self.StartPicker.pickerObject.get( 'select', 'yyyy-mm-dd' ),
					additional_cost: self.getAdditionalCosts( 'each' )
				};

				// Maybe add end date
				if ( self.product.booking_dates === 'two' ) {
					data.end_format = self.EndPicker.pickerObject.get( 'select', 'yyyy-mm-dd' );
				}

				// Block
				self.$cart.fadeTo( '400', '0.6' ).css( 'cursor', 'wait' );

				$.post( EasyBooking.ajaxUrl.toString().replace( '%%endpoint%%', 'set_booking_session' ), data, function ( response ) {

					self.$cart.find( '.woocommerce-error, .woocommerce-message' ).remove();

					let fragments = response.fragments;
					let error     = response.error;

					// Manage errors
					if ( error ) {

						// Display error
						self.$picker_wrap.prepend( `<div class="wceb_error woocommerce-error">${error}</div>` );

						// Reset pickers
						self.init();

						// Unblock
						self.$cart.fadeTo( 0, '1' ).css( 'cursor', 'auto' );

						return false;

					}

					// No error
					if ( fragments ) {

						// Replace fragments (booking details)
						$.each( fragments, function ( key, value ) {
							self.$cart.find( key ).replaceWith( value );
						});

						// Update price
						self.updatePrice( fragments.booking_price, fragments.booking_regular_price !== '' ? fragments.booking_regular_price : fragments.booking_price, false );

					}

					// Trigger event for Easy Booking PRO
					self.$cart.trigger( 'update_price', response );

					// Allow add to cart
					self.$add_to_cart_button.removeClass( 'date-selection-needed' );

					// Unblock
					self.$cart.fadeTo( 0, '1' ).css( 'cursor', 'auto' );

				});

			}

			/**
			* Check if dates are selected.
			* One-date selection only checks for start date, two-dates selection checks for start and end dates.
			* @return {boolean}
			**/
			hasSelectedDates() {

				if ( ( this.product.booking_dates === 'one' && this.StartPicker.isSet() )
					|| ( this.product.booking_dates === 'two' && this.StartPicker.isSet() && this.EndPicker.isSet() ) ) {
					return true;
				}

				return false;

			}

			/**
			* Get price (raw or calculated)
			* @param {string} type price or regular price
			* @return {number}
			**/
			getPrice( type = 'price' ) {

				let price = parseFloat( this.$booking_price.attr( ( type === 'regular' ? 'data-booking_regular_price' : 'data-booking_price' ) ) );

				// If dates are not set, get (price + addons) * qty, otherwise get stored price (calculated in backend)
				if ( ! this.hasSelectedDates() ) {

					price += parseFloat( this.getAdditionalCosts( 'total' ) );
					price *= this.$qty_input.length ? parseFloat( this.$qty_input.val() ) : 1;

				}
				
				return price;

			}

			/**
			* Get regular price (raw or calculated)
			* @return {number}
			**/
			getRegularPrice() {
				return this.getPrice( 'regular' );
			}

			/**
			* Get formatted price HTML
			* @param {boolean} perDay maybe add suffix (/ day, / night, etc.)
			* @return {string}
			**/
			getPriceHtml( perDay = true ) {

				let price        = this.getPrice();
				let regularPrice = this.getRegularPrice();

				//let price_html = '<span class="woocommerce-Price-amount amount">' + EasyBooking.Helper.formatPrice( price ) + '</span>' + this.product.price_suffix;
				let price_html = `<span class="woocommerce-Price-amount amount">${EasyBooking.Helper.formatPrice( price )}</span>${this.product.price_suffix}`;

				if ( price !== regularPrice ) {

					let regular_price_html = `<span class="woocommerce-Price-amount amount">${EasyBooking.Helper.formatPrice( regularPrice )}</span>${this.product.price_suffix}`;

					price_html = `<del>${regular_price_html}</del> <ins>${price_html}</ins>`;

				}
				
				return `<span class="price">${price_html}${perDay ? ` <span class="wceb_price_format">${this.product.prices_html}</span>` : ""}</span>`;

			}

			/**
			* Update price HTML and data attributes
			* @param {number} price
			* @param {number} regularPrice
			* @param {boolean} perDay maybe add suffix (/ day, / night, etc.)
			**/
			updatePrice( price, regularPrice, perDay = true ) {
				
				// Update booking_price and booking_regular_price data-attributes
				this.$booking_price.attr( 'data-booking_price', parseFloat( price ) );
				this.$booking_price.attr( 'data-booking_regular_price', parseFloat( regularPrice ) );

				// Update price HTML
				this.$booking_price.html( this.getPriceHtml( perDay ) );

			}

			/**
			* Update total price
			* If dates are selected recalculate price, otherwise display raw price
			**/
			updateTotals() {

				if ( this.hasSelectedDates() ) {

					this.calcBookingPrice();

				} else {

					this.$booking_price.find('.price .amount').html( EasyBooking.Helper.formatPrice( this.getPrice() ) );
					this.$booking_price.find('.price del .amount').html( EasyBooking.Helper.formatPrice( this.getRegularPrice() ) );

				}

			}

			/**
			* UHandle multiple product selection (gouped and bundle products)
			* @param {object} previouslySelected
			* @param {number} price
			* @param {number} regularPrice
			**/
			handleMultipleProductSelection( previouslySelected, price, regularPrice ) {

				var self = this;

				let action = 'init';

				let currentIDs  = Object.keys( self.product.selectedIDs );
				let previousIDs = Object.keys( previouslySelected );

				// Check if we updated selected products
				if ( currentIDs.length === previousIDs.length && currentIDs.every( ( value, index ) => value === previousIDs[index] ) ) {

					// If not, loop through each selected item to see if quantity has changed
					$.each( self.product.selectedIDs, function( id, quantity ) {

						// If not, set action to false to avoid triggering ajax request twice because of PB, otherwise update
						action = previouslySelected[id] !== quantity ? 'update' : false;
						return action !== 'update';

					});

				}
				
				if ( action === 'init' ) {

					self.init();
					self.updatePrice( price, regularPrice );

				} else if ( action === 'update' ) {

					// If dates are selected and we only adjust quantity, recalculate price, otherwise update price HTML
					self.hasSelectedDates() ? self.calcBookingPrice() : self.updatePrice( price, regularPrice );

				}

			}

			/**
			* Product Add-Ons compatibility
			* @param {string} format total or each
			* @return {number|object} total cost or array of additional costs
			**/
			getAdditionalCosts( format = 'total' ) {

				var self = this;

				if ( typeof self.PAO_form === 'undefined' ) {
					return 0;
				}

				if ( format === 'total' ) {
					return self.PAO_form.totals.total;
				}

				let costs = [];

				$.each( self.PAO_form.totals.addons_price_data, function( i, data ) {

					let addonName  = data.nameFormattedHTML.split('<span class="wc-pao-addon-name">').pop().split('</span>')[0];
					let addonValue = data.nameFormattedHTML.split('<span class="wc-pao-addon-value">').pop().split('</span>')[0];

					let id = self.$cart.find( `.wc-pao-addon-name[data-addon-name="${addonName}"]` )
							.parents( '.wc-pao-addon' )
							.attr( 'class' )
							.match( /(?:^|\s)wc-pao-addon-id-([^- ]+)(?:\s|$)/ )[1];

					costs.push( { id: id, cost: data.cost_raw, value: addonValue } );

				});

				return costs;

			}
			
		}

		class Product {

			/**
			* Product class
			* @constructor
			* @param {object} Datepickers
			**/
			constructor( Datepickers ) {

				if ( $.isEmptyObject( Datepickers ) ) {
					return false;
				}

				// Get product ID
				this.id = Datepickers.$cart.find('input[name="add-to-cart"], button[name="add-to-cart"]').val();

				if ( typeof this.id === 'undefined' ) {
					return false;
				}

				// Map parameters
				this.booking_dates        = EASYBOOKING.product_params[this.id].booking_dates;
				this.booking_duration     = parseInt( EASYBOOKING.product_params[this.id].booking_duration );
				this.children             = EASYBOOKING.product_params[this.id].children;
				this.end_text             = EASYBOOKING.product_params[this.id].end_text;
				this.first_date           = parseInt( EASYBOOKING.product_params[this.id].first_date );
				this.max                  = EASYBOOKING.product_params[this.id].max !== '' ? parseInt( EASYBOOKING.product_params[this.id].max ) : '';
				this.min                  = parseInt( EASYBOOKING.product_params[this.id].min );
				this.price_suffix         = EASYBOOKING.product_params[this.id].price_suffix;
				this.prices_html          = EASYBOOKING.product_params[this.id].prices_html;
				this.product_type         = EASYBOOKING.product_params[this.id].product_type;
				this.select_dates_message = EASYBOOKING.product_params[this.id].select_dates_message;
				this.start_text           = EASYBOOKING.product_params[this.id].start_text;
				this.prices               = EASYBOOKING.product_params[this.id].prices;
				this.regular_prices       = EASYBOOKING.product_params[this.id].regular_prices;
				this.selectedIDs          = {};

			}

		}

		class Picker {

			/**
			* Picker class
			* @constructor
			* @param {object} Datepickers
			* @param {string} type start or end
			**/
			constructor( Datepickers, type = 'start' ) {

				this.type         = type;
				this.$cart        = Datepickers.$cart;
				this.$reset_dates = Datepickers.$reset_dates;
				this.$input       = this.type === 'start' ? Datepickers.$inputStart : Datepickers.$inputEnd;

				this.pickerObject = this.$input.pickadate( 'picker' );
				this.pickerItem   = this.pickerObject.component.item;

				this.product = Datepickers.product;

				// Store disabled dates in another variable for later use
				this.disabled = [];
				
			}

			/**
			* Clear picker selection
			**/
			clear() {

				this.pickerItem.clear  = null;
				this.pickerItem.select = undefined;

				this.pickerObject.$node.val('');

			}

			/**
			* Reset picker
			**/
			reset() {

				let min = this.getMinimum();
				let max = this.getMaximum();
				
				// Reset disabled dates
				this.pickerItem.disable = this.getDisabled();

				// Set default values
				this.pickerItem.min       = EasyBooking.DateHelper.createDateObject( min );
				this.pickerItem.max       = EasyBooking.DateHelper.createDateObject( max );
				this.pickerItem.highlight = EasyBooking.DateHelper.createDateObject( min );
				this.pickerItem.view      = EasyBooking.DateHelper.createDateObject( new Date( min.getFullYear(), min.getMonth(), 1 ) );

				this.pickerObject.render();

			}

			/**
			* Display picker title
			**/
			display() {

				this.pickerObject.$root
					.find( '.picker__box' )
					.prepend( `<div class="picker__title">${( this.type === 'start' ? this.product.start_text : this.product.end_text )}</div>` );

			}

			/**
			* Set picker (clear or select date)
			* @param {object} data
			**/
			set( data ) {

				if ( typeof data.clear !== 'undefined' && data.clear === null ) {

					if ( this.product.booking_dates === 'two' ) {

						// If picker is cleared, maybe reset other picker
						this.otherPicker.reset();

						if ( ! this.otherPicker.isSet() ) {
							this.$reset_dates.hide();
						}

					}

					this.$cart.trigger( `clear_${this.type}_date` );

				} else if ( typeof data.select !== 'undefined' ) {

					// If picker is set, maybe update other picker
					if ( this.product.booking_dates === 'two' ) {

						this.otherPicker.update();
						this.$reset_dates.show();

					}

				}

			}

			/**
			* Update picker depending on other picker date selection
			**/
			update() {

				if ( ! this.otherPicker.isSet() ) {
					return;
				}

				let min = this.getMinimum();
				let max = this.getMaximum();

				// Get the closest disabled date
				let closestDisabled = this.getClosestDisabled();

				// If a date is disabled, maybe set it as min and/or max (depending on calendar)
				if ( closestDisabled ) {

					if ( ( this.type === 'end' && closestDisabled < max ) 
					|| ( this.type === 'start' && closestDisabled > min )) {

						this.type === 'end' ? max = closestDisabled : min = closestDisabled;

					}

				}

				this.pickerItem.min  = EasyBooking.DateHelper.createDateObject( min );
				this.pickerItem.max  = EasyBooking.DateHelper.createDateObject( max );

				let highlight = new Date( min );

				while ( true === EasyBooking.DateHelper.isDisabled( this.getDisabled(), highlight ) ) {
					highlight.setDate( highlight.getDate() + 1 );
				}

				this.pickerItem.highlight = EasyBooking.DateHelper.createDateObject( highlight );
				this.pickerItem.view      = EasyBooking.DateHelper.createDateObject( new Date( highlight.getFullYear(), highlight.getMonth(), 1 ) );

				this.$cart.trigger( `set_${this.type}_picker` );

				this.pickerObject.render();

			}

			/**
			* Maybe open other picker after closing current picker
			**/
			close() {

				var self = this;

				// Bug fix
				$( document.activeElement ).trigger( 'blur' );

				// Open other picker if current picker is set and the other not
				if ( self.product.booking_dates === 'two' && self.isSet() && ! self.otherPicker.isSet() ) {
					setTimeout( function () { self.otherPicker.pickerObject.open(); }, 250 );
				}

			}

			/**
			* Get picker selected date
			* @return {date|boolean}
			**/
			getSelected() {
				return this.isSet() ? new Date( this.pickerItem.select.pick ) : false;
			}

			/**
			* Get picker first available date
			* Maybe add minimum booking duration on end calendar
			* @return {date}
			**/
			getFirstAvailableDate() {

				let first = new Date();
				let add   = this.type === 'start' ? +parseInt( this.product.first_date ) : parseInt( this.product.first_date + this.product.min );

				if ( add > 0 ) {
					first.setDate( first.getDate() + add );
				}

				// If first available date is disabled, check the next date until one is available
				while ( true === EasyBooking.DateHelper.isDisabled( this.getDisabled(), first ) ) {
					first.setDate( first.getDate() + 1 );
				}

				return ( first instanceof Date ) ? first : new Date( first );

			}

			/**
			* Get picker minimum date
			* @return {date}
			**/
			getMinimum() {

				// Maybe get other picker selected date
				let selected = this.otherPicker.getSelected();
				let min      = selected ? selected : new Date();
				
				if ( selected ) {

					if ( this.type === 'start' ) {

						// After setting end date, remove maximum booking duration from selected date
						min = this.product.max !== '' ? EasyBooking.DateHelper.removeDays( selected, this.product.max ) : this.getFirstAvailableDate();

					} else if ( this.type === 'end' ) {
						
						// After setting start date, maybe add minimum booking duration to selected date
						min = EasyBooking.DateHelper.addDays( selected, this.product.min );
	
					}

				} else {

					let add = this.type === 'start' ? +parseInt( this.product.first_date ) : parseInt( this.product.first_date + this.product.min );
	
					if ( add > 0 ) {
						min = EasyBooking.DateHelper.addDays( min, add );
					}

				}
				
				// If first available date is disabled, check the next date until one is available
				while ( true === EasyBooking.DateHelper.isDisabled( this.getDisabled(), min ) ) {
					min = EasyBooking.DateHelper.addDays( min, 1 );
					
				}
				
				return ( min instanceof Date ) ? min : new Date( min );

			}

			/**
			* Get picker maximum date
			* @return {date}
			**/
			getMaximum() {

				// Maybe get other picker selected date
				let selected = this.otherPicker.getSelected();
				let max      = selected ? selected : EasyBooking.maxOption;

				if ( this.type === 'start' ) {

					// After setting end date, add minimum booking duration to selected date
					max.setDate( max.getDate() - this.product.min );

				} else if ( this.type === 'end' ) {

					// After setting start date, maybe add maximum booking duration to selected date, or set minimum to last available date
					max = this.product.max !== '' ? max.setDate( max.getDate() + this.product.max ) : EasyBooking.maxOption;

				}

				// If last available date is before maximum date, set maximum date to last available date
				if ( EasyBooking.maxOption < max ) {
					max = EasyBooking.maxOption;
				}

				return ( max instanceof Date ) ? max : new Date( max );

			}

			/**
			* Get picker disabled dates
			* @return {array}
			**/
			getDisabled() {
				return this.disabled;
			}

			/**
			* Maybe apply booking duration to other calendar
			* @param {bool} reverse apply booking duration in reverse (end to start)
			**/
			applyBookingDuration( reverse = false ) {

				// Get selected date on other picker
				let selected = this.otherPicker.getSelected();

				if ( ! selected || this.product.booking_duration === 1 ) {
					return;
				}

				/*
				* Get first and last date from viewed month (1 to 28-29-30-31)
				*/
				let viewFirst = new Date( this.pickerItem.view.pick );
				let viewLast  = new Date( this.pickerItem.view.year, this.pickerItem.view.month + 1, 0 );

				/*
				* Get number of days to remove from 1st day of viewed month to get first date shown on calendar
				*
				* If week starts on Monday we need to shift day number from:
				* Monday: 1, Tuesday: 2, Wednesday: 3, Thursday: 4, Friday: 5, Saturday: 6, Sunday: 0
				* To:
				* Monday: 0, Tuesday: 1, Wednesday: 2, Thursday: 3, Friday: 4, Saturday: 5, Sunday: 6
				* Week starts on Sunday, no change:
				* Sunday: 0, Monday: 1, Tuesday: 2, Wednesday: 3, Thursday: 4, Friday: 5, Saturday: 6
				*/
				let remove = viewFirst.getDay();

				if ( EasyBooking.firstWeekday === 'monday' ) {
					remove = remove === 0 ? 6 : remove - 1;
				}
				
				/*
				* Get number of days to add to last day of viewed month to get last date shown on calendar
				* daysInCal is the total of days shown (always 42)
				*/
				let add = 42 - ( viewLast.getDate() + remove );

				/*
				* Get first and last viewed dates on calendar
				* After setting End calendar (reverse = true), we go backwards (first becomes last and last becomes first)
				* Make sure first date is not superior or inferior to selected date
				*/
				let first, last;

				if ( reverse ) {

					first = EasyBooking.DateHelper.addDays( viewLast, add );
					last  = EasyBooking.DateHelper.removeDays( viewFirst, remove );

					if ( first > selected ) {
						first = selected;
					}

				} else {

					first = EasyBooking.DateHelper.removeDays( viewFirst, remove );
					last  = EasyBooking.DateHelper.addDays( viewLast, add );

					if ( first < selected ) {
						first = selected;
					}

				}

				// Get number of days between first viewed date and selected date
				let diff = Math.abs( Math.round( ( selected.getTime() - first.getTime() ) / 86400000 ) );

				// Add one day in Days mode because we need to count selected date
				if ( EasyBooking.calcMode === 'days' ) {
					diff += 1;
				}

				// See how many booking durations can fit and get number of days left
				let remain = diff % this.product.booking_duration;

				/*
				* Get first date to enable
				* If we don't have a complete booking duration, add/remove days left
				*/
				let firstToEnable = first;

				if ( remain > 0 ) {

					firstToEnable = reverse
						? EasyBooking.DateHelper.removeDays( first, this.product.booking_duration - remain )
						: EasyBooking.DateHelper.addDays( first, this.product.booking_duration - remain );

				}

				/*
				* Get dates to disable on viewed calendar
				* First disable all dates, then enable dates corresponding to booking duration
				*/
				let disabled = this.getDisabled();
				let enable   = reverse ? [{ from: last, to: first }] : [{ from: first, to: last }];
				let j = true;
				let highlight = firstToEnable;

				for ( let i = 0; i < 42; i += this.product.booking_duration ) {

					let dateToEnable = i === 0 ? firstToEnable : reverse
						? EasyBooking.DateHelper.removeDays( firstToEnable, i )
						: EasyBooking.DateHelper.addDays( firstToEnable, i );

					// Make sure date is not disabled for other reasons (out of stock or disabled in settings)
					if ( typeof disabled !== 'undefined' && disabled.length > 0
					&& true === EasyBooking.DateHelper.isDisabled( disabled, dateToEnable ) ) {
						continue;
					}
					
					if ( j ) {
						highlight = dateToEnable;
					}

					// Add 'inverted' parameter to enable date instead of disabling it
					enable.push( [
						dateToEnable.getFullYear(),
						dateToEnable.getMonth(),
						dateToEnable.getDate(),
						'inverted']
					);

					j = false;
					
				}

				this.pickerItem.highlight = EasyBooking.DateHelper.createDateObject( new Date( highlight.getFullYear(), highlight.getMonth(), highlight.getDate() ) );

				// Merge with already disabled dates
				this.pickerItem.disable = disabled.concat( enable );

			}

			/**
			* Get closest disabled date from selected date (on other calendar)
			* @param {date|boolean}
			**/
			getClosestDisabled() {

				const selectedTime = this.otherPicker.pickerItem.select.pick;
				const selectedDate = new Date( selectedTime );
				
				let selectedDay = selectedDate.getDay();

				// If first weekday is Sunday, add 1 day (because date object day starts at 0 and JS calendar start at 1)
				if ( EasyBooking.firstWeekday === 'sunday' ) {
					selectedDay += 1;
				}

				const pickerDisabled      = this.pickerObject.get('disable');
				const otherPickerDisabled = this.otherPicker.pickerObject.get('disable');

				const disabled      = EasyBooking.allowDisabled === 'no' ? pickerDisabled.concat(otherPickerDisabled) : pickerDisabled;
				const disabledTimes = new Set();

				// Loop through each disabled date to store time
				disabled.forEach( date => {

					// [year, month, day, type]
					if ( EasyBooking.DateHelper.isArray( date ) && ( date[3] === 'booked' || EasyBooking.allowDisabled === 'no') ) {

						disabledTimes.add( new Date( date[0], date[1], date[2] ).getTime() );

					// { from: date, to: date, type: type }
					} else if ( EasyBooking.DateHelper.isObject( date ) && ( date.type === 'booked' || EasyBooking.allowDisabled === 'no' ) ) {

						const getDate = this.type === 'end' ? new Date( date.from[0], date.from[1], date.from[2] ) : new Date( date.to[0], date.to[1], date.to[2] );
			
						disabledTimes.add( getDate.getTime() );

					// Date object
					} else if ( EasyBooking.DateHelper.isDate( date ) ) {

						disabledTimes.add( date.getTime() );

					// 1, 2, 3, 4, 5, 6, 7
					} else if ( EasyBooking.allowDisabled === 'no' && EasyBooking.DateHelper.isDay( date ) ) {

						let interval = Math.abs( selectedDay - date );

						interval = interval === 0 ? 7 : interval;

						if ( this.type === 'end' ) {

							if ( date < selectedDay && interval !== 7 ) interval = 7 - interval;
							disabledTimes.add( selectedDate.setDate( selectedDate.getDate() + interval ) );

						} else if ( this.type === 'start' ) {

							if ( selectedDay < date && interval !== 7 ) interval = 7 - interval;
							disabledTimes.add( selectedDate.setDate( selectedDate.getDate() - interval ) );

						}

						 // Reset selected date
						selectedDate.setTime( selectedTime );

					}

				});
				
				const sortedDisabledTimes = Array.from( disabledTimes ).sort( ( a, b ) => this.type === 'end' ? a - b : b - a );
				const closestDisabledTime = sortedDisabledTimes.find( time => this.type === 'end' ? time > selectedTime : time < selectedTime );

				return closestDisabledTime ? new Date( closestDisabledTime ) : false;

			}

			/**
			* Check if picker is set
			* @param {boolean}
			**/
			isSet() {
				return typeof this.pickerItem.select !== 'undefined' && this.pickerItem.select !== null;
			}

		}

		return {Datepickers, Picker};

	}());

	/**
	* Get datepicker class to extend
	* Check if Easy Booking PRO is active, otherwise use Easy Booking
	* @param {string} type Product type
	* @return {class}
	**/
	EasyBooking.datepickersClass = ( type ) => {
		
		const types = [ 'simple', 'variable', 'grouped', 'bundle' ];

		if ( types.includes( type ) ) {

			return typeof EasyBookingPro !== 'undefined' ?
			EasyBookingPro[`${type.charAt(0).toUpperCase() + type.slice(1)}Datepickers`] :
			EasyBooking.Datepickers.Datepickers;

		} else {
			throw new Error( 'Product type is incorrect!' );
		}

	}

}(jQuery, window));