/**
 * @file
 * MyEventLane Location autocomplete (Google Places / Apple MapKit).
 *
 * Goals:
 * - Drupal-behaviors compatible (AJAX-safe).
 * - No prototype patching / no duplicate listeners.
 * - No invalid selectors (e.g. :has()).
 * - Populate Drupal Address field widgets reliably.
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  const SETTINGS = (drupalSettings && drupalSettings.myeventlaneLocation) ? drupalSettings.myeventlaneLocation : {};
  const DEBUG = !!SETTINGS.debug;

  function log(...args) {
    if (DEBUG && window.console) console.log('[MEL Location]', ...args);
  }
  function warn(...args) {
    if (window.console) console.warn('[MEL Location]', ...args);
  }
  function error(...args) {
    if (window.console) console.error('[MEL Location]', ...args);
  }

  /**
   * ------------------------------------------------------------------------
   * Utilities
   * ------------------------------------------------------------------------
   */

  function isVisible(el) {
    return !!(el && (el.offsetParent !== null || el.getClientRects().length));
  }

  function closestForm(el) {
    return el ? el.closest('form') : null;
  }

  /**
   * Return the best "widget root" for field_location.
   *
   * Priority:
   * 1) Explicit wrapper: [data-mel-address="field_location"]
   * 2) Drupal field wrapper: .field--name-field-location
   * 3) Closest fieldset
   * 4) Form fallback
   */
  function getLocationWidgetRoot(form) {
    if (!form) return null;

    let root = form.querySelector('[data-mel-address="field_location"]');
    if (root) return root;

    root = form.querySelector('.field--name-field-location');
    if (root) return root;

    // Fieldset containing address inputs.
    const fieldsets = form.querySelectorAll('fieldset');
    for (const fs of fieldsets) {
      if (fs.querySelector('input[name*="[address][address_line1]"]') ||
          fs.querySelector('input[name*="[address][locality]"]') ||
          fs.querySelector('input[name*="[address][postal_code]"]')) {
        return fs;
      }
    }

    return form;
  }

  /**
   * Finds the search input that the vendor types into.
   * Supports:
   * - .myeventlane-location-address-search
   * - input[data-address-search="true"]
   * - name contains field_location_address_search
   */
  function findSearchInput(context) {
    if (!context) return null;

    let input = context.querySelector('.myeventlane-location-address-search');
    if (input) return input;

    input = context.querySelector('input[data-address-search="true"]');
    if (input) return input;

    input = context.querySelector('input[name*="field_location_address_search"], input[name*="address_search"]');
    if (input) return input;

    return null;
  }

  /**
   * Safely set a field value and notify Drupal.
   */
  function setFieldValue(field, value) {
    if (!field) return;
    field.value = value;

    // Ensure Drupal states & widgets react.
    field.dispatchEvent(new Event('input', { bubbles: true }));
    field.dispatchEvent(new Event('change', { bubbles: true }));
    field.dispatchEvent(new Event('blur', { bubbles: true }));
  }

  /**
   * Attempt to find address component field by name patterns within widget root.
   */
  function findAddressComponent(widgetRoot, componentName, allowSelect = true) {
    if (!widgetRoot) return null;

    // Standard Drupal address naming:
    // ...[address][address_line1]
    // ...[address][locality]
    // ...[address][administrative_area]
    // ...[address][postal_code]
    // ...[address][country_code]
    const baseSelector = `[name*="[address][${componentName}]"]`;
    let field = widgetRoot.querySelector(`input${baseSelector}`);

    if (!field && allowSelect) {
      field = widgetRoot.querySelector(`select${baseSelector}`);
    }
    if (field) return field;

    // Fallback patterns (some widgets differ slightly):
    field = widgetRoot.querySelector(`input[name*="${componentName}"]`) ||
            (allowSelect ? widgetRoot.querySelector(`select[name*="${componentName}"]`) : null);

    return field || null;
  }

  /**
   * Attempt to find lat/lng hidden fields by common MEL patterns.
   */
  function findLatLngFields(form, widgetRoot) {
    const scope = widgetRoot || form || document;

    // Prefer explicit classes.
    let lat = scope.querySelector('input.myeventlane-location-latitude-field[type="hidden"], input.myeventlane-location-latitude[type="hidden"]');
    let lng = scope.querySelector('input.myeventlane-location-longitude-field[type="hidden"], input.myeventlane-location-longitude[type="hidden"]');

    // Fallback by name patterns.
    if (!lat) lat = scope.querySelector('input[type="hidden"][name*="field_location_latitude"], input[type="hidden"][name*="field_event_lat"], input[type="hidden"][name*="latitude"]');
    if (!lng) lng = scope.querySelector('input[type="hidden"][name*="field_location_longitude"], input[type="hidden"][name*="field_event_lng"], input[type="hidden"][name*="longitude"]');

    // If still missing, try whole form.
    if (form) {
      if (!lat) lat = form.querySelector('input[type="hidden"][name*="latitude"]');
      if (!lng) lng = form.querySelector('input[type="hidden"][name*="longitude"]');
    }

    return { lat, lng };
  }

  /**
   * Place ID hidden field (optional).
   */
  function findPlaceIdField(form, widgetRoot) {
    const scope = widgetRoot || form || document;

    let f = scope.querySelector('input.mel-place-id[type="hidden"]');
    if (f) return f;

    f = scope.querySelector('input[type="hidden"][name*="field_location_place_id"], input[type="hidden"][name*="place_id"]');
    if (f) return f;

    if (form) {
      f = form.querySelector('input[type="hidden"][name*="field_location_place_id"], input[type="hidden"][name*="place_id"]');
    }

    return f || null;
  }

  /**
   * Venue name field (optional).
   * Supports both field_venue_name and the new venue_name field in wizard.
   */
  function findVenueNameField(form) {
    if (!form) return null;
    // Try wizard venue name field first.
    let field = form.querySelector('input.myeventlane-venue-name-field');
    if (field) return field;
    // Fallback to field_venue_name.
    return form.querySelector('input[name*="field_venue_name"]') || null;
  }

  /**
   * Normalize AU state values to short codes if needed.
   */
  function normalizeAUState(value) {
    if (!value) return '';
    const v = value.trim();

    // Already a short code.
    const short = ['NSW','VIC','QLD','SA','WA','TAS','ACT','NT'];
    if (short.includes(v.toUpperCase())) return v.toUpperCase();

    // Map common full names.
    const map = {
      'new south wales': 'NSW',
      'victoria': 'VIC',
      'queensland': 'QLD',
      'south australia': 'SA',
      'western australia': 'WA',
      'tasmania': 'TAS',
      'australian capital territory': 'ACT',
      'northern territory': 'NT',
    };
    const key = v.toLowerCase();
    return map[key] || v;
  }

  /**
   * Populate Drupal Address widget fields.
   */
  function populateAddressWidget(form, widgetRoot, components) {
    if (!form || !widgetRoot || !components) return;

    const country = findAddressComponent(widgetRoot, 'country_code', true);
    const state = findAddressComponent(widgetRoot, 'administrative_area', true);
    const suburb = findAddressComponent(widgetRoot, 'locality', false);
    const postcode = findAddressComponent(widgetRoot, 'postal_code', false);
    const line1 = findAddressComponent(widgetRoot, 'address_line1', false);
    const line2 = findAddressComponent(widgetRoot, 'address_line2', false);

    // Country first (drives dynamic state list in many configs).
    // Default to AU if empty or not provided.
    if (country) {
      const countryValue = components.country_code || 'AU';
      setFieldValue(country, countryValue);
    }

    // line1
    if (line1) setFieldValue(line1, components.address_line1 || '');

    // line2 optional
    if (line2 && components.address_line2) setFieldValue(line2, components.address_line2);

    // suburb + postcode
    if (suburb) setFieldValue(suburb, components.locality || '');
    if (postcode) setFieldValue(postcode, components.postal_code || '');

    // state: if select, prefer matching option.
    if (state) {
      const desired = normalizeAUState(components.administrative_area || '');
      if (state.tagName === 'SELECT') {
        // Try direct match, else match label.
        let matched = false;
        for (const opt of state.options) {
          if (opt.value === desired || opt.text === desired) {
            state.value = opt.value;
            matched = true;
            break;
          }
        }
        if (!matched && desired) {
          for (const opt of state.options) {
            if ((opt.text || '').toUpperCase().includes(desired.toUpperCase())) {
              state.value = opt.value;
              matched = true;
              break;
            }
          }
        }
        state.dispatchEvent(new Event('change', { bubbles: true }));
        state.dispatchEvent(new Event('input', { bubbles: true }));
      }
      else {
        setFieldValue(state, desired);
      }
    }

    // Trigger Drupal formUpdated if jQuery exists, otherwise input/change is enough.
    if (window.jQuery) {
      window.jQuery(form).trigger('formUpdated');
    }

    // Hard verify log (debug only).
    log('Populated address:', {
      address_line1: line1 ? line1.value : null,
      locality: suburb ? suburb.value : null,
      administrative_area: state ? state.value : null,
      postal_code: postcode ? postcode.value : null,
      country_code: country ? country.value : null,
    });
  }

  /**
   * Populate venue name (optional).
   * 
   * @param {HTMLFormElement} form
   * @param {string} name - Place/venue name from autocomplete
   */
  function populateVenueName(form, name) {
    if (!form || !name) return;
    const field = findVenueNameField(form);
    if (!field) {
      log('Venue name field not found in form');
      return;
    }

    let clean = String(name).trim();
    // Trim at first comma if very long.
    if (clean.includes(',')) {
      clean = clean.split(',')[0].trim();
    }
    setFieldValue(field, clean);
    log('Populated venue name:', clean);
  }

  /**
   * Set coordinates (optional).
   */
  function populateLatLng(form, widgetRoot, lat, lng) {
    const fields = findLatLngFields(form, widgetRoot);
    if (fields.lat) setFieldValue(fields.lat, String(Number(lat).toFixed(7)));
    if (fields.lng) setFieldValue(fields.lng, String(Number(lng).toFixed(7)));
  }

  /**
   * Set Place ID (Google only).
   */
  function populatePlaceId(form, widgetRoot, placeId) {
    if (!placeId) return;
    const f = findPlaceIdField(form, widgetRoot);
    if (!f) return;
    setFieldValue(f, placeId);
  }

  /**
   * ------------------------------------------------------------------------
   * Google Maps loader + autocomplete
   * ------------------------------------------------------------------------
   */

  let googleMapsPromise = null;

  function loadGoogleMapsPlaces(apiKey) {
    if (!apiKey) {
      return Promise.reject(new Error('Google Maps API key missing.'));
    }

    // Already available.
    if (window.google && window.google.maps && window.google.maps.places) {
      return Promise.resolve(window.google);
    }

    // Already loading.
    if (googleMapsPromise) {
      return googleMapsPromise;
    }

    googleMapsPromise = new Promise((resolve, reject) => {
      // If a script already exists, wait for it.
      const existing = document.querySelector('script[src*="maps.googleapis.com/maps/api/js"]');
      if (existing) {
        const start = Date.now();
        const timer = setInterval(() => {
          if (window.google && window.google.maps && window.google.maps.places) {
            clearInterval(timer);
            resolve(window.google);
          } else if (Date.now() - start > 10000) {
            clearInterval(timer);
            reject(new Error('Google Maps API did not become available (existing script).'));
          }
        }, 100);
        return;
      }

      const cb = '__melGoogleMapsReady__' + Date.now();
      window[cb] = () => {
        delete window[cb];
        if (window.google && window.google.maps && window.google.maps.places) {
          resolve(window.google);
        } else {
          reject(new Error('Google Maps callback fired but Places API missing.'));
        }
      };

      const script = document.createElement('script');
      script.async = true;
      script.defer = true;
      script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(apiKey)}&libraries=places&callback=${encodeURIComponent(cb)}`;
      script.onerror = () => {
        delete window[cb];
        reject(new Error('Failed to load Google Maps script.'));
      };

      document.head.appendChild(script);
    });

    return googleMapsPromise;
  }

  function extractGoogleComponents(place) {
    const out = {
      name: place && place.name ? place.name : '',
      address_line1: '',
      address_line2: '',
      locality: '',
      administrative_area: '',
      postal_code: '',
      country_code: 'AU',
    };

    if (!place) return out;

    const comps = place.address_components || [];
    for (const c of comps) {
      const types = c.types || [];
      if (types.includes('street_number')) out.address_line1 = (c.long_name || '') + ' ' + out.address_line1;
      if (types.includes('route')) out.address_line1 = (out.address_line1 || '') + (c.long_name || '');
      if (types.includes('subpremise')) out.address_line2 = c.long_name || '';
      if (types.includes('locality')) out.locality = c.long_name || '';
      if (types.includes('administrative_area_level_1')) out.administrative_area = c.short_name || c.long_name || '';
      if (types.includes('postal_code')) out.postal_code = c.long_name || '';
      if (types.includes('country')) out.country_code = c.short_name || 'AU';
    }

    out.address_line1 = String(out.address_line1 || '').trim();
    if (!out.address_line1 && place.formatted_address) {
      out.address_line1 = String(place.formatted_address).split(',')[0].trim();
    }

    return out;
  }

  function setupGoogleAutocomplete(searchInput, form, widgetRoot) {
    const apiKey = SETTINGS.google_maps_api_key;
    if (!apiKey) {
      error('Google provider selected but SETTINGS.google_maps_api_key missing.');
      return;
    }

    loadGoogleMapsPlaces(apiKey)
      .then((google) => {
        if (!searchInput || !form) return;

        // Avoid double-instantiating on the same input.
        if (searchInput.dataset.melAutocompleteAttached === '1') {
          return;
        }
        searchInput.dataset.melAutocompleteAttached = '1';

        log('Google Places available, attaching autocomplete', searchInput);

        const autocomplete = new google.maps.places.Autocomplete(searchInput, {
          types: ['establishment', 'geocode'],
          componentRestrictions: { country: 'au' },
        });

        autocomplete.addListener('place_changed', () => {
          const place = autocomplete.getPlace();
          if (!place || !place.geometry) {
            warn('Selected place missing geometry. Place:', place);
            return;
          }

          const comps = extractGoogleComponents(place);
          const lat = place.geometry.location.lat();
          const lng = place.geometry.location.lng();

          populateAddressWidget(form, widgetRoot, comps);
          // Populate venue name from place.name (not comps.name, which may be empty).
          populateVenueName(form, place.name || comps.name || '');
          populateLatLng(form, widgetRoot, lat, lng);
          populatePlaceId(form, widgetRoot, place.place_id || '');

          // Dispatch custom event for venue-selection.js to handle venue-specific logic
          const placeSelectedEvent = new CustomEvent('place_selected', {
            detail: {
              place: place,
              provider: 'google_maps',
              components: comps,
              lat: lat,
              lng: lng,
            },
            bubbles: true,
          });
          searchInput.dispatchEvent(placeSelectedEvent);

          // Optional: map preview container can be handled elsewhere; do not block.
          log('Google place selected + populated', { comps, lat, lng, place_id: place.place_id, place_name: place.name });
        });
      })
      .catch((e) => {
        error(e);
      });
  }

  /**
   * ------------------------------------------------------------------------
   * Apple Maps (MapKit) - optional
   * ------------------------------------------------------------------------
   */

  let appleMapKitPromise = null;

  function loadAppleMapKit(token) {
    if (!token) {
      return Promise.reject(new Error('Apple Maps token missing.'));
    }

    if (window.mapkit && window.mapkit.Map) {
      return Promise.resolve(window.mapkit);
    }

    if (appleMapKitPromise) return appleMapKitPromise;

    appleMapKitPromise = new Promise((resolve, reject) => {
      const existing = document.querySelector('script[src*="apple-mapkit.com/mk/"]');
      if (existing) {
        const start = Date.now();
        const timer = setInterval(() => {
          if (window.mapkit && window.mapkit.Map) {
            clearInterval(timer);
            try {
              window.mapkit.init({
                authorizationCallback: (done) => done(token),
              });
            } catch (e) {}
            resolve(window.mapkit);
          } else if (Date.now() - start > 10000) {
            clearInterval(timer);
            reject(new Error('MapKit did not become available (existing script).'));
          }
        }, 100);
        return;
      }

      const script = document.createElement('script');
      script.async = true;
      script.src = 'https://cdn.apple-mapkit.com/mk/5.x.x/mapkit.js';
      script.onload = () => {
        if (!window.mapkit) {
          reject(new Error('MapKit script loaded but mapkit missing.'));
          return;
        }
        try {
          window.mapkit.init({
            authorizationCallback: (done) => done(token),
          });
        } catch (e) {
          // init can throw if called twice; ignore.
        }
        resolve(window.mapkit);
      };
      script.onerror = () => reject(new Error('Failed to load MapKit script.'));
      document.head.appendChild(script);
    });

    return appleMapKitPromise;
  }

  function extractAppleComponents(place) {
    return {
      name: place && place.name ? place.name : '',
      address_line1: (place && place.formattedAddressLines && place.formattedAddressLines[0]) ? place.formattedAddressLines[0] : '',
      address_line2: (place && place.formattedAddressLines && place.formattedAddressLines[1]) ? place.formattedAddressLines[1] : '',
      locality: (place && place.locality) ? place.locality : '',
      administrative_area: (place && place.administrativeArea) ? place.administrativeArea : '',
      postal_code: (place && place.postalCode) ? place.postalCode : '',
      country_code: (place && place.countryCode) ? place.countryCode : 'AU',
    };
  }

  function setupAppleAutocomplete(searchInput, form, widgetRoot) {
    const token = SETTINGS.apple_maps_token;
    if (!token) {
      error('Apple provider selected but SETTINGS.apple_maps_token missing.');
      return;
    }

    loadAppleMapKit(token)
      .then((mapkit) => {
        if (!searchInput || !form) return;

        if (searchInput.dataset.melAppleAttached === '1') return;
        searchInput.dataset.melAppleAttached = '1';

        // Simple suggestion dropdown
        const wrapper = searchInput.parentElement;
        if (!wrapper) return;

        wrapper.style.position = wrapper.style.position || 'relative';

        const list = document.createElement('div');
        list.className = 'myeventlane-location-suggestions';
        list.style.cssText = 'position:absolute;left:0;right:0;top:100%;background:#fff;border:1px solid #ccc;max-height:240px;overflow:auto;z-index:1000;display:none;';
        wrapper.appendChild(list);

        const searchService = new mapkit.Search({
          region: new mapkit.CoordinateRegion(
            new mapkit.Coordinate(-25.2744, 133.7751),
            new mapkit.CoordinateSpan(40, 40)
          ),
        });

        let t = null;
        searchInput.addEventListener('input', () => {
          const q = String(searchInput.value || '').trim();
          if (q.length < 3) {
            list.style.display = 'none';
            list.innerHTML = '';
            return;
          }

          clearTimeout(t);
          t = setTimeout(() => {
            searchService.search(q, (err, data) => {
              if (err || !data || !data.places) {
                list.style.display = 'none';
                list.innerHTML = '';
                return;
              }

              list.innerHTML = '';
              const places = data.places.slice(0, 6);
              for (const p of places) {
                const item = document.createElement('div');
                item.style.cssText = 'padding:10px;border-bottom:1px solid #eee;cursor:pointer;';
                item.textContent = p.name + (p.formattedAddressLines ? ' — ' + p.formattedAddressLines.join(', ') : '');
                item.addEventListener('click', () => {
                  list.style.display = 'none';
                  list.innerHTML = '';
                  searchInput.value = p.name;

                  const comps = extractAppleComponents(p);
                  const lat = p.coordinate ? p.coordinate.latitude : null;
                  const lng = p.coordinate ? p.coordinate.longitude : null;

                  populateAddressWidget(form, widgetRoot, comps);
                  // Populate venue name from place.name (not comps.name, which may be empty).
                  populateVenueName(form, p.name || comps.name || '');
                  if (lat !== null && lng !== null) {
                    populateLatLng(form, widgetRoot, lat, lng);
                  }

                  // Dispatch custom event for venue-selection.js to handle venue-specific logic
                  const placeSelectedEvent = new CustomEvent('place_selected', {
                    detail: {
                      place: p,
                      provider: 'apple_maps',
                      components: comps,
                      lat: lat,
                      lng: lng,
                    },
                    bubbles: true,
                  });
                  searchInput.dispatchEvent(placeSelectedEvent);

                  log('Apple place selected + populated', { comps, lat, lng, place_name: p.name });
                });

                list.appendChild(item);
              }

              list.style.display = places.length ? 'block' : 'none';
            });
          }, 250);
        });

        document.addEventListener('click', (e) => {
          if (!wrapper.contains(e.target)) {
            list.style.display = 'none';
          }
        });

        log('Apple autocomplete attached', searchInput);
      })
      .catch((e) => {
        error(e);
      });
  }

  /**
   * Set default country to AU if empty.
   */
  function ensureDefaultCountry(form, widgetRoot) {
    if (!form || !widgetRoot) return;
    
    const country = findAddressComponent(widgetRoot, 'country_code', true);
    if (!country) return;
    
    // Only set default if field is empty.
    if (!country.value || country.value === '') {
      // Check if AU is available in options (for select fields).
      if (country.tagName === 'SELECT') {
        for (const opt of country.options) {
          if (opt.value === 'AU') {
            setFieldValue(country, 'AU');
            log('Set default country to AU');
            break;
          }
        }
      } else {
        // For text inputs, just set the value.
        setFieldValue(country, 'AU');
        log('Set default country to AU');
      }
    }
  }

  /**
   * ------------------------------------------------------------------------
   * Main initializer (called per form)
   * ------------------------------------------------------------------------
   */
  function initForForm(form) {
    if (!form) return;

    const widgetRoot = getLocationWidgetRoot(form);
    const searchInput = findSearchInput(form);

    // If search input doesn't exist on this step yet, don't do anything.
    // Behavior will re-run on the next AJAX step render.
    if (!searchInput) {
      log('No search input found in this form context (yet).');
      return;
    }

    // If hidden (e.g. online mode), skip until visible.
    if (!isVisible(searchInput)) {
      log('Search input hidden; skipping until visible.');
      return;
    }

    // Set default country to AU if empty.
    ensureDefaultCountry(form, widgetRoot);

    const provider = SETTINGS.provider || 'google_maps';

    log('Initializing provider:', provider, 'Form:', form);

    if (provider === 'google_maps') {
      setupGoogleAutocomplete(searchInput, form, widgetRoot);
    } else if (provider === 'apple_maps') {
      setupAppleAutocomplete(searchInput, form, widgetRoot);
    } else {
      warn('Unknown provider:', provider);
    }
  }

  /**
   * ------------------------------------------------------------------------
   * Drupal behavior
   * ------------------------------------------------------------------------
   */
  Drupal.behaviors.myeventlaneLocationAutocomplete = {
    attach(context) {
      // Target forms that contain our address search field OR field_location wrapper.
      const candidates = [];

      // If context itself is a form.
      if (context && context.tagName === 'FORM') {
        candidates.push(context);
      } else if (context && context.querySelectorAll) {
        // Any forms containing our field.
        const forms = context.querySelectorAll('form');
        for (const f of forms) {
          // Quick filter: only those that look relevant.
          if (
            f.querySelector('.myeventlane-location-address-search') ||
            f.querySelector('input[data-address-search="true"]') ||
            f.querySelector('input[name*="field_location_address_search"]') ||
            f.querySelector('[data-mel-address="field_location"]') ||
            f.querySelector('.field--name-field-location') ||
            f.querySelector('.myeventlane-venue-selection-widget')
          ) {
            candidates.push(f);
          }
        }
      }

      // Run once per form per attach cycle.
      // Use a unique key per form to handle AJAX rebuilds properly.
      for (const form of once('mel-location-autocomplete', candidates, context)) {
        // Small delay helps when wizard step is injected via AJAX.
        setTimeout(() => initForForm(form), 50);
      }
    }
  };

})(window.Drupal || {}, window.drupalSettings || {}, window.once);