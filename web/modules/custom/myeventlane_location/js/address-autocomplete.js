/**
 * @file
 * Address autocomplete functionality for Google Maps and Apple Maps.
 */

// CRITICAL: Log BEFORE IIFE to verify file is parsed
console.log('========================================');
console.log('MyEventLane Location: JavaScript file parsed');
console.log('MyEventLane Location: File loaded at:', new Date().toISOString());
console.log('MyEventLane Location: Drupal available?', typeof Drupal !== 'undefined');
console.log('MyEventLane Location: drupalSettings available?', typeof drupalSettings !== 'undefined');
console.log('========================================');

// Get Drupal and drupalSettings from window if not passed
var Drupal = typeof Drupal !== 'undefined' ? Drupal : (typeof window !== 'undefined' ? window.Drupal : null);
var drupalSettings = typeof drupalSettings !== 'undefined' ? drupalSettings : (typeof window !== 'undefined' ? window.drupalSettings : null);

(function (Drupal, drupalSettings) {
  'use strict';

  // Immediate console log to verify script is loading.
  console.log('========================================');
  console.log('MyEventLane Location: IIFE executing');
  console.log('MyEventLane Location: Drupal:', Drupal ? 'available' : 'MISSING');
  console.log('MyEventLane Location: drupalSettings:', drupalSettings ? 'available' : 'MISSING');
  console.log('========================================');

  // Track initialization to prevent double initialization.
  const initializedForms = new WeakSet();

  /**
   * Initializes address autocomplete on Event forms.
   */
  function initAddressAutocomplete() {
    console.log('MyEventLane Location: Initializing address autocomplete');
    
    // Check if drupalSettings is available
    if (!drupalSettings) {
      console.error('MyEventLane Location: drupalSettings is not available!');
      console.error('MyEventLane Location: Cannot initialize without settings.');
      return;
    }
    
    const settings = drupalSettings.myeventlaneLocation || {};
    const provider = settings.provider || 'google_maps';
    
    console.log('MyEventLane Location: Settings', settings);
    console.log('MyEventLane Location: Provider:', provider);
    console.log('MyEventLane Location: Google Maps API key present?', !!settings.google_maps_api_key);
    
    if (provider === 'google_maps' && !settings.google_maps_api_key) {
      console.error('MyEventLane Location: Google Maps API key not found in settings!');
      console.error('MyEventLane Location: Full drupalSettings:', drupalSettings);
      return;
    }

    // Find the address search input - check multiple locations.
    let searchInput = document.querySelector('.myeventlane-location-address-search');
    console.log('MyEventLane Location: Search input (direct)', searchInput);
    
    if (!searchInput) {
      // Check in wizard content areas.
      const wizardContent = document.querySelector('.mel-event-form__wizard-content, .mel-wizard-step, .mel-event-wizard__content');
      if (wizardContent) {
        searchInput = wizardContent.querySelector('.myeventlane-location-address-search');
        console.log('MyEventLane Location: Search input (wizard)', searchInput);
      }
    }
    if (!searchInput) {
      // Check in location containers.
      const locationContainer = document.querySelector('.location, .location-fields-container, .mel-form-card:has(.field--name-field-location), .field--name-field-location');
      if (locationContainer) {
        searchInput = locationContainer.querySelector('.myeventlane-location-address-search');
        console.log('MyEventLane Location: Search input (container)', searchInput);
      }
    }
    if (!searchInput) {
      // Check by name attribute - try exact match first (wizard uses nested names).
      searchInput = document.querySelector('input[name="location_fields[field_location_address_search]"]');
      console.log('MyEventLane Location: Search input (exact name match)', searchInput);
    }
    if (!searchInput) {
      // Check by data attribute.
      searchInput = document.querySelector('input[data-address-search="true"]');
      console.log('MyEventLane Location: Search input (data attribute)', searchInput);
    }
    if (!searchInput) {
      // Check by name pattern.
      searchInput = document.querySelector('input[name*="address_search"], input[name*="field_location"][name*="address_search"], input[name*="field_location_address_search"]');
      console.log('MyEventLane Location: Search input (by name pattern)', searchInput);
    }
    if (!searchInput) {
      // Check in any form (including wizard).
      const form = document.querySelector('form.node-event-form, form.node-event-edit-form, form#event-wizard-form, form[id="event-wizard-form"], form[id*="event_wizard"], form.mel-event-wizard, form');
      if (form) {
        searchInput = form.querySelector('.myeventlane-location-address-search');
        console.log('MyEventLane Location: Search input (form)', searchInput);
      }
    }
    
    if (!searchInput) {
      console.warn('MyEventLane Location: Search input not found. Available inputs:', 
        Array.from(document.querySelectorAll('input[type="text"]')).map(el => ({
          name: el.name,
          class: el.className,
          id: el.id,
          visible: el.offsetParent !== null
        }))
      );
      // For wizard forms, the field might be hidden initially or on a different step.
      // Set up observers and listeners to initialize when the location step becomes visible.
      const isWizard = document.querySelector('.mel-event-wizard, form#event-wizard-form, form[id*="event_wizard"]');
      if (isWizard) {
        console.log('MyEventLane Location: Wizard form detected, setting up step change listeners');
        
        // Listen for AJAX completion (when wizard steps change).
        if (typeof Drupal !== 'undefined' && Drupal.ajax) {
          document.addEventListener('ajaxSuccess', function(event) {
            // Check if location step is now visible.
            setTimeout(function() {
              const locationStep = document.querySelector('.mel-wizard-step, .location-fields-container');
              const searchField = document.querySelector('.myeventlane-location-address-search, input[name*="field_location_address_search"]');
              if (locationStep && searchField && searchField.offsetParent !== null) {
                console.log('MyEventLane Location: Location step visible after AJAX, initializing');
                initAddressAutocomplete();
              }
            }, 100);
          });
        }
        
        // Also set up a MutationObserver to watch for the field appearing.
        const observer = new MutationObserver(function(mutations) {
          const searchField = document.querySelector('.myeventlane-location-address-search, input[name*="field_location_address_search"]');
          if (searchField && searchField.offsetParent !== null) {
            console.log('MyEventLane Location: Search field appeared, initializing');
            observer.disconnect();
            initAddressAutocomplete();
          }
        });
        
        // Observe the wizard container for changes.
        const wizardContainer = document.querySelector('.mel-event-wizard, form#event-wizard-form, #event-wizard-wrapper');
        if (wizardContainer) {
          observer.observe(wizardContainer, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['style', 'class']
          });
        }
        
        // Also try periodically (fallback).
        let retryCount = 0;
        const maxRetries = 10;
        const retryInterval = setInterval(function() {
          retryCount++;
          const searchField = document.querySelector('.myeventlane-location-address-search, input[name*="field_location_address_search"]');
          if (searchField && searchField.offsetParent !== null) {
            console.log('MyEventLane Location: Search field found on retry', retryCount);
            clearInterval(retryInterval);
            initAddressAutocomplete();
          } else if (retryCount >= maxRetries) {
            console.warn('MyEventLane Location: Max retries reached, stopping');
            clearInterval(retryInterval);
          }
        }, 500);
      }
      return;
    }
    
    console.log('MyEventLane Location: Search input found', searchInput.name, searchInput.className, 'visible:', searchInput.offsetParent !== null);
    
    // If the search input is hidden (e.g., location_mode is 'online'), wait for it to become visible.
    if (searchInput.offsetParent === null) {
      console.log('MyEventLane Location: Search input is hidden, setting up observer to initialize when visible');
      const observer = new MutationObserver(function(mutations, obs) {
        if (searchInput.offsetParent !== null) {
          console.log('MyEventLane Location: Search input is now visible, initializing');
          obs.disconnect();
          // Clear initialization flag to allow re-init.
          const form = searchInput.closest('form');
          if (form && initializedForms.has(form)) {
            initializedForms.delete(form);
          }
          setTimeout(function() {
            initAddressAutocomplete();
          }, 100);
        }
      });
      
      // Observe the input and its parent containers for visibility changes.
      observer.observe(searchInput, { attributes: true, attributeFilter: ['style', 'class'] });
      if (searchInput.parentElement) {
        observer.observe(searchInput.parentElement, { attributes: true, attributeFilter: ['style', 'class'] });
      }
      
      // Also check periodically (fallback).
      let checkCount = 0;
      const checkInterval = setInterval(function() {
        checkCount++;
        if (searchInput.offsetParent !== null) {
          console.log('MyEventLane Location: Search input became visible (periodic check)');
          clearInterval(checkInterval);
          observer.disconnect();
          const form = searchInput.closest('form');
          if (form && initializedForms.has(form)) {
            initializedForms.delete(form);
          }
          setTimeout(function() {
            initAddressAutocomplete();
          }, 100);
        } else if (checkCount > 20) {
          // Stop checking after 10 seconds.
          clearInterval(checkInterval);
          observer.disconnect();
          console.warn('MyEventLane Location: Search input did not become visible after 10 seconds');
        }
      }, 500);
      
      return;
    }

    // Prevent double initialization.
    const form = searchInput.closest('form');
    if (form && initializedForms.has(form)) {
      return;
    }
    if (form) {
      initializedForms.add(form);
    }
    
    // Find the widget container.
    let widget = form.querySelector('[data-mel-address="field_location"], .myeventlane-location-address-widget, .field--name-field-location, fieldset[data-drupal-selector*="field-location"]');
    if (!widget) {
      const wizardContent = form.querySelector('.mel-event-form__wizard-content, .mel-wizard-step, .mel-event-form__section, .mel-event-wizard__content, .location-fields-container');
      if (wizardContent) {
        widget = wizardContent.querySelector('[data-mel-address="field_location"], .myeventlane-location-address-widget, .field--name-field-location, fieldset[data-drupal-selector*="field-location"]');
      }
    }
    if (!widget) {
      const locationContainer = form.querySelector('.location, .mel-form-card:has(.field--name-field-location)');
      if (locationContainer) {
        widget = locationContainer.querySelector('[data-mel-address="field_location"], .myeventlane-location-address-widget, .field--name-field-location, fieldset[data-drupal-selector*="field-location"]');
      }
    }
    if (!widget) {
      widget = form.querySelector('fieldset:has(input[name*="address_line1"]), [data-drupal-selector*="field-location"]');
    }
    if (!widget) {
      if (searchInput) {
        widget = searchInput.closest('[data-mel-address="field_location"], .field--name-field-location, fieldset, .myeventlane-location-address-widget, .form-item');
      }
    }
    if (!widget) {
      widget = form;
    }

    // Initialize based on provider.
    if (provider === 'google_maps') {
      initGoogleMapsAutocomplete(widget, searchInput, settings);
    } else if (provider === 'apple_maps') {
      initAppleMapsAutocomplete(widget, searchInput, settings);
    }
    
    // For wizard forms, also listen for location_mode changes to re-initialize.
    const locationModeInputs = form.querySelectorAll('input[name="location_mode"]');
    if (locationModeInputs.length > 0) {
      console.log('MyEventLane Location: Found location_mode inputs, setting up change listener');
      locationModeInputs.forEach(function(radio) {
        radio.addEventListener('change', function() {
          if (radio.value === 'in_person') {
            console.log('MyEventLane Location: Location mode changed to in_person, re-initializing in 500ms');
            // Clear initialization flag.
            if (initializedForms.has(form)) {
              initializedForms.delete(form);
            }
            setTimeout(function() {
              initAddressAutocomplete();
            }, 500);
          }
        });
      });
    }
  }

  /**
   * Initializes Google Maps Places Autocomplete.
   */
  function initGoogleMapsAutocomplete(widget, searchInput, settings) {
    console.log('MyEventLane Location: initGoogleMapsAutocomplete called', 'google defined:', typeof google !== 'undefined');
    
    if (typeof google === 'undefined' || !google.maps || !google.maps.places) {
      console.log('MyEventLane Location: Google Maps API not loaded, loading script');
      
      // Check if script is already being loaded.
      const existingScript = document.querySelector('script[src*="maps.googleapis.com/maps/api/js"]');
      if (existingScript) {
        console.log('MyEventLane Location: Google Maps script already exists, waiting for it to load');
        // Wait for existing script to load.
        const checkGoogle = setInterval(function() {
          if (typeof google !== 'undefined' && google.maps && google.maps.places) {
            clearInterval(checkGoogle);
            console.log('MyEventLane Location: Google Maps API loaded, setting up autocomplete');
            setupGoogleMapsAutocomplete(widget, searchInput);
          }
        }, 100);
        
        // Timeout after 10 seconds.
        setTimeout(function() {
          clearInterval(checkGoogle);
          if (typeof google === 'undefined' || !google.maps || !google.maps.places) {
            console.error('MyEventLane Location: Google Maps API failed to load after 10 seconds');
          }
        }, 10000);
        return;
      }
      
      // Create unique callback name to avoid conflicts.
      const callbackName = 'myeventlaneLocationGoogleMapsReady_' + Date.now();
      const script = document.createElement('script');
      script.src = `https://maps.googleapis.com/maps/api/js?key=${settings.google_maps_api_key}&libraries=places&callback=${callbackName}`;
      script.async = true;
      script.defer = true;
      
      window[callbackName] = function () {
        console.log('MyEventLane Location: Google Maps API callback fired');
        delete window[callbackName];
        setupGoogleMapsAutocomplete(widget, searchInput);
      };
      
      script.onerror = function() {
        console.error('MyEventLane Location: Failed to load Google Maps API script');
        delete window[callbackName];
      };
      
      document.head.appendChild(script);
      console.log('MyEventLane Location: Google Maps script added to page');
    } else {
      console.log('MyEventLane Location: Google Maps API already loaded, setting up autocomplete');
      setupGoogleMapsAutocomplete(widget, searchInput);
    }
  }

  /**
   * Sets up Google Maps autocomplete after API is loaded.
   */
  function setupGoogleMapsAutocomplete(widget, searchInput) {
    console.log('MyEventLane Location: Setting up Google Maps autocomplete', searchInput);
    
    if (!searchInput) {
      console.error('MyEventLane Location: Search input not found for autocomplete setup');
      return;
    }
    
    if (typeof google === 'undefined' || !google.maps || !google.maps.places) {
      console.error('MyEventLane Location: Google Maps Places API not loaded');
      return;
    }
    
    const autocomplete = new google.maps.places.Autocomplete(searchInput, {
      types: ['establishment', 'geocode'],
      componentRestrictions: { country: 'au' },
    });

    console.log('MyEventLane Location: Autocomplete created, adding place_changed listener');

    autocomplete.addListener('place_changed', function () {
      console.log('MyEventLane Location: place_changed event fired');
      const place = autocomplete.getPlace();
      
      console.log('MyEventLane Location: Place selected', place);
      
      if (!place.geometry) {
        console.warn('MyEventLane Location: Place has no geometry', place);
        return;
      }

      const addressComponents = extractGoogleAddressComponents(place);
      console.log('MyEventLane Location: Extracted address components', addressComponents);
      
      // Get coordinates.
      const lat = place.geometry.location.lat();
      const lng = place.geometry.location.lng();
      
      // Populate address fields directly (standard Drupal address field).
      populateAddressFields(widget, addressComponents, searchInput);
      
      // Also populate venue name if field exists.
      const form = searchInput.closest('form');
      if (form) {
        populateVenueName(form, addressComponents.name);
        
        // Set coordinates in separate fields.
        setCoordinates(widget, lat, lng, searchInput);
        
        // Set Place ID if available (Google Maps only).
        const placeId = place.place_id || '';
        if (placeId) {
          setPlaceId(widget, placeId, searchInput);
        }
        
        // Trigger formUpdated to ensure Drupal recognizes changes.
        if (typeof jQuery !== 'undefined') {
          jQuery(form).trigger('formUpdated');
          console.log('MyEventLane Location: Triggered formUpdated after address selection');
        }
      }

      // Map preview is optional - don't let it block address population.
      try {
        showMapPreview(widget, lat, lng, 'google_maps', searchInput);
      } catch (error) {
        console.warn('MyEventLane Location: Map preview failed, but address fields were populated:', error);
      }
    });
    
    console.log('MyEventLane Location: Autocomplete setup complete');
  }

  /**
   * Initializes Apple Maps autocomplete.
   */
  function initAppleMapsAutocomplete(widget, searchInput, settings) {
    if (typeof mapkit === 'undefined') {
      const script = document.createElement('script');
      script.src = 'https://cdn.apple-mapkit.com/mk/5.x.x/mapkit.js';
      script.async = true;
      script.onload = function () {
        if (settings.apple_maps_token) {
          mapkit.init({
            authorizationCallback: function (done) {
              done(settings.apple_maps_token);
            },
          });
          setupAppleMapsAutocomplete(widget, searchInput);
        }
      };
      document.head.appendChild(script);
    } else {
      setupAppleMapsAutocomplete(widget, searchInput);
    }
  }

  /**
   * Sets up Apple Maps autocomplete after API is loaded.
   */
  function setupAppleMapsAutocomplete(widget, searchInput) {
    let searchTimeout;
    const searchService = new mapkit.Search({
      region: new mapkit.CoordinateRegion(
        new mapkit.Coordinate(-25.2744, 133.7751),
        new mapkit.CoordinateSpan(40, 40)
      ),
    });

    const suggestionsContainer = document.createElement('div');
    suggestionsContainer.className = 'myeventlane-location-suggestions';
    suggestionsContainer.style.cssText = 'position: absolute; background: white; border: 1px solid #ccc; max-height: 200px; overflow-y: auto; z-index: 1000; width: 100%; display: none;';
    searchInput.parentNode.style.position = 'relative';
    searchInput.parentNode.appendChild(suggestionsContainer);

    searchInput.addEventListener('input', function () {
      const query = this.value.trim();
      if (query.length < 3) {
        suggestionsContainer.style.display = 'none';
        return;
      }

      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(function () {
        searchService.search(query, function (error, data) {
          if (error || !data || !data.places) {
            suggestionsContainer.style.display = 'none';
            return;
          }

          suggestionsContainer.innerHTML = '';
          data.places.slice(0, 5).forEach(function (place) {
            const item = document.createElement('div');
            item.className = 'myeventlane-location-suggestion-item';
            item.style.cssText = 'padding: 8px; cursor: pointer; border-bottom: 1px solid #eee;';
            item.textContent = place.name + (place.formattedAddressLines ? ' - ' + place.formattedAddressLines.join(', ') : '');
            item.addEventListener('click', function () {
              selectAppleMapsPlace(widget, place, searchInput, suggestionsContainer);
            });
            suggestionsContainer.appendChild(item);
          });
          suggestionsContainer.style.display = 'block';
        });
      }, 300);
    });

    document.addEventListener('click', function (e) {
      if (!widget.contains(e.target)) {
        suggestionsContainer.style.display = 'none';
      }
    });
  }

  /**
   * Handles selection of an Apple Maps place.
   */
  function selectAppleMapsPlace(widget, place, searchInput, suggestionsContainer) {
    searchInput.value = place.name + (place.formattedAddressLines ? ' - ' + place.formattedAddressLines.join(', ') : '');
    suggestionsContainer.style.display = 'none';

    const addressComponents = extractAppleAddressComponents(place);
    
    // Get coordinates.
    const lat = place.coordinate.latitude;
    const lng = place.coordinate.longitude;
    
    // Populate address fields directly (standard Drupal address field).
    populateAddressFields(widget, addressComponents, searchInput);
    
    // Also populate venue name if field exists.
    const form = searchInput.closest('form');
    if (form) {
      populateVenueName(form, addressComponents.name);
      
      // Set coordinates in separate fields.
      setCoordinates(widget, lat, lng, searchInput);
      
      // Note: Apple Maps does not provide Google place_id.
      // If a stable identifier becomes available in the future, it can be stored here.
      // For now, leave place_id blank for Apple Maps selections.
      
      // Trigger formUpdated.
      if (typeof jQuery !== 'undefined') {
        jQuery(form).trigger('formUpdated');
      }
    }

    // Map preview is optional - don't let it block address population.
    try {
      showMapPreview(widget, lat, lng, 'apple_maps', searchInput);
    } catch (error) {
      console.warn('MyEventLane Location: Map preview failed, but address fields were populated:', error);
    }
  }

  /**
   * Extracts address components from Google Places result.
   */
  function extractGoogleAddressComponents(place) {
    const components = {
      name: place.name || '',
      address_line1: '',
      address_line2: '',
      locality: '',
      administrative_area: '',
      postal_code: '',
      country_code: 'AU',
    };

    if (!place.address_components || place.address_components.length === 0) {
      if (place.formatted_address) {
        components.address_line1 = place.formatted_address;
      }
      return components;
    }

    place.address_components.forEach(function (component) {
      const types = component.types;
      if (types.includes('street_number')) {
        components.address_line1 = component.long_name + ' ';
      } else if (types.includes('route')) {
        components.address_line1 += component.long_name;
      } else if (types.includes('subpremise')) {
        components.address_line2 = component.long_name;
      } else if (types.includes('locality')) {
        components.locality = component.long_name;
      } else if (types.includes('administrative_area_level_1')) {
        components.administrative_area = component.short_name;
      } else if (types.includes('postal_code')) {
        components.postal_code = component.long_name;
      } else if (types.includes('country')) {
        components.country_code = component.short_name;
      }
    });

    components.address_line1 = components.address_line1.trim();
    
    if (!components.address_line1 && place.formatted_address) {
      const lines = place.formatted_address.split(',');
      components.address_line1 = lines[0] || place.formatted_address;
    }

    return components;
  }

  /**
   * Extracts address components from Apple Maps place.
   */
  function extractAppleAddressComponents(place) {
    return {
      name: place.name || '',
      address_line1: place.formattedAddressLines ? place.formattedAddressLines[0] || '' : '',
      address_line2: place.formattedAddressLines && place.formattedAddressLines[1] ? place.formattedAddressLines[1] : '',
      locality: place.locality || '',
      administrative_area: place.administrativeArea || '',
      postal_code: place.postalCode || '',
      country_code: place.countryCode || 'AU',
    };
  }

  /**
   * Populates venue name field if it exists.
   */
  function populateVenueName(form, venueName) {
    if (!venueName) return;
    
    // Find venue name field.
    const venueNameField = form.querySelector('input[name*="field_venue_name"]');
    if (venueNameField) {
      // Clean up venue name (remove long addresses).
      let cleanName = venueName;
      if (cleanName.includes(',') && cleanName.length > 50) {
        const parts = cleanName.split(',');
        cleanName = parts[0].trim();
      }
      venueNameField.value = cleanName;
      venueNameField.dispatchEvent(new Event('input', { bubbles: true }));
      venueNameField.dispatchEvent(new Event('change', { bubbles: true }));
      console.log('MyEventLane Location: Set venue name =', cleanName);
    }
  }

  /**
   * Populates address fields in the widget.
   * Works with standard Drupal address field type.
   */
  function populateAddressFields(widget, components, searchInput) {
    const form = (searchInput && searchInput.closest('form')) ||
                 widget.closest('form') ||
                 document.querySelector('form.node-event-form, form.node-event-edit-form, form[id*="node-event"], form#event-wizard-form');

    if (!form) {
      console.warn('MyEventLane Location: Form not found for address autocomplete');
      return;
    }
    
    // Debug: Log that we're trying to populate fields.
    console.log('MyEventLane Location: Populating address fields', components);
    
    // Find the location container to scope our search - prioritize data-mel-address attribute.
    const widgetContainer = form.querySelector('[data-mel-address="field_location"]');
    const locationContainer = widgetContainer || 
                              form.querySelector('.location-fields-container, .myeventlane-location-address-widget, .field--name-field-location') || 
                              form;
    
    console.log('MyEventLane Location: Widget container found:', !!widgetContainer);
    console.log('MyEventLane Location: Location container:', locationContainer);
    
    const findField = function(selectors, labelText) {
      // PRIORITY 1: Search within the widget container with data-mel-address attribute first.
      // This is the most reliable way to find wizard address fields.
      if (widgetContainer) {
        for (const selector of selectors) {
          try {
            const field = widgetContainer.querySelector(selector);
            if (field && (field.offsetParent !== null || field.type === 'hidden' || field.style.display === 'none')) {
              console.log('MyEventLane Location: Found field via widget container', selector, field.name);
              return field;
            }
          } catch (e) {
            // Invalid selector, skip
          }
        }
      }
      
      // PRIORITY 2: Try selectors scoped to location container.
      for (const selector of selectors) {
        try {
          let field = locationContainer.querySelector(selector);
          if (!field && locationContainer !== form) {
            // Fallback to form-wide search.
            field = form.querySelector(selector);
          }
          // Also try searching from searchInput parent if available.
          if (!field && searchInput) {
            const searchParent = searchInput.closest('fieldset, .form-item, .form-wrapper, [data-mel-address]') || searchInput.parentElement;
            if (searchParent) {
              field = searchParent.querySelector(selector);
            }
          }
          if (field && (field.offsetParent !== null || field.type === 'hidden')) {
            console.log('MyEventLane Location: Found field via selector', selector, field.name);
            return field;
          }
        } catch (e) {
          // Invalid selector, skip
          console.warn('MyEventLane Location: Invalid selector', selector, e);
        }
      }
      
      // PRIORITY 3: Try finding by name pattern matching (more aggressive search).
      if (labelText) {
        const namePatterns = {
          'suburb': ['locality'],
          'state': ['administrative_area', 'administrativeArea'],
          'postal code': ['postal_code', 'postalCode'],
          'street address': ['address_line1', 'addressLine1'],
        };
        
        const patterns = namePatterns[labelText.toLowerCase()] || [];
        for (const pattern of patterns) {
          // Search in widget container first if available.
          let searchScope = widgetContainer || locationContainer;
          let exactMatch = null;
          
          // Try widget container first.
          if (widgetContainer) {
            exactMatch = Array.from(widgetContainer.querySelectorAll('input, select')).find(el => {
              return el.name && (
                el.name.includes(pattern) || 
                el.name.endsWith('[' + pattern + ']') ||
                el.name.includes('[' + pattern + ']')
              );
            });
          }
          
          // Fallback to location container.
          if (!exactMatch) {
            exactMatch = Array.from(locationContainer.querySelectorAll('input, select')).find(el => {
              return el.name && (
                el.name.includes(pattern) || 
                el.name.endsWith('[' + pattern + ']') ||
                el.name.includes('[' + pattern + ']')
              );
            });
          }
          
          // Final fallback: search entire form.
          if (!exactMatch) {
            exactMatch = Array.from(form.querySelectorAll('input, select')).find(el => {
              return el.name && (
                el.name.includes(pattern) || 
                el.name.endsWith('[' + pattern + ']') ||
                el.name.includes('[' + pattern + ']')
              );
            });
          }
          
          if (exactMatch && (exactMatch.offsetParent !== null || exactMatch.type === 'hidden')) {
            console.log('MyEventLane Location: Found field via name pattern', pattern, exactMatch.name);
            return exactMatch;
          }
        }
      }
      
      // PRIORITY 4: Try finding by label text.
      if (labelText) {
        const searchScopes = [];
        if (widgetContainer) searchScopes.push(widgetContainer);
        searchScopes.push(locationContainer);
        searchScopes.push(form);
        
        for (const scope of searchScopes) {
          const labels = Array.from(scope.querySelectorAll('label'));
          for (const label of labels) {
            const labelTextLower = label.textContent.trim().toLowerCase();
            const searchTextLower = labelText.toLowerCase();
            if (labelTextLower.includes(searchTextLower) || searchTextLower.includes(labelTextLower)) {
              const inputId = label.getAttribute('for');
              if (inputId) {
                const field = scope.querySelector('#' + inputId) || form.querySelector('#' + inputId);
                if (field) {
                  console.log('MyEventLane Location: Found field via label', labelText, field.name);
                  return field;
                }
              }
              let field = label.parentElement.querySelector('input, select');
              if (!field) {
                let next = label.nextElementSibling;
                while (next && !field) {
                  field = next.querySelector('input, select');
                  if (!field) next = next.nextElementSibling;
                }
              }
              if (field) {
                console.log('MyEventLane Location: Found field via label (sibling)', labelText, field.name);
                return field;
              }
            }
          }
        }
      }
      
      console.warn('MyEventLane Location: Field not found for', labelText || selectors[0]);
      return null;
    };
    
    // Find address fields - prioritize wizard structure with data-mel-address attribute.
    // Standard Drupal address field structure: location_fields[field_location][0][address][component]
    const countryCode = findField([
      '[data-mel-address="field_location"] select[name*="country_code"]',
      'select[name="location_fields[field_location][0][address][country_code]"]',
      'select[name*="location_fields"][name*="field_location"][name*="country_code"]',
      '.location-fields-container select[name*="country_code"]',
      '.mel-wizard-step select[name*="country_code"]',
      '.myeventlane-location-address-widget select[name*="country_code"]',
    ], 'country');

    const administrativeArea = findField([
      '[data-mel-address="field_location"] select[name*="administrative_area"]',
      'select[name="location_fields[field_location][0][address][administrative_area]"]',
      'select[name*="location_fields"][name*="field_location"][name*="administrative_area"]',
      '.location-fields-container select[name*="administrative_area"]',
      '.mel-wizard-step select[name*="administrative_area"]',
      '.myeventlane-location-address-widget select[name*="administrative_area"]',
    ], 'state');

    const locality = findField([
      '[data-mel-address="field_location"] input[name*="locality"]',
      'input[name="location_fields[field_location][0][address][locality]"]',
      'input[name*="location_fields"][name*="field_location"][name*="locality"]',
      '.location-fields-container input[name*="locality"]',
      '.mel-wizard-step input[name*="locality"]',
      '.myeventlane-location-address-widget input[name*="locality"]',
    ], 'suburb');

    const postalCode = findField([
      '[data-mel-address="field_location"] input[name*="postal_code"]',
      'input[name="location_fields[field_location][0][address][postal_code]"]',
      'input[name*="location_fields"][name*="field_location"][name*="postal_code"]',
      '.location-fields-container input[name*="postal_code"]',
      '.mel-wizard-step input[name*="postal_code"]',
      '.myeventlane-location-address-widget input[name*="postal_code"]',
    ], 'postal code');

    const addressLine1 = findField([
      '[data-mel-address="field_location"] input[name*="address_line1"]',
      'input[name="location_fields[field_location][0][address][address_line1]"]',
      'input[name*="location_fields"][name*="field_location"][name*="address_line1"]',
      '.location-fields-container input[name*="address_line1"]',
      '.mel-wizard-step input[name*="address_line1"]',
      '.myeventlane-location-address-widget input[name*="address_line1"]',
    ], 'street address');

    const addressLine2 = findField([
      '[data-mel-address="field_location"] input[name*="address_line2"]',
      'input[name="location_fields[field_location][0][address][address_line2]"]',
      'input[name*="location_fields"][name*="field_location"][name*="address_line2"]',
      '.location-fields-container input[name*="address_line2"]',
    ], 'address line 2');

    const venueNameField = findField([
      'input[name="location_fields[field_venue_name]"]',
      'input[name*="field_venue_name"]',
      '.mel-wizard-step input[name*="field_venue_name"]'
    ], 'venue name');

    // CRITICAL: Set country_code FIRST, then wait for dependent state options.
    if (countryCode) {
      countryCode.value = components.country_code || 'AU';
      countryCode.dispatchEvent(new Event('input', { bubbles: true }));
      countryCode.dispatchEvent(new Event('change', { bubbles: true }));
      countryCode.dispatchEvent(new Event('blur', { bubbles: true }));
      console.log('MyEventLane Location: Set country_code =', countryCode.value, 'in field', countryCode.name);
    } else {
      console.warn('MyEventLane Location: Country code field not found');
    }

    // Wait briefly for state options to populate.
    setTimeout(() => {
      // Populate administrative_area with retry logic.
      if (administrativeArea) {
        const setAdministrativeArea = (retry = false) => {
          if (administrativeArea.tagName === 'SELECT') {
            const options = administrativeArea.options;
            let found = false;
            for (let i = 0; i < options.length; i++) {
              const opt = options[i];
              if (
                opt.value === components.administrative_area ||
                opt.text === components.administrative_area ||
                (components.administrative_area && opt.text.toLowerCase().includes(components.administrative_area.toLowerCase()))
              ) {
                administrativeArea.value = opt.value;
                found = true;
                break;
              }
            }
            if (!found && !retry && options.length <= 1) {
              // Options not ready yet, retry once.
              setTimeout(() => setAdministrativeArea(true), 200);
              return;
            }
          } else {
            administrativeArea.value = components.administrative_area || '';
          }
          administrativeArea.dispatchEvent(new Event('input', { bubbles: true }));
          administrativeArea.dispatchEvent(new Event('change', { bubbles: true }));
          administrativeArea.dispatchEvent(new Event('blur', { bubbles: true }));
          console.log('MyEventLane Location: Set administrative_area =', administrativeArea.value, 'in field', administrativeArea.name);
        };
        setAdministrativeArea();
      } else {
        console.warn('MyEventLane Location: Administrative area field not found');
      }

      // Populate other fields immediately.
      if (locality) {
        locality.value = components.locality || '';
        locality.dispatchEvent(new Event('input', { bubbles: true }));
        locality.dispatchEvent(new Event('change', { bubbles: true }));
        locality.dispatchEvent(new Event('blur', { bubbles: true }));
        console.log('MyEventLane Location: Set locality =', locality.value, 'in field', locality.name);
      } else {
        console.error('MyEventLane Location: CRITICAL - Locality field not found!');
      }

      if (postalCode) {
        postalCode.value = components.postal_code || '';
        postalCode.dispatchEvent(new Event('input', { bubbles: true }));
        postalCode.dispatchEvent(new Event('change', { bubbles: true }));
        postalCode.dispatchEvent(new Event('blur', { bubbles: true }));
        console.log('MyEventLane Location: Set postal_code =', postalCode.value, 'in field', postalCode.name);
      } else {
        console.error('MyEventLane Location: CRITICAL - Postal code field not found!');
      }

      if (addressLine1) {
        addressLine1.value = components.address_line1 || '';
        addressLine1.dispatchEvent(new Event('input', { bubbles: true }));
        addressLine1.dispatchEvent(new Event('change', { bubbles: true }));
        addressLine1.dispatchEvent(new Event('blur', { bubbles: true }));
        console.log('MyEventLane Location: Set address_line1 =', addressLine1.value, 'in field', addressLine1.name);
      } else {
        console.error('MyEventLane Location: CRITICAL - Address line 1 field not found!');
      }
      
      if (addressLine2 && components.address_line2) {
        addressLine2.value = components.address_line2 || '';
        addressLine2.dispatchEvent(new Event('input', { bubbles: true }));
        addressLine2.dispatchEvent(new Event('change', { bubbles: true }));
        addressLine2.dispatchEvent(new Event('blur', { bubbles: true }));
        console.log('MyEventLane Location: Set address_line2 =', addressLine2.value, 'in field', addressLine2.name);
      }
      
      // Final verification - check all critical fields have values and trigger form state update.
      setTimeout(() => {
        const criticalFields = {
          'address_line1': addressLine1,
          'locality': locality,
          'administrative_area': administrativeArea,
          'postal_code': postalCode
        };
        
        const missing = [];
        const empty = [];
        
        for (const [name, field] of Object.entries(criticalFields)) {
          if (!field) {
            missing.push(name);
          } else if (!field.value || field.value.trim() === '') {
            empty.push(name);
          }
        }
        
        if (missing.length > 0) {
          console.error('MyEventLane Location: CRITICAL FIELDS MISSING:', missing.join(', '));
        }
        if (empty.length > 0) {
          console.error('MyEventLane Location: CRITICAL FIELDS EMPTY:', empty.join(', '));
          console.error('MyEventLane Location: This will cause validation to fail!');
        }
        if (missing.length === 0 && empty.length === 0) {
          console.log('MyEventLane Location: SUCCESS - All critical fields populated!');
          
          // CRITICAL: Trigger form state update to ensure Drupal recognizes the values.
          if (typeof jQuery !== 'undefined' && form) {
            jQuery(form).trigger('formUpdated');
            // Also trigger on each field individually.
            Object.values(criticalFields).forEach(field => {
              if (field) {
                jQuery(field).trigger('formUpdated');
              }
            });
            console.log('MyEventLane Location: Triggered formUpdated events to update Drupal form state');
          }
        }
      }, 100);

      if (addressLine2 && components.address_line2) {
        addressLine2.value = components.address_line2;
        addressLine2.dispatchEvent(new Event('input', { bubbles: true }));
        addressLine2.dispatchEvent(new Event('change', { bubbles: true }));
        addressLine2.dispatchEvent(new Event('blur', { bubbles: true }));
      }

      if (venueNameField && components.name) {
        let venueName = components.name;
        if (venueName.includes(',') && venueName.length > 50) {
          const parts = venueName.split(',');
          venueName = parts[0].trim();
        }
        venueNameField.value = venueName;
        venueNameField.dispatchEvent(new Event('input', { bubbles: true }));
        venueNameField.dispatchEvent(new Event('change', { bubbles: true }));
        venueNameField.dispatchEvent(new Event('blur', { bubbles: true }));
      }
      
      // Summary log of what was populated.
      const populatedFields = [];
      if (addressLine1 && addressLine1.value) populatedFields.push('address_line1');
      if (locality && locality.value) populatedFields.push('locality');
      if (administrativeArea && administrativeArea.value) populatedFields.push('administrative_area');
      if (postalCode && postalCode.value) populatedFields.push('postal_code');
      if (addressLine2 && addressLine2.value) populatedFields.push('address_line2');
      
      const missingFields = [];
      if (!addressLine1) missingFields.push('address_line1');
      if (!locality) missingFields.push('locality');
      if (!administrativeArea) missingFields.push('administrative_area');
      if (!postalCode) missingFields.push('postal_code');
      
      console.log('MyEventLane Location: Address population summary - Populated:', populatedFields.join(', '));
      if (missingFields.length > 0) {
        console.warn('MyEventLane Location: Missing fields:', missingFields.join(', '));
        // Log all available fields for debugging.
        const widgetContainer = form.querySelector('[data-mel-address="field_location"]');
        const searchScope = widgetContainer || locationContainer;
        const allFields = Array.from(searchScope.querySelectorAll('input, select'));
        console.log('MyEventLane Location: All available fields in scope:', allFields.map(el => ({
          name: el.name,
          id: el.id,
          type: el.type,
          value: el.value,
          visible: el.offsetParent !== null
        })));
      }
    }, 100);
  }

  /**
   * Sets latitude and longitude in hidden fields.
   */
  function setCoordinates(widget, lat, lng, searchInput) {
    const form = searchInput ? searchInput.closest('form') : (widget.closest('form') || document.querySelector('form'));
    if (form) {
      let eventLatField = form.querySelector('input.myeventlane-location-latitude-field[type="hidden"]');
      let eventLngField = form.querySelector('input.myeventlane-location-longitude-field[type="hidden"]');

      if (!eventLatField) {
        eventLatField = form.querySelector('.myeventlane-location-latitude[type="hidden"]');
      }
      if (!eventLngField) {
        eventLngField = form.querySelector('.myeventlane-location-longitude[type="hidden"]');
      }
      
      if (!eventLatField) {
        const hiddenLatFields = form.querySelectorAll('input[type="hidden"][name*="field_location_latitude"], input[type="hidden"][name*="field_event_lat"]');
        if (hiddenLatFields.length > 0) {
          eventLatField = hiddenLatFields[0];
        }
      }
      if (!eventLngField) {
        const hiddenLngFields = form.querySelectorAll('input[type="hidden"][name*="field_location_longitude"], input[type="hidden"][name*="field_event_lng"]');
        if (hiddenLngFields.length > 0) {
          eventLngField = hiddenLngFields[0];
        }
      }
      
      const wizardContent = form.querySelector('.mel-event-form__wizard-content, .mel-wizard-step, .mel-event-form__section');
      if (wizardContent) {
        if (!eventLatField) {
          eventLatField = wizardContent.querySelector('input[type="hidden"].myeventlane-location-latitude-field, input[type="hidden"][name*="field_location_latitude"]');
        }
        if (!eventLngField) {
          eventLngField = wizardContent.querySelector('input[type="hidden"].myeventlane-location-longitude-field, input[type="hidden"][name*="field_location_longitude"]');
        }
      }
      
      if (eventLatField) {
        const formattedLat = parseFloat(lat.toFixed(7));
        eventLatField.value = formattedLat.toString();
        eventLatField.dispatchEvent(new Event('input', { bubbles: true }));
        eventLatField.dispatchEvent(new Event('change', { bubbles: true }));
      }
      if (eventLngField) {
        const formattedLng = parseFloat(lng.toFixed(7));
        eventLngField.value = formattedLng.toString();
        eventLngField.dispatchEvent(new Event('input', { bubbles: true }));
        eventLngField.dispatchEvent(new Event('change', { bubbles: true }));
      }
    }
  }

  /**
   * Sets Place ID in hidden field.
   */
  function setPlaceId(widget, placeId, searchInput) {
    const form = searchInput ? searchInput.closest('form') : (widget.closest('form') || document.querySelector('form'));
    if (!form || !placeId) {
      return;
    }

    // Find Place ID field - try multiple selectors.
    let placeIdField = form.querySelector('input.mel-place-id[type="hidden"]');
    
    if (!placeIdField) {
      placeIdField = form.querySelector('input[type="hidden"][name*="field_location_place_id"]');
    }
    
    if (!placeIdField) {
      // Try scoped to location fields container.
      const locationContainer = form.querySelector('[data-mel-address="field_location"], .location-fields-container, .mel-wizard-step');
      if (locationContainer) {
        placeIdField = locationContainer.querySelector('input[type="hidden"][name*="field_location_place_id"]');
      }
    }
    
    if (!placeIdField) {
      // Try wizard content areas.
      const wizardContent = form.querySelector('.mel-event-form__wizard-content, .mel-wizard-step, .mel-event-form__section, .location-fields-container');
      if (wizardContent) {
        placeIdField = wizardContent.querySelector('input[type="hidden"][name*="field_location_place_id"], input.mel-place-id[type="hidden"]');
      }
    }
    
    if (placeIdField) {
      placeIdField.value = placeId;
      placeIdField.dispatchEvent(new Event('input', { bubbles: true }));
      placeIdField.dispatchEvent(new Event('change', { bubbles: true }));
      console.log('MyEventLane Location: Set place_id =', placeId, 'in field', placeIdField.name);
    } else {
      console.warn('MyEventLane Location: Place ID field not found');
    }
  }

  /**
   * Shows a map preview with a marker.
   */
  /**
   * Shows a map preview of the selected location.
   * Gracefully skips if container doesn't exist (e.g., in wizard forms).
   */
  function showMapPreview(widget, lat, lng, provider, searchInput) {
    const form = searchInput ? searchInput.closest('form') : (widget.closest('form') || document.querySelector('form'));
    if (!form) {
      console.log('MyEventLane Location: showMapPreview - no form found');
      return;
    }
    
    let previewContainer = form.querySelector('.myeventlane-location-map-preview');
    
    if (!previewContainer) {
      const wizardContent = form.querySelector('.mel-event-form__wizard-content, .mel-wizard-step, .mel-event-form__section, .mel-event-wizard__content, .location-fields-container');
      if (wizardContent) {
        previewContainer = wizardContent.querySelector('.myeventlane-location-map-preview');
      }
    }
    
    if (!previewContainer && searchInput) {
      const searchContainer = searchInput.closest('[data-mel-address="field_location"], .field--name-field-location, fieldset, .myeventlane-location-address-widget, .form-item');
      if (searchContainer) {
        previewContainer = searchContainer.querySelector('.myeventlane-location-map-preview');
      }
    }
    
    // If no preview container exists (e.g., in wizard forms), skip map preview.
    // This is fine - the address autocomplete still works without the preview.
    if (!previewContainer) {
      console.log('MyEventLane Location: showMapPreview - no preview container found, skipping (this is OK for wizard forms)');
      return;
    }

    // Only show map if container exists and has dimensions.
    if (previewContainer.offsetWidth === 0 || previewContainer.offsetHeight === 0) {
      console.log('MyEventLane Location: showMapPreview - container has no dimensions, skipping');
      return;
    }

    try {
      previewContainer.style.display = 'block';

      if (provider === 'google_maps') {
        if (typeof google !== 'undefined' && google.maps && google.maps.Map) {
          const map = new google.maps.Map(previewContainer, {
            center: { lat: lat, lng: lng },
            zoom: 15,
            mapTypeControl: false,
            streetViewControl: false,
          });
          new google.maps.Marker({
            position: { lat: lat, lng: lng },
            map: map,
          });
          console.log('MyEventLane Location: Map preview displayed');
        } else {
          console.warn('MyEventLane Location: Google Maps API not fully loaded');
        }
      } else if (provider === 'apple_maps') {
        if (typeof mapkit !== 'undefined' && mapkit.Map) {
          const map = new mapkit.Map(previewContainer);
          const coordinate = new mapkit.Coordinate(lat, lng);
          map.region = new mapkit.CoordinateRegion(coordinate, new mapkit.CoordinateSpan(0.01, 0.01));
          const marker = new mapkit.MarkerAnnotation(coordinate);
          map.addAnnotation(marker);
          console.log('MyEventLane Location: Apple Maps preview displayed');
        } else {
          console.warn('MyEventLane Location: MapKit not fully loaded');
        }
      }
    } catch (error) {
      console.error('MyEventLane Location: Error displaying map preview:', error);
      // Don't let map preview errors break address autocomplete.
    }
  }

  // Initialize on DOM ready, but ONLY on event forms.
  function initialize() {
    console.log('MyEventLane Location: Initialize function called');
    const isEventForm = document.querySelector('.myeventlane-location-address-search') ||
                        document.querySelector('form.node-event-form, form.node-event-edit-form, form[id*="node-event"]') ||
                        document.querySelector('form.mel-event-form-vendor') ||
                        document.querySelector('.mel-event-form--wizard') ||
                        document.querySelector('form#event-wizard-form, form[id="event-wizard-form"], form[id*="event_wizard"]') ||
                        document.querySelector('.mel-event-wizard') ||
                        document.querySelector('.mel-event-wizard__content');
    
    console.log('MyEventLane Location: Is event form?', !!isEventForm);
    
    if (!isEventForm) {
      return;
    }
    
    // Check if this is a wizard form - if so, wait for location step.
    const isWizard = document.querySelector('.mel-event-wizard, form#event-wizard-form, form[id*="event_wizard"]');
    
    if (isWizard) {
      console.log('MyEventLane Location: Wizard form detected, will initialize when location step is visible');
      
      // Set up a MutationObserver to watch for the field appearing.
      const wizardObserver = new MutationObserver(function(mutations) {
        const searchField = document.querySelector('.myeventlane-location-address-search, input[name*="field_location_address_search"]');
        if (searchField && searchField.offsetParent !== null) {
          const form = searchField.closest('form');
          if (form && !initializedForms.has(form)) {
            console.log('MyEventLane Location: Location step visible in wizard, initializing');
            wizardObserver.disconnect();
            initAddressAutocomplete();
          }
        }
      });
      
      // Observe the entire document for wizard step changes.
      wizardObserver.observe(document.body, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ['style', 'class']
      });
      
      // Also try immediately in case we're already on the location step.
      setTimeout(function() {
        const searchField = document.querySelector('.myeventlane-location-address-search, input[name*="field_location_address_search"]');
        if (searchField && searchField.offsetParent !== null) {
          const form = searchField.closest('form');
          if (form && !initializedForms.has(form)) {
            console.log('MyEventLane Location: Location step already visible, initializing');
            wizardObserver.disconnect();
            initAddressAutocomplete();
          }
        }
      }, 100);
    } else {
      // For non-wizard forms, initialize immediately.
      console.log('MyEventLane Location: Non-wizard form, calling initAddressAutocomplete in 300ms');
      setTimeout(initAddressAutocomplete, 300);
    }
  }

  console.log('MyEventLane Location: Script loaded, document readyState:', document.readyState);
  console.log('MyEventLane Location: Drupal available?', typeof Drupal !== 'undefined');
  console.log('MyEventLane Location: drupalSettings available?', typeof drupalSettings !== 'undefined');
  
  // Initialize immediately if document is ready, otherwise wait.
  if (document.readyState === 'loading') {
    console.log('MyEventLane Location: Adding DOMContentLoaded listener');
    document.addEventListener('DOMContentLoaded', function() {
      console.log('MyEventLane Location: DOMContentLoaded fired');
      initialize();
    });
  } else {
    console.log('MyEventLane Location: Document already ready, calling initialize');
    // Use setTimeout to ensure DOM is fully ready.
    setTimeout(initialize, 100);
  }
  
  // Use Drupal behaviors for proper integration (especially for AJAX).
  if (typeof Drupal !== 'undefined' && Drupal.behaviors) {
    console.log('MyEventLane Location: Registering Drupal behavior');
    Drupal.behaviors.myeventlaneLocationAutocomplete = {
      attach: function (context, settings) {
        console.log('MyEventLane Location: Behavior attach called', context, 'settings:', settings);
        
        // Check if we're in an event form context.
        const isEventForm = context.querySelector('.myeventlane-location-address-search') ||
                            context.querySelector('form.node-event-form, form.node-event-edit-form, form[id*="node-event"]') ||
                            context.querySelector('form.mel-event-form-vendor') ||
                            context.querySelector('.mel-event-form--wizard') ||
                            context.querySelector('form#event-wizard-form, form[id="event-wizard-form"], form[id*="event_wizard"]') ||
                            context.querySelector('.mel-event-wizard') ||
                            context.querySelector('.mel-event-wizard__content') ||
                            context.querySelector('.location-fields-container');
        
        // Also check if the full document contains wizard elements (for initial page load).
        const hasWizard = document.querySelector('.mel-event-wizard') || 
                         document.querySelector('form#event-wizard-form, form[id="event-wizard-form"]') ||
                         document.querySelector('.mel-event-wizard__content');
        
        console.log('MyEventLane Location: Behavior - Is event form?', !!isEventForm, 'Has wizard?', !!hasWizard, 'context === document?', context === document);
        
        // Initialize if we're in an event form context, or if we're on the full document and there's a wizard.
        if (isEventForm || (context === document && hasWizard)) {
          console.log('MyEventLane Location: Behavior - Calling initAddressAutocomplete in 200ms');
          setTimeout(function() {
            // Clear the initialized forms set for this context to allow re-initialization after AJAX.
            // Only clear if we're dealing with a specific context (not the full document).
            if (context !== document) {
              const form = context.closest('form') || context.querySelector('form');
              if (form && initializedForms.has(form)) {
                console.log('MyEventLane Location: Clearing initialization flag for form to allow re-init');
                initializedForms.delete(form);
              }
            }
            initAddressAutocomplete();
          }, 200);
        }
      }
    };
  } else {
    console.warn('MyEventLane Location: Drupal behaviors not available, using fallback initialization');
    // Fallback: try to initialize after a delay if Drupal behaviors aren't available.
    setTimeout(function() {
      if (document.querySelector('.myeventlane-location-address-search')) {
        console.log('MyEventLane Location: Fallback initialization - search input found');
        initialize();
      }
    }, 1000);
  }
  
  // CRITICAL: Before form submit, ensure all hidden address fields have values.
  // This ensures validation passes even if form state hasn't updated yet.
  document.addEventListener('submit', function(e) {
    const form = e.target;
    if (!form || form.tagName !== 'FORM') return;
    
    // Check if this is an event wizard form.
    const isWizardForm = form.id === 'event-wizard-form' || 
                       form.classList.contains('mel-event-wizard') ||
                       form.querySelector('.myeventlane-location-address-search');
    
    if (!isWizardForm) return;
    
    // Find all hidden address fields and verify they have values.
    const widgetContainer = form.querySelector('[data-mel-address="field_location"]');
    if (!widgetContainer) return;
    
    const addressFields = {
      address_line1: widgetContainer.querySelector('input[data-address-component="address_line1"]'),
      locality: widgetContainer.querySelector('input[data-address-component="locality"]'),
      administrative_area: widgetContainer.querySelector('input[data-address-component="administrative_area"], select[data-address-component="administrative_area"]'),
      postal_code: widgetContainer.querySelector('input[data-address-component="postal_code"]'),
    };
    
    // Check if any critical fields are empty.
    const emptyFields = [];
    for (const [name, field] of Object.entries(addressFields)) {
      if (field && (!field.value || field.value.trim() === '')) {
        emptyFields.push(name);
      }
    }
    
    if (emptyFields.length > 0) {
      console.warn('MyEventLane Location: Form submit detected with empty address fields:', emptyFields);
      console.warn('MyEventLane Location: This may cause validation to fail.');
      // Don't prevent submit - let validation handle it, but log for debugging.
    } else {
      console.log('MyEventLane Location: Form submit - all address fields have values');
    }
  }, true); // Use capture phase to run before form validation.

  // Re-initialize after AJAX (for wizard step changes).
  if (typeof Drupal !== 'undefined' && Drupal.ajax) {
    // Listen for AJAX completion to re-initialize when steps change.
    document.addEventListener('ajaxSuccess', function(event) {
      // Small delay to ensure DOM is updated.
      setTimeout(function() {
        // Check if location step is now visible.
        const locationStep = document.querySelector('.mel-wizard-step, .location-fields-container, [data-mel-address="field_location"]');
        const searchField = document.querySelector('.myeventlane-location-address-search, input[name*="field_location_address_search"]');
        if (locationStep && searchField && searchField.offsetParent !== null) {
          console.log('MyEventLane Location: Location step visible after AJAX, re-initializing');
          // Only initialize if not already initialized for this form.
          const form = searchField.closest('form');
          if (form && !initializedForms.has(form)) {
            initAddressAutocomplete();
          }
        }
      }, 200);
    });
    
    // Also listen for AJAX commands (more reliable for Drupal).
    if (Drupal.ajax && Drupal.ajax.prototype) {
      const originalBeforeSend = Drupal.ajax.prototype.beforeSend;
      Drupal.ajax.prototype.beforeSend = function(xmlhttprequest, options) {
        if (originalBeforeSend) {
          originalBeforeSend.call(this, xmlhttprequest, options);
        }
      };
      
      const originalSuccess = Drupal.ajax.prototype.success;
      Drupal.ajax.prototype.success = function(response, status) {
        if (originalSuccess) {
          originalSuccess.call(this, response, status);
        }
        // Re-initialize after AJAX success.
        setTimeout(function() {
          const searchField = document.querySelector('.myeventlane-location-address-search, input[name*="field_location_address_search"]');
          if (searchField && searchField.offsetParent !== null) {
            const form = searchField.closest('form');
            if (form && !initializedForms.has(form)) {
              console.log('MyEventLane Location: Re-initializing after AJAX success');
              initAddressAutocomplete();
            }
          }
        }, 300);
      };
    }
  }
  
  // Also re-initialize after AJAX (for wizard step changes) - legacy approach.
  if (typeof Drupal !== 'undefined' && Drupal.ajax) {
    // Use once() to avoid multiple wrappers if script loads multiple times.
    if (!Drupal.ajax.prototype._myeventlaneLocationWrapped) {
      const originalSuccess = Drupal.ajax.prototype.success;
      Drupal.ajax.prototype.success = function(response, status) {
        if (originalSuccess) {
          originalSuccess.call(this, response, status);
        }
        // Re-initialize address autocomplete after AJAX completes (for wizard steps).
        console.log('MyEventLane Location: AJAX success, re-initializing');
        // Clear initialization flags for forms in the response.
        if (response && response.commands) {
          response.commands.forEach(function(cmd) {
            if (cmd.selector) {
              const elements = document.querySelectorAll(cmd.selector);
              elements.forEach(function(el) {
                const form = el.closest('form');
                if (form && initializedForms.has(form)) {
                  console.log('MyEventLane Location: Clearing initialization flag after AJAX');
                  initializedForms.delete(form);
                }
              });
            }
          });
        }
        setTimeout(function() {
          initAddressAutocomplete();
        }, 300);
      };
      Drupal.ajax.prototype._myeventlaneLocationWrapped = true;
    }
  }

})(Drupal || {}, drupalSettings || {});

// Log AFTER IIFE to confirm it completed
console.log('MyEventLane Location: IIFE completed execution');
