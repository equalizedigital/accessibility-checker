# License State Machine (Free + Pro)

This document describes how the free and pro plugins coordinate license status, metadata, and fallback behavior.

## Purpose

The system uses one shared license key option (`edacp_license_key`) and coordinates runtime behavior through:

- `edacp_license_status` (current license status)
- `edac_license_metadata` (inferred license state and response fields)
- `edac_fallback_active` (flag indicating automatic fallback from invalid Pro license)

## State Flow

### 1) Initial (No Active License)

- User has not activated a key
- `edacp_license_status` is empty/false
- `edac_license_metadata` is empty/false

### 2) Free Active

- Free activation succeeds
- `edacp_license_status` becomes `valid`
- `edac_license_metadata.type` becomes `free`
- Free plugin performs periodic checks

### 3) Pro Installed + Valid

- Pro key is activated and valid
- `edacp_license_status` is `valid`
- `edac_license_metadata.type` becomes `pro`
- Pro plugin is authoritative for license checks
- Free plugin guard bails in `periodic_check_license()` when Pro is valid
- Free activation is blocked while Pro is valid

### 4) Pro Invalid (Expired/Disabled/Revoked)

- Pro periodic check detects status is not `valid`
- Pro writes the invalid status to `edacp_license_status`
- Pro clears `edac_license_metadata`
- Pro sets `edac_fallback_active = 1`
- Free periodic check no longer bails and resumes checking as fallback
- Resulting metadata is re-inferred as free when the key validates as free

### 5) Pro Explicitly Deactivated

- User deactivates Pro key
- Pro clears local key/status
- Pro clears `edac_license_metadata`
- Pro clears `edac_fallback_active`
- System is left in a clean state; free can be activated by user action

## Guard Rules

### Free periodic check guard

Free bails only if Pro is both installed and valid:

- `defined( 'EDACP_VERSION' )`
- `edacp_license_status === 'valid'`

If Pro exists but is not valid, free resumes as fallback.

### Free activation guard

Free activation is blocked while Pro is installed and valid to prevent state overwrites.

## Metadata Inference

`edac_license_metadata` is inferred (not raw response payload). It includes:

- `type`: `free`, `pro`, or `unknown`
- `level`: `single-site`, `multi-site`, `unlimited`, `lifetime`, or `unknown`
- `item_id`, `item_name`, `expires`, `license_limit`, `site_count`, `activations_left`, `last_response_at`

Inference order:

1. Product ID match (preferred)
2. Item name fallback
3. Source-context fallback (`free`/`pro`)

## Concurrency Notes

- Decision points use option reads (`get_option`) with deterministic guards
- Transitions are primarily cron-driven and not designed around highly concurrent writes
- Free and Pro both key off `edacp_license_status` for authority handoff

