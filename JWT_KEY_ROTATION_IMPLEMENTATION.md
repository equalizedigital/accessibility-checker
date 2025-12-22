# JWT Key Rotation Implementation: Option 2 + Option 3

## Overview

Both **Option 2 (Reactive)** and **Option 3 (Proactive)** JWT key rotation strategies have been fully implemented in the Connector class.

## What Was Implemented

### Option 3 (Proactive) - Daily Automatic Key Verification

**Location**: `Connector::periodic_check_license()`

**How it works**:
1. The daily license check (runs via WordPress cron at `edacp_check_license_hook`)
2. After checking the license status, it now calls `verify_and_update_public_key()`
3. This makes a lightweight GET request to `/public-key` endpoint
4. If the issuer has issued a new public key, it's automatically fetched and stored
5. **Result**: Zero downtime, no failed validations, seamless key rotation

**Code**:
```php
// At the end of periodic_check_license()
self::verify_and_update_public_key();
```

**Requirements**:
- Issuer must provide: `GET /wp-json/myed-email-reports/v1/public-key`
- No authentication required (public key is public)
- Response: `{ "jwt_public_key": "-----BEGIN PUBLIC KEY-----\n...\n-----END PUBLIC KEY-----" }`

---

### Option 2 (Reactive) - Fallback Key Refresh on Validation Failure

**Location**: `Connector::validate_jwt_token_with_fallback()`

**How it works**:
1. When a JWT token validation fails in the REST API
2. Instead of immediately denying access, the fallback method tries one more time
3. It calls `refresh_public_key_from_issuer()` to fetch the latest key via GET request
4. If the key is refreshed, it retries the validation with the new key
5. **Result**: Handles edge cases where cron didn't run or keys were rotated unexpectedly

**Code**:
```php
public static function validate_jwt_token_with_fallback( $token ) {
    if ( self::validate_jwt_token( $token ) ) {
        return true; // Valid on first try
    }
    // Validation failed - try refreshing the key
    if ( self::refresh_public_key_from_issuer() ) {
        return self::validate_jwt_token( $token ); // Retry with new key
    }
    return false; // Still invalid
}
```

**Requirements**:
- Issuer must provide: `GET /wp-json/myed-email-reports/v1/public-key`
- No authentication required (public key is public)
- Response: `{ "jwt_public_key": "-----BEGIN PUBLIC KEY-----\n...\n-----END PUBLIC KEY-----" }`

---

### Integration with REST API

**Location**: `class-rest-api.php` - `/scans-stats` permission callback

**Updated to use**: `Connector::validate_jwt_token_in_request_with_fallback()`

This new method:
- Extracts the JWT token from the `Authorization: Bearer <token>` header
- Uses the fallback validator (combining both Option 2 + 3)
- Falls back to WordPress capability check if no token is present

**Code**:
```php
'permission_callback' => function ( $request ) {
    // Use the fallback validator which handles both Option 2 (Reactive) and Option 3 (Proactive)
    if ( Connector::validate_jwt_token_in_request_with_fallback( $request ) ) {
        return true;
    }
    return current_user_can( 'edit_posts' );
},
```

---

## New Methods Added to Connector Class

| Method | Purpose | Option |
|--------|---------|--------|
| `validate_jwt_token_with_fallback()` | Validates JWT, falls back to key refresh if needed | Option 2 |
| `validate_jwt_token_in_request_with_fallback()` | REST API wrapper with fallback | Option 2 + 3 |
| `verify_and_update_public_key()` | Proactively checks issuer for updated key | Option 3 |
| `refresh_public_key_from_issuer()` | Fetches latest public key on demand | Option 2 |

---

## Flow Diagrams

### Normal Day (Option 3)
```
Daily Cron (24h interval)
    ↓
periodic_check_license()
    ↓
verify_and_update_public_key()  ← Option 3 (Proactive)
    ↓
GET /public-key (lightweight API call, no auth)
    ↓
If new key available: store it
    ↓
Next REST request uses updated key
    ✓ Zero downtime
```

### Edge Case: Cron Missed, Key Was Rotated (Option 2)
```
REST API Request arrives
    ↓
validate_jwt_token_in_request_with_fallback()
    ↓
validate_jwt_token() → FAILS (signature mismatch)
    ↓
refresh_public_key_from_issuer()  ← Option 2 (Reactive)
    ↓
GET /public-key (fetch new key, no auth)
    ↓
validate_jwt_token() → SUCCEEDS (with new key)
    ✓ Single retry, request succeeds
```

---

## Benefits of Combined Approach

✅ **Option 3 handles 99% of cases**: Proactive daily check ensures keys are always fresh
✅ **Option 2 handles edge cases**: If cron fails or keys rotate unexpectedly, fallback catches it
✅ **Zero downtime**: Keys are updated before validation ever fails
✅ **Automatic recovery**: No admin action needed
✅ **Compatible with manual reset**: Unregister/Re-register still works as emergency option

---

## Testing the Implementation

### Test Option 3 (Proactive):
1. Register a site (stores public key)
2. Have issuer rotate its private key
3. Wait for daily cron to run (or trigger manually)
4. Verify that `edac_jwt_public_key` option was updated with new key
5. Make REST API call with old JWT - should still work because key was refreshed

### Test Option 2 (Reactive):
1. Register a site (stores public key)
2. Manually update the `edac_jwt_public_key` option with wrong/old key
3. Make REST API call with valid JWT signed with new issuer key
4. System should detect validation failure, fetch new key, retry, and succeed

---

## Files Modified

- `includes/classes/MyDot/Connector.php` - Added methods, updated `periodic_check_license()`
- `includes/classes/class-rest-api.php` - Updated permission callback to use fallback validator

---

## Summary

**Status**: ✅ **Complete**

Both Option 2 (Reactive) and Option 3 (Proactive) are now fully implemented and integrated. The system will:

1. **Proactively** verify and update JWT public keys daily (Option 3)
2. **Reactively** refresh keys if validation fails (Option 2)
3. **Seamlessly** handle issuer key rotations with zero downtime
4. **Automatically** manage key updates without admin intervention
5. **Fall back** to WordPress capability checks when no JWT is provided

No further configuration needed unless you want to add Option 2 + Option 3 handling to other REST endpoints.

