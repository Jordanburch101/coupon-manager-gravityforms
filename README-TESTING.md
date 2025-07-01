# Testing Strategy for GravityForms Coupon Generator

## Overview

This document outlines the comprehensive testing strategy implemented for the GravityForms Coupon Generator plugin to ensure data stability and prevent PHP errors.

## Test Architecture

```
tests/
├── unit/                    # Unit tests for individual methods
├── integration/            # Integration tests for full workflows
├── database/              # Database integrity and transaction tests
├── mocks/                 # Mock classes for GravityForms dependencies
└── bin/                   # Test setup scripts
```

## Testing Layers

### 1. Unit Tests
- Test individual methods in isolation
- Mock external dependencies
- Focus on business logic validation
- Cover edge cases and error conditions

### 2. Integration Tests
- Test complete workflows
- Verify AJAX handlers
- Test permission checks
- Ensure proper WordPress integration

### 3. Database Tests
- Ensure data integrity
- Test transaction rollbacks
- Verify no duplicate coupon codes
- Test concurrent operations
- Validate JSON structure in meta fields

## Key Test Coverage Areas

### Coupon Generation
- ✅ Single coupon creation
- ✅ Bulk generation (up to 1000)
- ✅ Different discount types (percentage/flat)
- ✅ Date validations
- ✅ Usage limits
- ✅ Stackable options
- ✅ Code length variations
- ✅ Special characters in prefixes

### Coupon Updates
- ✅ Discount amount changes
- ✅ Type conversions (percentage ↔ flat)
- ✅ Date modifications
- ✅ Usage limit updates
- ✅ Activation/deactivation
- ✅ Bulk updates via CSV
- ✅ Error handling for non-existent coupons

### Data Integrity
- ✅ Valid JSON in all meta fields
- ✅ No duplicate coupon codes
- ✅ Proper character encoding (UTF-8, emojis)
- ✅ Field length constraints
- ✅ Required field validation
- ✅ Transaction rollbacks on failure

### Security
- ✅ Permission checks
- ✅ Nonce verification
- ✅ SQL injection prevention
- ✅ XSS protection

## Running Tests Locally

### Prerequisites
```bash
# Install Composer dependencies
composer install

# Install WordPress test suite
composer run install-wp-tests wordpress_test root root localhost latest
```

### Running All Tests
```bash
composer test
```

### Running Specific Test Suites
```bash
# Unit tests only
composer test:unit

# Integration tests only
composer test:integration

# Database tests only
composer test:database

# Generate coverage report
composer test:coverage
```

## GitHub Actions CI/CD

The plugin uses GitHub Actions for continuous integration with:

- **PHP Version Matrix**: Tests against PHP 7.4, 8.0, 8.1, and 8.2
- **WordPress Version Matrix**: Tests against WP 5.9, 6.0, and latest
- **MySQL Service**: Uses MySQL 5.7 for database tests
- **Code Coverage**: Generates coverage reports with Codecov
- **Security Checks**: Automated scanning for vulnerabilities
- **Linting**: PHP CodeSniffer with WordPress standards

## Handling Premium Dependencies

Since GravityForms and GravityForms Coupons are premium plugins, we:

1. **Mock Classes**: Created mock implementations of GFForms and GFCoupons classes
2. **Database Structure**: Replicate the `gf_addon_feed` table structure
3. **API Compatibility**: Mock methods match the real plugin APIs

## Database Transaction Safety

All tests use database transactions that are rolled back after each test:

```php
public function setUp(): void {
    global $wpdb;
    $wpdb->query('START TRANSACTION');
}

public function tearDown(): void {
    global $wpdb;
    $wpdb->query('ROLLBACK');
}
```

This ensures:
- No test data persists between tests
- Tests can't interfere with each other
- Database remains clean

## Critical Test Scenarios

### 1. Concurrent Generation
Tests simulate multiple users generating coupons simultaneously to ensure no duplicates.

### 2. Special Characters
Tests various character encodings including UTF-8, emojis, and special symbols.

### 3. Large Scale Operations
Tests bulk operations up to the 1000 coupon limit.

### 4. Partial Failures
Tests ensure successful operations complete even if some items fail.

## Best Practices Implemented

1. **Isolation**: Each test is completely isolated
2. **Repeatability**: Tests produce consistent results
3. **Fast Execution**: Uses transactions instead of actual DB writes
4. **Clear Assertions**: Descriptive failure messages
5. **Edge Case Coverage**: Tests boundary conditions

## Adding New Tests

When adding new features:

1. Write unit tests first (TDD approach)
2. Add integration tests for user workflows
3. Include database integrity checks
4. Update this documentation

## Monitoring Test Health

- Coverage reports are generated for each build
- Failed tests block merging
- Coverage threshold: Aim for >80%
- Regular dependency updates

## Common Issues and Solutions

### Issue: Tests fail with "Table doesn't exist"
**Solution**: Run `composer run install-wp-tests` to set up the test database

### Issue: Permission denied errors
**Solution**: Ensure MySQL user has CREATE/DROP privileges

### Issue: Mock classes not found
**Solution**: Check that `tests/bootstrap.php` is loading mocks correctly

## Future Improvements

1. Add visual regression tests for admin UI
2. Implement load testing for bulk operations
3. Add mutation testing
4. Create end-to-end tests with real GravityForms (requires license) 