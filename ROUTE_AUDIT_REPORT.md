# Route Audit Report
**Date:** 2025-01-27  
**Scope:** Complete audit of all routes and domain configurations in MyEventLane  
**Target:** Ensure all routes correctly point to:
- Main site: `https://myeventlane.ddev.site/`
- Vendor: `https://vendor.myeventlane.ddev.site/`
- Admin: `https://admin.myeventlane.ddev.site/`

## Executive Summary

✅ **All routes are production-ready.** All routing files use relative paths, and domain configuration has been updated to use the correct subdomains. The system will automatically work in production when domain settings are updated.

## Issues Found and Fixed

### 1. ✅ Domain Settings Configuration
**File:** `web/sites/default/config/sync/myeventlane_core.domain_settings.yml`

**Issue:**
- All three domains were pointing to `https://myeventlane.ddev.site`
- Vendor and admin domains should use their respective subdomains

**Fix Applied:**
```yaml
public_domain: 'https://myeventlane.ddev.site'
vendor_domain: 'https://vendor.myeventlane.ddev.site'  # ✅ Fixed
admin_domain: 'https://admin.myeventlane.ddev.site'    # ✅ Fixed
```

**Impact:** This is the primary configuration that controls domain-based routing and redirects throughout the application. The VendorDomainSubscriber uses these settings to enforce proper domain routing.

### 2. ✅ DomainDetector Fallback URLs
**File:** `web/modules/custom/myeventlane_core/src/Service/DomainDetector.php`

**Issue:**
- Fallback URLs were hardcoded to `https://myeventlane.ddev.site` for all domain types
- This would break in production if configuration was missing

**Fix Applied:**
- Updated fallback URLs to use correct subdomains:
  - Public: `https://myeventlane.ddev.site` (unchanged)
  - Vendor: `https://vendor.myeventlane.ddev.site` ✅
  - Admin: `https://admin.myeventlane.ddev.site` ✅
- Fallback logic first attempts to construct from current request (production-ready)
- Only uses hardcoded fallback as last resort

**Impact:** These fallback URLs are used when domain configuration is missing or unavailable. They now correctly default to the appropriate subdomains.

## Route Architecture Overview

The MyEventLane platform uses a multi-domain architecture with domain-based routing:

### Domain Types
1. **Public Domain** (`myeventlane.ddev.site`)
   - Event discovery, ticketing, RSVPs
   - Customer onboarding
   - Public-facing content
   - Routes: `/events`, `/categories`, `/organisers`, `/my-events`

2. **Vendor Domain** (`vendor.myeventlane.ddev.site`)
   - Vendor dashboard (`/vendor/dashboard`)
   - Event management (`/vendor/events/*`)
   - Stripe Connect (`/vendor/stripe/*`)
   - Vendor analytics (`/vendor/analytics`)
   - Vendor onboarding (`/vendor/onboard/*`)

3. **Admin Domain** (`admin.myeventlane.ddev.site`)
   - Admin dashboard (`/admin/myeventlane`)
   - System administration (`/admin/*`)
   - Entity management (`/admin/structure/*`)

### Key Routing Components

1. **DomainDetector Service** (`myeventlane_core/src/Service/DomainDetector.php`)
   - Detects current domain type (vendor/admin/public)
   - Builds URLs for different domain types
   - Used throughout the application for cross-domain navigation
   - ✅ Now correctly configured with subdomain fallbacks

2. **VendorDomainSubscriber** (`myeventlane_core/src/EventSubscriber/VendorDomainSubscriber.php`)
   - Enforces domain-based routing
   - Redirects vendor routes to vendor domain
   - Redirects public routes to public domain
   - Respects `force_redirects` configuration
   - Uses `DomainDetector::buildDomainUrl()` for cross-domain redirects

3. **Theme Negotiators**
   - `VendorThemeNegotiator`: Applies vendor theme on vendor domain
   - `AdminThemeNegotiator`: Applies admin theme (Gin) on admin domain

## Routing Files Audited

All 18 routing files were reviewed. **All use relative paths** and will correctly generate URLs based on the configured base URL:

✅ `myeventlane_analytics.routing.yml` - All paths relative  
✅ `myeventlane_boost.routing.yml` - All paths relative  
✅ `myeventlane_commerce.routing.yml` - All paths relative  
✅ `myeventlane_core.routing.yml` - All paths relative  
✅ `myeventlane_dashboard.routing.yml` - All paths relative  
✅ `myeventlane_donations.routing.yml` - All paths relative  
✅ `myeventlane_escalations.routing.yml` - All paths relative  
✅ `myeventlane_event_attendees.routing.yml` - All paths relative  
✅ `myeventlane_finance.routing.yml` - All paths relative  
✅ `myeventlane_location.routing.yml` - All paths relative  
✅ `myeventlane_messaging.routing.yml` - All paths relative  
✅ `myeventlane_rsvp.routing.yml` - All paths relative  
✅ `myeventlane_tickets.routing.yml` - All paths relative  
✅ `myeventlane_vendor.routing.yml` - All paths relative  
✅ `myeventlane_wallet.routing.yml` - All paths relative  
✅ `myeventlane_views.routing.yml` - All paths relative  
✅ `myeventlane_checkout_paragraph.routing.yml` - All paths relative  
✅ `myeventlane_admin_dashboard.routing.yml` - All paths relative  

**Result:** All routing files use relative paths (e.g., `/vendor/dashboard`, `/admin/myeventlane`) and will correctly generate URLs based on Drupal's base URL configuration.

## URL Generation Patterns

### ✅ Correct Patterns (No Changes Needed)

1. **Relative Route Generation:**
   ```php
   Url::fromRoute('myeventlane_vendor.console.dashboard')
   // Generates: /vendor/dashboard (relative)
   ```

2. **Absolute Route Generation:**
   ```php
   Url::fromRoute('myeventlane_vendor.console.dashboard', [], ['absolute' => TRUE])
   // Generates: https://vendor.myeventlane.ddev.site/vendor/dashboard
   // Uses Drupal's base URL configuration
   ```

3. **Cross-Domain URL Generation:**
   ```php
   $this->domainDetector->buildDomainUrl('/vendor/dashboard', 'vendor')
   // Generates: https://vendor.myeventlane.ddev.site/vendor/dashboard
   // Uses configured domain settings
   ```

4. **Entity URL Generation:**
   ```php
   $event->toUrl()->setAbsolute()->toString()
   // Generates: https://myeventlane.ddev.site/node/123
   // Uses entity's canonical URL with base URL
   ```

### ✅ Controllers and Services

All controllers reviewed use proper URL generation:
- `StripeConnectController`: Uses `Url::fromRoute()` with request-based base URL ✅
- `CreateEventGatewayController`: Uses `Url::fromRoute()` ✅
- `VendorDashboardController`: Uses `Url::fromRoute()` ✅
- `AdminDashboardController`: Uses `Url::fromRoute()` and `toUrl()` ✅
- All redirects use `Url::fromRoute()` which respects base URL ✅

### ✅ Email Templates

Email templates use Drupal's URL generation functions which respect the base URL:
- `url('entity.node.canonical', {'node': nid}, {'absolute': true})` ✅
- All email templates reviewed - no hardcoded URLs found ✅

### ✅ Twig Templates

All Twig templates reviewed:
- Use `url()` or `path()` functions (Drupal Twig functions) ✅
- No hardcoded domain URLs found ✅

## Production Readiness

### Configuration-Based Domains

The system is **production-ready** because:

1. **Domain settings are configurable:**
   - Stored in `myeventlane_core.domain_settings` config
   - Can be updated via Drush: `ddev drush config:set myeventlane_core.domain_settings vendor_domain 'https://vendor.myeventlane.com'`
   - Can be updated via UI: `/admin/config/myeventlane/general`

2. **Fallback logic is production-ready:**
   - First attempts to construct from current request (works in any environment)
   - Only uses hardcoded fallback as last resort
   - Fallback URLs now use correct subdomains

3. **All routes use relative paths:**
   - Drupal automatically prepends the base URL
   - Base URL is configured in `settings.php` or via environment variables
   - Works in both dev and production

### For Production Deployment

When deploying to production, update the domain settings:

```bash
# Set production domains
ddev drush config:set myeventlane_core.domain_settings public_domain 'https://myeventlane.com'
ddev drush config:set myeventlane_core.domain_settings vendor_domain 'https://vendor.myeventlane.com'
ddev drush config:set myeventlane_core.domain_settings admin_domain 'https://admin.myeventlane.com'

# Clear cache
ddev drush cr
```

Or update via the UI at `/admin/config/myeventlane/general`.

## Files Modified

1. ✅ `web/sites/default/config/sync/myeventlane_core.domain_settings.yml`
   - Updated `vendor_domain` to use subdomain
   - Updated `admin_domain` to use subdomain

2. ✅ `web/modules/custom/myeventlane_core/src/Service/DomainDetector.php`
   - Updated `getVendorDomainUrl()` fallback to use vendor subdomain
   - Updated `getAdminDomainUrl()` fallback to use admin subdomain
   - Added comments clarifying production-ready behavior

## Verification Steps

### 1. Verify Domain Configuration
```bash
ddev drush config:get myeventlane_core.domain_settings
```

Expected output:
```yaml
public_domain: 'https://myeventlane.ddev.site'
vendor_domain: 'https://vendor.myeventlane.ddev.site'
admin_domain: 'https://admin.myeventlane.ddev.site'
```

### 2. Test Domain Detection
```bash
# Test public domain URL generation
ddev drush php-eval "echo \Drupal::service('myeventlane_core.domain_detector')->getPublicDomainUrl();"
# Expected: https://myeventlane.ddev.site

# Test vendor domain URL generation
ddev drush php-eval "echo \Drupal::service('myeventlane_core.domain_detector')->getVendorDomainUrl();"
# Expected: https://vendor.myeventlane.ddev.site

# Test admin domain URL generation
ddev drush php-eval "echo \Drupal::service('myeventlane_core.domain_detector')->getAdminDomainUrl();"
# Expected: https://admin.myeventlane.ddev.site
```

### 3. Test Routes

1. **Public Domain:**
   - Visit `https://myeventlane.ddev.site`
   - Visit `https://myeventlane.ddev.site/events`
   - Visit `https://myeventlane.ddev.site/organisers`

2. **Vendor Domain:**
   - Visit `https://vendor.myeventlane.ddev.site`
   - Should redirect to `https://vendor.myeventlane.ddev.site/vendor/dashboard`
   - Visit `https://vendor.myeventlane.ddev.site/vendor/events`

3. **Admin Domain:**
   - Visit `https://admin.myeventlane.ddev.site`
   - Visit `https://admin.myeventlane.ddev.site/admin/myeventlane`
   - Visit `https://admin.myeventlane.ddev.site/admin/structure/myeventlane/vendor`

4. **Cross-Domain Redirects:**
   - Visit `https://myeventlane.ddev.site/vendor/dashboard`
   - Should redirect to `https://vendor.myeventlane.ddev.site/vendor/dashboard` (if `force_redirects` is enabled)
   - Visit `https://vendor.myeventlane.ddev.site/events`
   - Should redirect to `https://myeventlane.ddev.site/events` (if `force_redirects` is enabled)

## Notes

- ✅ Email addresses like `noreply@myeventlane.com` are correct and don't need changes
- ✅ External CDN URLs (Stripe, Google Maps, etc.) are correct and don't need changes
- ✅ SVG namespace URLs (`http://www.w3.org/2000/svg`) are correct and don't need changes
- ✅ Documentation links in markdown files are informational only
- ✅ Vite config files with `localhost` or `ddev.site` URLs are dev-only and don't affect production
- ✅ Node modules are third-party and don't need changes

## Summary

✅ **All routes are correctly configured and production-ready.**

- All routing files use relative paths
- Domain settings configured with correct subdomains
- DomainDetector fallbacks updated to use correct subdomains
- All URL generation uses Drupal's base URL system
- No hardcoded production URLs found
- System will work correctly in production when domain settings are updated

The platform is ready for production deployment. Simply update the domain settings configuration when deploying to production.
