# User Compliance System Implementation

## Overview

This implementation provides an efficient mechanism to enforce user compliance (terms acceptance and parental authorization for underage users) without impacting performance on every authenticated request.

## Key Features

### 1. Session-Based Caching
- Compliance check runs **once per session** after login
- Result is cached in session storage
- No database queries on subsequent requests
- Automatic cache invalidation when compliance status changes

### 2. Middleware Architecture
- `EnsureUserCompliance` middleware added to `auth` middleware group
- Runs automatically for all authenticated routes
- Bypasses specific routes (logout, compliance pages, heartbeat, CSRF)
- Redirects to appropriate compliance page when needed

### 3. Database Structure
New fields added to `users` table:
- `terms_accepted_at` (timestamp, nullable) - When user accepted T&Cs
- `birth_date` (date, nullable) - User's date of birth
- `parental_authorization_verified_at` (timestamp, nullable) - When parental auth was verified

## Implementation Details

### Files Created

#### Middleware
- `/app/Domains/Auth/Public/Middleware/EnsureUserCompliance.php`
  - Session-cached compliance checking
  - Route bypass logic
  - Redirect handling with intended URL preservation

#### Controllers
- `/app/Domains/Auth/Private/Controllers/ComplianceController.php`
  - `showTerms()` - Display T&C acceptance page
  - `acceptTerms()` - Process T&C acceptance
  - `showParentalAuthorization()` - Display parental auth upload page
  - `uploadParentalAuthorization()` - Process file upload

#### Views
- `/app/Domains/Auth/Private/Resources/views/pages/compliance/terms.blade.php`
- `/app/Domains/Auth/Private/Resources/views/pages/compliance/parental.blade.php`

#### Migration
- `/app/Domains/Auth/Database/Migrations/2025_11_23_204700_add_compliance_fields_to_users_table.php`

#### Tests
- `/app/Domains/Auth/Tests/Feature/ComplianceTest.php`
  - 17 comprehensive test cases covering all scenarios

### User Model Methods

New helper methods added to `User` model:

```php
// Check if user has accepted terms
public function hasAcceptedTerms(): bool

// Mark terms as accepted
public function acceptTerms(): void

// Check if user is under 15 years old
public function is_under_15: bool

// Check if parental authorization is needed
public function needsParentalAuthorization(): bool

// Mark parental authorization as verified
public function verifyParentalAuthorization(): void

// Check if user is fully compliant
public function isCompliant(): bool
```

## Compliance Flow

### For Regular Users (15+ years old)

1. User logs in
2. Middleware checks `terms_accepted_at`
3. If null → redirect to `/compliance/terms`
4. User accepts terms → redirected to intended URL
5. Session cached, no further checks this session

### For Underage Users (<15 years old)

1. User logs in
2. Middleware checks `terms_accepted_at`
3. If null → redirect to `/compliance/terms`
4. User accepts terms
5. Middleware checks `needsParentalAuthorization()`
6. Redirect to `/compliance/parental-authorization`
7. User uploads document → redirected to intended URL
8. Session cached, no further checks this session

## Routes

All routes under `auth` middleware:

```
GET  /compliance/terms                     (compliance.terms.show)
POST /compliance/terms                     (compliance.terms.accept)
GET  /compliance/parental-authorization    (compliance.parental.show)
POST /compliance/parental-authorization    (compliance.parental.upload)
```

## Configuration

Middleware is registered in `/bootstrap/app.php`:

```php
$middleware->appendToGroup('auth', EnsureUserCompliance::class);
```

Routes bypassing compliance check (in middleware):
- `logout`
- `compliance.terms.show`
- `compliance.terms.accept`
- `compliance.parental.show`
- `compliance.parental.upload`
- `session.heartbeat`
- `session.csrf`

## Performance Considerations

### Session Caching Strategy
```php
$sessionKey = 'user_compliance_checked_' . $user->id;

if (!session()->has($sessionKey)) {
    // Perform database check
    if (!$user->hasAcceptedTerms()) { ... }
    if ($user->needsParentalAuthorization()) { ... }
    
    // Cache result
    session()->put($sessionKey, true);
}
```

**Benefits:**
- ✅ Database query only once per session
- ✅ Zero performance impact after first check
- ✅ Automatic cleanup on logout/session expiry
- ✅ Per-user cache key prevents conflicts
- ✅ Cache invalidation on compliance status change

## Deployment Steps

1. **Run Migration**
   ```bash
   ./vendor/bin/sail artisan migrate
   ```

2. **Run Tests**
   ```bash
   ./vendor/bin/sail artisan test --filter=ComplianceTest
   ```

3. **Customize Views**
   - Update `/app/Domains/Auth/Private/Resources/views/pages/compliance/terms.blade.php` with actual T&Cs
   - Add translations if needed
   - Style compliance pages to match site design

4. **Parental Authorization Storage**
   - Current implementation stores files to `storage/app/private/parental_authorizations/`
   - Consider adding a table to track:
     - File path
     - Upload timestamp
     - Verification status (pending/approved/rejected)
     - Admin notes

5. **Admin Review System** (Future Enhancement)
   - Add Filament resource to review uploaded documents
   - Email notifications to admins on new uploads
   - Ability to approve/reject with notes
   - Email notification to user on status change

## Registration Integration

To capture birth date during registration, modify:

**`RegisterRequest.php`**
```php
'birth_date' => ['required', 'date', 'before:today'],
```

**`RegisteredUserController.php`**
```php
$user = User::create([
    'email' => $data['email'],
    'password' => Hash::make($data['password']),
    'birth_date' => $data['birth_date'],
]);
```

**Registration View**
Add date picker for birth date field.

## Testing Scenarios

All scenarios covered in test suite:

- ✅ User without terms redirected
- ✅ User can accept terms
- ✅ Terms require checkbox
- ✅ Underage user redirected to parental auth after terms
- ✅ Underage user must accept terms before parental page
- ✅ Valid file upload accepted
- ✅ Invalid file types rejected
- ✅ Compliant users access dashboard
- ✅ Session caching works
- ✅ Guest users not checked
- ✅ Logout bypasses check
- ✅ All User model helper methods work correctly

## Security Considerations

1. **File Upload Validation**
   - Max size: 5MB
   - Allowed types: PDF, JPG, JPEG, PNG
   - Stored in private storage (not publicly accessible)

2. **Session Security**
   - Cache keys tied to user ID
   - Cleared on logout
   - Cleared on compliance status change

3. **Route Protection**
   - All compliance routes require authentication
   - Terms acceptance required before parental auth
   - Proper redirect chaining

## Future Enhancements

1. **Admin Verification Workflow**
   - Manual review of parental authorizations
   - Approval/rejection workflow
   - Email notifications

2. **Catch-up Campaign**
   - Script to mark all existing users as compliant
   - Or force acceptance on next login

3. **Terms Versioning**
   - Track which version of terms user accepted
   - Force re-acceptance on major updates
   - `terms_version` field in database

4. **Audit Trail**
   - Log all compliance events
   - IP address and timestamp tracking
   - Export for legal compliance

## Troubleshooting

### Compliance check running on every request
- Verify session is working properly
- Check session driver configuration
- Ensure session middleware is enabled

### Users stuck in compliance loop
- Check that compliance routes are in bypass list
- Verify User model methods return correct values
- Check database values are being saved properly

### File uploads failing
- Verify `private` disk is configured in `config/filesystems.php`
- Check directory permissions for `storage/app/private/`
- Ensure file size doesn't exceed php.ini limits
