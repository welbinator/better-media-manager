# Snyk Security Fixes for v1.2.2

## Summary
- **1 High:** XPath Injection (likely false positive - inputs are sanitized)
- **5 Medium:** DOM-based XSS issues in admin.js

## Issues Found

### 1. XPath Injection (HIGH - Score 767)
**File:** `admin/class-ajax-handler.php` line 87
**Status:** FALSE POSITIVE - inputs ARE sanitized
- `$target_url` uses `esc_url_raw()` 
- `$target_class` uses `sanitize_text_field()`
**Action:** Add inline comment to document sanitization

### 2-6. DOM-based XSS (MEDIUM - Score 634 each)
**File:** `admin/js/admin.js`

**Line 37 & 39:** Already using `escapeHtml()` ✓
**Line 168:** Uses `data.images.length` directly (number, but should escape)
**Line 240:** Uses `$preview.html(html)` with image data loop
**Line 457:** (need to check this one)

## Fixes Applied

### Fix 1: Escape data.images.length (Line 168)
Even though it's a number, wrap in escapeHtml() for consistency and to satisfy Snyk.

### Fix 2: Review image rendering loop
Ensure all data from server is properly escaped before insertion into DOM.

### Fix 3: Add security documentation
Document all input sanitization and output escaping for future reference.
