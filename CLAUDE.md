# SuiteCRM 7.15 - Development Guide

## Project Overview

**Application**: SuiteCRM 7.15 - Open-source Customer Relationship Management
**Language**: PHP 8.1-8.4
**Framework**: Custom MVC + Slim 3.8 (V8 API) + Smarty 4 (templating)
**Database**: MySQL 5.7+ / MariaDB 10.2+ / MSSQL 2016+

## Current Work: Story #10 - Reporting Data

**Branch**: `feature/10-test`
**Main Branch**: `hotfix` (use for PRs)
**Status**: Steps 1-4 Complete ✅, All Steps Validated ✅

### Story Definition
Original requirement: "need reporting data" (vague)

**Clarified as**: V8 REST API endpoint for programmatic access to Opportunity report data in JSON format, enabling external systems (BI tools, data warehouses, mobile apps) to consume SuiteCRM data while respecting OAuth2 authentication and ACL security.

### Implementation Plan (5 Steps)

1. **Requirements Clarification** ✅ COMPLETED & VALIDATED
   - Created comprehensive documentation (102KB across 5 files)
   - Locked down requirements: Opportunities module, 12 filters, OAuth2, JSON API v1.0 spec
   - Endpoint: `GET /Api/V8/custom/report-data/opportunities`
   - Validation document added (STEP-1-VALIDATION.md, 608 lines)

2. **Backend Service** ✅ COMPLETED & VALIDATED
   - File: `custom/lib/ReportingData/OpportunityReportService.php` (1,245 lines)
   - 12 filters, 4 grouping fields, 6 aggregations implemented
   - ACL enforcement, team security, parameterized queries
   - Date period support (this_quarter, last_month, etc.)
   - Validation document added (STEP-2-VALIDATION.md, 609 lines)

3. **V8 API Endpoint** ✅ COMPLETED & VALIDATED
   - Controller: `custom/Api/V8/Controller/ReportDataController.php` (95 lines)
   - Service: `custom/Api/V8/Service/ReportDataService.php` (295 lines)
   - Params: `custom/Api/V8/Param/ReportDataParams.php` (258 lines)
   - Routes: `custom/application/Ext/Api/V8/Config/routes.php`
   - DI: `custom/application/Ext/Api/V8/Config/services/` (services, controllers, params)
   - Tests: `custom/tests/api/V8/ReportDataControllerCest.php` (469 lines, 26 tests)
   - OAuth2 authentication, JSON API v1.0 format, Slim 3.8 integration
   - Validation document added (STEP-3-VALIDATION.md, 865 lines)

4. **Dashboard Dashlet** ✅ COMPLETED & VALIDATED
   - Main class: `custom/modules/Home/Dashlets/OpportunityReportDashlet/OpportunityReportDashlet.php` (339 lines)
   - Template: `custom/modules/Home/Dashlets/OpportunityReportDashlet/OpportunityReportDashlet.tpl` (122 lines)
   - Metadata: `custom/modules/Home/Dashlets/OpportunityReportDashlet/OpportunityReportDashlet.meta.php` (19 lines)
   - Language: `custom/modules/Home/Dashlets/OpportunityReportDashlet/OpportunityReportDashlet.en_us.lang.php` (46 lines)
   - Registration: `custom/Extension/modules/Home/Ext/Dashlets/opportunity_report_dashlet.php` (19 lines)
   - Filter form (4 filters: sales_stage, date_period, amount_min, my_opportunities)
   - Data table with opportunity records, summary section with aggregations
   - Direct backend service integration (no OAuth2 needed for internal context)
   - Total: 545 lines across 5 files (4 PHP + 1 Smarty template)

5. **Testing & Validation** (Next Step)
   - Unit tests, integration tests, acceptance tests

### Key Technical Decisions

| Aspect | Decision | Rationale |
|--------|----------|-----------|
| Module | Opportunities | Highest business value for reporting |
| Format | JSON API v1.0 spec | Consistency with V8 API |
| Auth | OAuth2 bearer tokens | Existing infrastructure |
| Security | Module + record ACL | Leverage SuiteCRM security |
| Aggregation | SQL GROUP BY | Performance, accuracy |
| Backend | Custom service | AOR_Reports is UI-first, not API-first |
| Dashlet Data Access | Direct backend service call | More appropriate than OAuth2 for internal context |

## Code Quality Standards

### Upgrade Safety ⚠️ CRITICAL
- **All custom code MUST be in `custom/` directory**
- **NEVER modify core SuiteCRM files** (they'll be overwritten on upgrade)
- Use extension points: custom/Api, custom/Extension, custom/modules

### Directory Structure
```
custom/
├── lib/                      # PSR-4 classes (namespace: SuiteCRM\Custom\)
├── Api/V8/                   # API extensions
│   ├── Controller/           # API controllers
│   ├── Param/                # Parameter middleware
│   └── Config/routes.php     # Route definitions
├── modules/{Module}/         # Module customizations
│   ├── Ext/                  # Auto-merged extensions (vardefs, etc.)
│   ├── Dashlets/             # Dashboard widgets
│   └── views/                # Custom views
├── Extension/modules/        # Extension manifests (auto-merged)
└── docs/                     # Project documentation
```

### Coding Standards
- **PSR-4** autoloading for namespaced classes
- **PSR-12** coding style (spaces, braces, naming)
- **PHPDoc** comments on all public methods
- Use SuiteCRM DB abstraction (DBManager, BeanFactory) - never raw SQL
- Parameterized queries only (prevent SQL injection)
- ACL checks before data access: `ACLController::checkAccess()`
- Input validation and sanitization

### SuiteCRM Patterns

#### Database Queries
```php
// Good - Use DB abstraction
$db = DBManagerFactory::getInstance();
$query = "SELECT * FROM opportunities WHERE deleted = 0 AND sales_stage = ?";
$result = $db->query($query, [$stage]);

// Bad - Raw SQL
$query = "SELECT * FROM opportunities WHERE sales_stage = '$stage'"; // SQL injection risk!
```

#### Bean Access
```php
// Good - Use BeanFactory
$opportunity = BeanFactory::getBean('Opportunities', $id);
if ($opportunity && $opportunity->ACLAccess('view')) {
    // Process
}

// Use get_list for multiple records
$opportunities = BeanFactory::newBean('Opportunities');
$list = $opportunities->get_list('date_modified DESC', 'deleted = 0', 0, 50);
```

#### ACL Checks
```php
// Module-level
if (!ACLController::checkAccess('Opportunities', 'list', true)) {
    throw new Exception('Insufficient permissions');
}

// Bean-level
if (!$bean->ACLAccess('view')) {
    throw new Exception('Access denied');
}
```

#### V8 API Controllers
```php
namespace SuiteCRM\Custom\Api\V8\Controller;

use Api\V8\Controller\BaseController;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class MyController extends BaseController
{
    public function myAction(Request $request, Response $response): Response
    {
        // 1. Extract params (validated by middleware)
        $params = $request->getAttribute('params');

        // 2. Get current user
        $currentUser = $GLOBALS['current_user'];

        // 3. ACL check
        if (!ACLController::checkAccess('Module', 'action', true)) {
            return $this->errorResponse($response, 'Access denied', 403);
        }

        // 4. Process
        $data = $this->processData($params);

        // 5. Return JSON API response
        return $this->successResponse($response, $data);
    }
}
```

## Important Files & Locations

### Configuration
- `composer.json` - PHP dependencies (don't modify, read-only)
- `.env.dist` - Environment template
- `config.php` - Generated by installer (gitignored)

### Core Modules
- `modules/` - 123 core modules (DON'T MODIFY)
- `include/` - Framework utilities (DON'T MODIFY)
- `Api/V8/` - Core V8 API (DON'T MODIFY)

### Customization Areas ✅
- `custom/` - All custom code goes here
- `custom/Extension/` - Auto-merged extensions

### Reporting Infrastructure
- `modules/AOR_Reports/` - Advanced Open Reports (existing UI-based reporting)
- `include/export_utils.php` - CSV export utilities
- `export.php` - Export entry point

### API
- `Api/V8/Config/routes.php` - Core routes
- `custom/Api/V8/Config/routes.php` - Custom routes (create this)
- OAuth2 endpoint: `POST /Api/access_token`

### Testing
- `tests/unit/phpunit/` - PHPUnit tests
- `tests/api/` - Codeception API tests
- `tests/acceptance/` - Selenium acceptance tests
- `codeception.dist.yml` - Test configuration

## Git Workflow

### Branch Strategy
- Main branch: `hotfix`
- Feature branches: `feature/{story-id}-{slug}`
- Current: `feature/10-test`

### Commit Messages
```
Story #{id}: Brief summary (50 chars)

Detailed explanation of changes:
- What was changed
- Why it was changed
- How it works

Technical details:
- Key files modified
- Patterns used
- Testing approach

Next steps: [if applicable]
```

### PR Target
- Always create PRs against `hotfix` branch
- NOT against `main` or `master`

## Story #10 Documentation

Complete documentation available:
- `STORY-10-MANIFEST.md` - High-level summary (13KB)
- `custom/docs/story-10/REQUIREMENTS.md` - Full requirements spec (31KB)
- `custom/docs/story-10/API_SPECIFICATION.md` - Complete API docs (24KB)
- `custom/docs/story-10/IMPLEMENTATION_PLAN.md` - 5-step plan (16KB)

### API Endpoint Specification

**Endpoint**: `GET /Api/V8/custom/report-data/opportunities`

**Note**: Custom endpoints use the `/custom` prefix for upgrade safety and clear distinction from core endpoints.

**Authentication**: OAuth2 bearer token (required via ResourceServerMiddleware)

**Filters** (12 parameters):
- `sales_stage`, `sales_stage_exclude` - Filter by stage(s)
- `date_closed_from`, `date_closed_to`, `date_closed_period` - Date filtering
- `amount_min`, `amount_max` - Amount range
- `assigned_user_id`, `assigned_user_id_current` - User filtering
- `lead_source`, `account_id` - Source/account filtering
- `probability_min`, `probability_max` - Win probability

**Grouping**: `group_by` (sales_stage, assigned_user_id, lead_source, account_id)

**Pagination**: `page[number]`, `page[size]` (max 500)

**Sorting**: `sort` (prefix `-` for DESC, e.g., `-amount_usdollar`)

**Response Format**: JSON API v1.0 spec or CSV

**Example Request**:
```http
GET /Api/V8/custom/report-data/opportunities?date_closed_period=this_quarter&page[size]=100
Authorization: Bearer {token}
Accept: application/vnd.api+json
```

**Example Response**:
```json
{
  "data": [
    {
      "type": "Opportunities",
      "id": "abc-123",
      "attributes": {
        "name": "Deal Name",
        "amount_usdollar": "150000.00",
        "sales_stage": "Proposal",
        "probability": 75,
        "date_closed": "2026-03-31",
        ...
      }
    }
  ],
  "meta": {
    "total_count": 1247,
    "returned_count": 50,
    "aggregations": { ... }
  },
  "links": { ... }
}
```

## Development Commands

### Testing
```bash
# PHPUnit tests
vendor/bin/phpunit tests/unit/phpunit/

# Codeception API tests
vendor/bin/codecept run api

# Codeception acceptance tests
vendor/bin/codecept run acceptance

# Run specific test
vendor/bin/phpunit tests/unit/phpunit/modules/AOR_Reports/AOR_ReportTest.php
```

### Code Quality
```bash
# PHP CodeSniffer (check standards)
vendor/bin/phpcs --standard=phpcs.xml custom/

# PHP CS Fixer (auto-fix standards)
vendor/bin/php-cs-fixer fix custom/

# Check syntax (if php available)
find custom -name "*.php" -exec php -l {} \;
```

### Git
```bash
# Check status
git status

# Create feature branch
git checkout -b feature/story-id-name

# Commit changes
git add custom/path/to/files
git commit -m "Story #10: Description"

# Push to remote
git push -u origin feature/10-test

# Create PR (use gh CLI)
gh pr create --title "Story #10: Title" --body "Description" --base hotfix
```

## Common Patterns & Utilities

### Get Current User
```php
global $current_user;
if (!$current_user || !$current_user->id) {
    throw new Exception('User not authenticated');
}
```

### Logging
```php
$GLOBALS['log']->fatal('Critical error: ' . $message);
$GLOBALS['log']->error('Error: ' . $message);
$GLOBALS['log']->warn('Warning: ' . $message);
$GLOBALS['log']->info('Info: ' . $message);
$GLOBALS['log']->debug('Debug: ' . $message);
```

### Date Handling
```php
global $timedate;
$userDate = $timedate->to_display_date_time($dbDateTime); // DB to user format
$dbDate = $timedate->to_db($userDate); // User format to DB
```

### Currency Formatting
```php
global $locale;
$formatted = $locale->formatCurrency($amount, $currency_id);
```

### Database Transaction
```php
$db = DBManagerFactory::getInstance();
$db->query('START TRANSACTION');
try {
    // Queries here
    $db->query('COMMIT');
} catch (Exception $e) {
    $db->query('ROLLBACK');
    throw $e;
}
```

## Troubleshooting

### API Returns 404
- Check route registration in `custom/Api/V8/Config/routes.php`
- Verify route file is loaded (check custom loader)
- Clear cache: `rm -rf cache/*`
- Check .htaccess for rewrite rules

### ACL Issues
- Verify user has role assigned
- Check module ACL in Admin > Role Management
- Ensure record has team assignment
- Test with Admin user first

### Query Performance
- Add indexes on filtered/sorted columns
- Use EXPLAIN to analyze queries
- Implement pagination (don't fetch all records)
- Consider query caching (Memcached/Redis)
- Limit joins (max 3-4 tables)

### OAuth2 Token Issues
- Verify client_id and client_secret configured
- Check token expiration (3600 seconds default)
- Use refresh_token for long-running integrations
- Ensure HTTPS in production

## Security Checklist

- [ ] All queries use parameterized statements (no string interpolation)
- [ ] ACL checks before data access
- [ ] Input validation (type, length, format)
- [ ] Output sanitization (HTML, SQL, JSON)
- [ ] OAuth2 token required for API endpoints
- [ ] HTTPS enforced in production
- [ ] Error messages don't expose internal details
- [ ] Rate limiting implemented
- [ ] CORS configured for trusted domains only
- [ ] No sensitive data in logs

## Next Steps for Story #10

### Immediate Actions (Step 5)
1. Create comprehensive testing suite:
   - Unit tests for OpportunityReportService backend logic
   - Integration tests for API endpoint and dashlet
   - Acceptance tests for end-to-end workflows
2. Validate all functionality:
   - Filter combinations and edge cases
   - ACL enforcement across user roles
   - Performance testing with large datasets
   - Security testing (SQL injection, XSS, ACL bypass)

## Important Notes

### SuiteCRM Specifics
- Bean = Data model object (extends SugarBean)
- Vardef = Field definition (metadata)
- Dashlet = Dashboard widget
- Extension = Auto-merged customization (vardefs, language, etc.)
- Team Security = Record-level access control
- ACL = Module and field-level permissions

### Database Tables
- `opportunities` - Opportunity records
- `users` - User accounts
- `accounts` - Account records (companies)
- `aor_reports` - Advanced Open Reports
- `acl_roles` - Permission roles
- `team_sets` - Team security assignments

### Critical: Avoid These Mistakes
- ❌ Modifying core files (will be lost on upgrade)
- ❌ Raw SQL queries (use DB abstraction)
- ❌ Skipping ACL checks (security vulnerability)
- ❌ Direct $_GET/$_POST access in API (use middleware)
- ❌ Hardcoded credentials or secrets
- ❌ Committing config.php or .env files
- ❌ Large result sets without pagination
- ❌ Ignoring error handling

## Resources

- SuiteCRM Docs: https://docs.suitecrm.com/
- Developer Guide: https://docs.suitecrm.com/developer/
- JSON API Spec: https://jsonapi.org/format/
- OAuth2 RFC: https://tools.ietf.org/html/rfc6749
- PSR-12 Style: https://www.php-fig.org/psr/psr-12/

---

**Last Updated**: 2026-02-23 (Story #10 Steps 1-4 Complete, Step 5 Pending)
**Document Size**: 15.1KB (50% of 30KB limit)
**Code Status**: All 2,435 lines compiled successfully, PSR-12 compliant
