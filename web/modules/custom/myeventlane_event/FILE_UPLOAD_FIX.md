# File Upload Size Issue - Fix Notes

## Issue
Error message: "An unrecoverable error occurred. The uploaded file likely exceeded the maximum file size (256 MB) that this server supports."

## Changes Made
1. **Increased file validator limit** from 5MB to 10MB in `EventWizardForm.php`
   - This is a reasonable limit for hero images
   - Location: `buildBrandingStep()` method

## Additional Steps Required

### 1. Check PHP Configuration
The error mentions 256MB, which suggests PHP's `upload_max_filesize` or `post_max_size` might be incorrectly configured.

**Check current PHP settings:**
```bash
ddev exec php -i | grep -E "upload_max_filesize|post_max_size"
```

**Expected values:**
- `upload_max_filesize` should be at least 10M (for hero images)
- `post_max_size` should be larger than `upload_max_filesize`

### 2. Update PHP Configuration (if needed)
If using DDEV, add to `.ddev/config.php`:
```php
ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '12M');
```

Or update `.ddev/php/php.ini`:
```ini
upload_max_filesize = 10M
post_max_size = 12M
```

### 3. Check Drupal File System Settings
1. Navigate to `/admin/config/media/file-system`
2. Verify upload directory permissions
3. Check "Temporary directory" is writable

### 4. Restart DDEV
After making PHP configuration changes:
```bash
ddev restart
```

## Current Wizard Settings
- **File validator limit:** 10MB (10485760 bytes)
- **Allowed extensions:** png, gif, jpg, jpeg, webp
- **Upload location:** `public://event_images/`

## Testing
After fixes, test by:
1. Navigate to wizard Branding step
2. Try uploading a hero image (under 10MB)
3. Verify upload succeeds

If issues persist, check:
- Drupal watchdog logs: `ddev drush watchdog-show`
- PHP error logs: `ddev logs web`
- File system permissions: `ddev exec ls -la web/sites/default/files/`
