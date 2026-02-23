# Story 10: Reporting Data - Implementation Plan

## Document Information
- **Version**: 1.0
- **Date**: 2026-02-23
- **Status**: Locked
- **Related Documents**: REQUIREMENTS.md, API_SPECIFICATION.md

---

## 1. Implementation Overview

This document outlines the technical implementation plan for Story 10, broken down into discrete steps with clear deliverables and validation criteria.

---

## 2. Implementation Steps

### Step 1: Requirements Clarification and Documentation ✅ COMPLETED

**Objective**: Lock down requirements and create comprehensive specification

**Deliverables**:
- [x] REQUIREMENTS.md - Complete requirements specification
- [x] API_SPECIFICATION.md - Detailed API documentation
- [x] IMPLEMENTATION_PLAN.md - This document
- [x] Custom directory structure created

**Validation**:
- All specification documents created
- Requirements are clear and actionable
- No ambiguity remains

**Status**: ✅ COMPLETED

---

### Step 2: Backend Reporting Service Implementation

**Objective**: Build the core OpportunityReportService class that queries and transforms data

**Files to Create**:
1. `custom/lib/ReportingData/OpportunityReportService.php`
   - Main service class with query building logic
   - Filter validation and sanitization
   - ACL enforcement integration
   - Aggregation calculations
   - Pagination support

**Key Methods**:
```php
class OpportunityReportService
{
    public function getOpportunities(array $filters, array $options): array
    public function getAggregatedReport(string $groupBy, array $filters, array $options): array
    public function validateFilters(array $filters): array
    public function buildQuery(array $filters, array $options): array
    private function applyACL(User $user, array &$queryParts): void
    private function applyFilters(array $filters, array &$queryParts): void
    private function applyPagination(array $options, array &$queryParts): void
    private function applySorting(array $options, array &$queryParts): void
    private function formatResults(array $results, string $mode): array
    private function calculateAggregations(array $results, string $groupBy): array
}
```

**Dependencies**:
- SuiteCRM DBManager (database abstraction)
- ACLController (permissions)
- User bean (current user context)
- BeanFactory (opportunity bean instantiation)

**Validation Criteria**:
- [ ] Service can query opportunities with no filters
- [ ] All filter parameters work correctly
- [ ] Grouping produces accurate aggregations
- [ ] ACL filtering restricts data appropriately
- [ ] Pagination returns correct subsets
- [ ] Sorting works for all supported fields
- [ ] Performance < 2s for 10,000 records

---

### Step 3: V8 API Endpoint Implementation

**Objective**: Expose reporting service via RESTful JSON API with OAuth2 authentication

**Files to Create**:

1. `custom/Api/V8/Controller/ReportDataController.php`
   - HTTP request handler
   - Parameter extraction and validation
   - Service invocation
   - JSON API response formatting
   - Error handling

2. `custom/Api/V8/Param/ReportDataParams.php`
   - Parameter middleware class
   - Query parameter validation
   - Type coercion
   - Default value application

3. `custom/Api/V8/Config/routes.php`
   - Route registration
   - Middleware binding

**Implementation Details**:

**ReportDataController**:
```php
namespace SuiteCRM\Custom\Api\V8\Controller;

use Api\V8\Controller\BaseController;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class ReportDataController extends BaseController
{
    public function getOpportunityReport(Request $request, Response $response): Response
    {
        // 1. Extract validated parameters from request
        // 2. Get current user from OAuth2 token
        // 3. Invoke OpportunityReportService
        // 4. Format response as JSON API
        // 5. Return response with appropriate headers
    }

    private function formatJsonApiResponse(array $data, array $meta, array $links): array
    private function formatErrorResponse(\Exception $e): array
}
```

**ReportDataParams**:
```php
namespace SuiteCRM\Custom\Api\V8\Param;

use Api\V8\Param\Params;

class ReportDataParams extends Params
{
    public function getFilters(): array
    public function getGroupBy(): ?string
    public function getPage(): array
    public function getSort(): array
    public function getFormat(): string

    protected function validate(): void
}
```

**routes.php**:
```php
<?php
use Api\Core\Loader\CustomLoader;
use Api\V8\Factory\ParamsMiddlewareFactory;

return [
    'routes' => function ($app) {
        $paramsMiddlewareFactory = $app->getContainer()->get(ParamsMiddlewareFactory::class);

        $app->get(
            '/V8/report-data/opportunities',
            'SuiteCRM\Custom\Api\V8\Controller\ReportDataController:getOpportunityReport'
        )->add($paramsMiddlewareFactory->bind(\SuiteCRM\Custom\Api\V8\Param\ReportDataParams::class));
    }
];
```

**Validation Criteria**:
- [ ] Endpoint accessible at `/Api/V8/report-data/opportunities`
- [ ] OAuth2 authentication enforced (401 without token)
- [ ] ACL checks prevent unauthorized access (403)
- [ ] All query parameters parsed correctly
- [ ] JSON API response format valid
- [ ] Error responses follow JSON API error spec
- [ ] Rate limiting headers present
- [ ] CORS headers configured correctly
- [ ] CSV format works with Accept: text/csv

---

### Step 4: Dashboard Dashlet Implementation

**Objective**: Create UI component for accessing report data within SuiteCRM

**Files to Create**:

1. `custom/modules/Home/Dashlets/OpportunityReportDashlet/OpportunityReportDashlet.php`
   - Main dashlet class
   - Configuration handling
   - Data retrieval (via service or API)
   - Display logic

2. `custom/modules/Home/Dashlets/OpportunityReportDashlet/OpportunityReportDashlet.meta.php`
   - Dashlet metadata
   - Configuration form definition
   - Display options

3. `custom/modules/Home/Dashlets/OpportunityReportDashlet/OpportunityReportDashlet.tpl`
   - Smarty template
   - Table display
   - Export button
   - Responsive layout

4. `custom/Extension/modules/Home/Ext/Dashlets/opportunity_report_dashlet.php`
   - Dashlet registration
   - Makes dashlet available in dashlet picker

**Implementation Details**:

**OpportunityReportDashlet.php**:
```php
<?php
require_once('include/Dashlets/DashletGeneric.php');

class OpportunityReportDashlet extends DashletGeneric
{
    public function __construct($id, $options = array())
    {
        parent::__construct($id);
        $this->title = 'Opportunity Report Data';
        $this->searchFields = array(/* config fields */);
        $this->isConfigurable = true;
        $this->hasScript = true;
    }

    public function displayOptions()
    {
        // Render configuration form
    }

    public function display()
    {
        // Fetch data via OpportunityReportService
        // Assign to Smarty template
        // Return rendered HTML
    }

    public function getReportData()
    {
        // Call OpportunityReportService with user's filter settings
    }
}
```

**OpportunityReportDashlet.meta.php**:
```php
<?php
return array(
    'title' => 'Opportunity Report Data',
    'description' => 'Display opportunity reporting data with filters',
    'category' => 'Module Views',
    'icon' => 'icon-signal',
    'offscreen' => false,
);
```

**OpportunityReportDashlet.tpl**:
```smarty
<div class="dashlet-container">
    <div class="dashlet-header">
        <h4>{$title}</h4>
        <button class="btn btn-sm btn-primary export-csv">Export CSV</button>
    </div>

    <div class="dashlet-body">
        {if $error}
            <div class="alert alert-danger">{$error}</div>
        {else}
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Amount</th>
                        <th>Stage</th>
                        <th>Close Date</th>
                        <th>Owner</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$opportunities item=opp}
                    <tr>
                        <td><a href="index.php?module=Opportunities&action=DetailView&record={$opp.id}">{$opp.name}</a></td>
                        <td>{$opp.amount_formatted}</td>
                        <td>{$opp.sales_stage}</td>
                        <td>{$opp.date_closed}</td>
                        <td>{$opp.assigned_user_name}</td>
                    </tr>
                    {/foreach}
                </tbody>
                <tfoot>
                    <tr>
                        <td><strong>Total:</strong></td>
                        <td><strong>{$total_amount}</strong></td>
                        <td colspan="3"><strong>{$total_count} opportunities</strong></td>
                    </tr>
                </tfoot>
            </table>

            {if $pagination.total_pages > 1}
            <div class="pagination">
                <!-- Pagination controls -->
            </div>
            {/if}
        {/if}
    </div>
</div>
```

**opportunity_report_dashlet.php**:
```php
<?php
$dashletData['OpportunityReportDashlet'] = array(
    'module' => 'Home',
    'title' => 'Opportunity Report Data',
    'description' => 'Display opportunity reporting data with customizable filters',
    'category' => 'Reports',
);
```

**Validation Criteria**:
- [ ] Dashlet appears in dashlet picker under "Reports" category
- [ ] Dashlet can be added to Home dashboard
- [ ] Configuration form displays with all filter options
- [ ] Data displays in table format
- [ ] Opportunity names link to DetailView
- [ ] Export CSV button downloads data
- [ ] Refresh button reloads data
- [ ] Totals row shows aggregations
- [ ] Responsive design works on mobile
- [ ] Error messages display appropriately

---

### Step 5: Testing and Validation

**Objective**: Comprehensive testing to ensure quality and requirements satisfaction

**Testing Approach**:

1. **Unit Tests** (PHPUnit)
   - File: `custom/tests/unit/lib/ReportingData/OpportunityReportServiceTest.php`
   - Test all service methods in isolation
   - Mock database and ACL dependencies
   - Validate filter logic, aggregations, pagination

2. **API Integration Tests** (Codeception)
   - File: `custom/tests/api/V8/ReportDataControllerCest.php`
   - Test full API endpoint behavior
   - Validate OAuth2 authentication
   - Test all filter combinations
   - Verify JSON API response format
   - Test error handling

3. **Acceptance Tests** (Codeception/Selenium)
   - File: `custom/tests/acceptance/modules/Home/OpportunityReportDashletCest.php`
   - Test dashlet UI functionality
   - Validate configuration form
   - Test data display and export

4. **Manual Testing Checklist**
   - [ ] API responds to GET requests
   - [ ] All filters work as documented
   - [ ] Grouping produces correct aggregations
   - [ ] CSV export downloads valid file
   - [ ] Dashlet displays on dashboard
   - [ ] Dashlet configuration saves and applies
   - [ ] ACL properly restricts data
   - [ ] Rate limiting works
   - [ ] Performance meets requirements (< 2s for 10k records)
   - [ ] Error messages are clear and actionable

**Validation Criteria**:
- [ ] All unit tests pass
- [ ] All integration tests pass
- [ ] All acceptance tests pass
- [ ] Manual testing checklist complete
- [ ] No security vulnerabilities identified
- [ ] Performance benchmarks met
- [ ] Code passes PSR-12 style checks

---

## 3. File Structure Summary

```
custom/
├── docs/
│   └── story-10/
│       ├── REQUIREMENTS.md ✅
│       ├── API_SPECIFICATION.md ✅
│       └── IMPLEMENTATION_PLAN.md ✅
├── lib/
│   └── ReportingData/
│       └── OpportunityReportService.php [STEP 2]
├── Api/
│   └── V8/
│       ├── Controller/
│       │   └── ReportDataController.php [STEP 3]
│       ├── Param/
│       │   └── ReportDataParams.php [STEP 3]
│       └── Config/
│           └── routes.php [STEP 3]
├── modules/
│   └── Home/
│       └── Dashlets/
│           └── OpportunityReportDashlet/
│               ├── OpportunityReportDashlet.php [STEP 4]
│               ├── OpportunityReportDashlet.meta.php [STEP 4]
│               └── OpportunityReportDashlet.tpl [STEP 4]
├── Extension/
│   └── modules/
│       └── Home/
│           └── Ext/
│               └── Dashlets/
│                   └── opportunity_report_dashlet.php [STEP 4]
└── tests/
    ├── unit/
    │   └── lib/
    │       └── ReportingData/
    │           └── OpportunityReportServiceTest.php [STEP 5]
    ├── api/
    │   └── V8/
    │       └── ReportDataControllerCest.php [STEP 5]
    └── acceptance/
        └── modules/
            └── Home/
                └── OpportunityReportDashletCest.php [STEP 5]
```

---

## 4. Dependencies and Prerequisites

### Required SuiteCRM Components
- ✅ SuiteCRM 7.15 installed and configured
- ✅ Opportunities module enabled
- ✅ OAuth2 server configured
- ✅ V8 API functional
- ✅ PHP 8.1+ with required extensions
- ✅ Database with opportunities data

### External Dependencies
- None (all dependencies already in SuiteCRM)

### Configuration Requirements
- OAuth2 client credentials for testing
- Admin user account for ACL testing
- Regular user account for permission testing
- Test data: Minimum 100 opportunities across multiple stages

---

## 5. Risk Mitigation Strategy

| Risk | Mitigation |
|------|------------|
| **Query performance degradation** | Implement pagination, query optimization, add database indexes |
| **ACL complexity** | Leverage existing ACL system, comprehensive testing |
| **API route conflicts** | Use custom namespace, test route registration |
| **Upgrade safety** | All code in custom/, no core modifications |
| **Security vulnerabilities** | Use parameterized queries, input validation, ACL checks |

---

## 6. Success Criteria Summary

### Functional Requirements ✅
- API endpoint operational with all specified filters
- Grouping and aggregations accurate
- CSV export functional
- Dashboard dashlet displays data
- OAuth2 authentication enforced
- ACL properly restricts access

### Non-Functional Requirements ✅
- Response time < 2 seconds for 10k records
- Code follows PSR-4 and PSR-12 standards
- All code in custom/ directory
- Comprehensive documentation
- No core file modifications

### Testing Requirements ✅
- Unit tests written and passing
- API integration tests passing
- Dashlet acceptance tests passing
- Manual testing complete
- Security review complete

---

## 7. Timeline Estimate

| Step | Estimated Effort | Status |
|------|------------------|--------|
| Step 1: Requirements & Docs | 4 hours | ✅ COMPLETED |
| Step 2: Backend Service | 6 hours | ⏳ Pending |
| Step 3: API Endpoint | 4 hours | ⏳ Pending |
| Step 4: Dashboard Dashlet | 4 hours | ⏳ Pending |
| Step 5: Testing & Validation | 6 hours | ⏳ Pending |
| **Total** | **24 hours** | **4% Complete** |

---

## 8. Next Actions

**Immediate Next Step**: Proceed to Step 2 - Backend Service Implementation

**Action Items**:
1. Create `OpportunityReportService.php`
2. Implement query building logic
3. Add filter validation
4. Integrate ACL checks
5. Implement aggregation calculations
6. Add pagination and sorting
7. Test service independently

---

**END OF IMPLEMENTATION PLAN**
