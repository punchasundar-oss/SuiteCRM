# Story 10: Reporting Data - Requirements Specification

## Document Control
- **Story ID**: 10
- **Story Title**: test
- **Story Description**: need reporting data
- **Parent Feature**: Reporting & Data Export
- **Created**: 2026-02-23
- **Status**: Requirements Locked
- **Branch**: feature/10-test

---

## 1. Executive Summary

This specification defines the requirements for Story 10: "Reporting Data". After thorough analysis of the SuiteCRM 7.15 codebase and existing reporting infrastructure, the requirement has been clarified as: **Creation of a V8 REST API endpoint for programmatic access to Opportunity report data in JSON format**.

### Business Value
- Enables external system integration (BI tools, data warehouses, mobile apps)
- Addresses gap in current reporting (AOR_Reports is UI-focused, lacks API access)
- Maintains security and ACL compliance
- Provides foundation for future reporting API expansion

---

## 2. Scope Definition

### 2.1 In Scope
✅ V8 REST API endpoint for Opportunity data retrieval
✅ JSON response format following JSON API specification
✅ Filtering by: sales_stage, date_closed range, assigned_user_id, amount range, lead_source
✅ Grouping and aggregation support (COUNT, SUM, AVG)
✅ OAuth2 authentication and authorization
✅ ACL and team security enforcement
✅ Dashboard dashlet for UI access
✅ CSV export capability (leverage existing utilities)
✅ Pagination support for large datasets
✅ Comprehensive error handling

### 2.2 Out of Scope
❌ Modification of existing AOR_Reports module
❌ Support for modules other than Opportunities (future enhancement)
❌ Real-time streaming or WebSocket support
❌ Custom chart/visualization generation (use existing AOR_Charts)
❌ Scheduled report delivery (use existing AOR_Scheduled_Reports)
❌ PDF report generation (use existing AOR PDF functionality)
❌ Multi-module join queries (single module focus)
❌ User interface for building custom reports (use AOR_Reports for that)

---

## 3. Detailed Requirements

### 3.1 Module Selection

**Primary Module**: `Opportunities`

**Rationale**:
- Most frequently reported module in CRM systems
- Direct revenue impact and business value
- Rich field set with relationships (Accounts, Contacts)
- Common filtering needs (stage, date, amount)
- High external integration demand (BI, forecasting tools)

### 3.2 Field Specification

#### Core Fields (Always Included)
| Field Name | Type | Description | Source |
|------------|------|-------------|--------|
| `id` | varchar(36) | UUID identifier | opportunities.id |
| `name` | varchar(50) | Opportunity name | opportunities.name |
| `amount` | decimal(26,6) | Deal value | opportunities.amount |
| `amount_usdollar` | decimal(26,6) | Normalized USD amount | opportunities.amount_usdollar |
| `sales_stage` | varchar(255) | Pipeline stage | opportunities.sales_stage |
| `probability` | int(3) | Win probability % | opportunities.probability |
| `date_closed` | date | Expected/actual close | opportunities.date_closed |
| `lead_source` | varchar(50) | Opportunity origin | opportunities.lead_source |
| `date_entered` | datetime | Creation timestamp | opportunities.date_entered |
| `date_modified` | datetime | Last update timestamp | opportunities.date_modified |
| `assigned_user_id` | varchar(36) | Owner user ID | opportunities.assigned_user_id |
| `assigned_user_name` | varchar(255) | Owner full name | users.user_name (JOIN) |
| `account_id` | varchar(36) | Related account ID | opportunities.account_id |
| `account_name` | varchar(255) | Related account name | accounts.name (JOIN) |

#### Extended Fields (Optional, Query Parameter: `fields=extended`)
| Field Name | Type | Description | Source |
|------------|------|-------------|--------|
| `description` | text | Opportunity notes | opportunities.description |
| `next_step` | varchar(100) | Next action | opportunities.next_step |
| `opportunity_type` | varchar(255) | Deal type | opportunities.opportunity_type |
| `campaign_id` | varchar(36) | Source campaign | opportunities.campaign_id |
| `campaign_name` | varchar(255) | Campaign name | campaigns.name (JOIN) |
| `created_by` | varchar(36) | Creator user ID | opportunities.created_by |
| `modified_user_id` | varchar(36) | Last modifier ID | opportunities.modified_user_id |

### 3.3 Filter Parameters

#### Supported Query Parameters
| Parameter | Type | Operator | Example | Description |
|-----------|------|----------|---------|-------------|
| `sales_stage` | string[] | IN | `?sales_stage=Prospecting,Qualification` | Filter by stage(s) |
| `sales_stage_exclude` | string[] | NOT IN | `?sales_stage_exclude=Closed Lost` | Exclude stage(s) |
| `date_closed_from` | date | >= | `?date_closed_from=2026-01-01` | Close date range start |
| `date_closed_to` | date | <= | `?date_closed_to=2026-12-31` | Close date range end |
| `date_closed_period` | enum | RELATIVE | `?date_closed_period=this_quarter` | Relative date range |
| `amount_min` | decimal | >= | `?amount_min=10000.00` | Minimum deal value |
| `amount_max` | decimal | <= | `?amount_max=100000.00` | Maximum deal value |
| `assigned_user_id` | varchar(36) | = | `?assigned_user_id=abc-123` | Filter by owner |
| `assigned_user_id_current` | boolean | = | `?assigned_user_id_current=1` | Current user's opps |
| `lead_source` | string[] | IN | `?lead_source=Web,Cold Call` | Filter by source(s) |
| `account_id` | varchar(36) | = | `?account_id=xyz-789` | Filter by account |
| `probability_min` | int | >= | `?probability_min=75` | Minimum win % |
| `probability_max` | int | <= | `?probability_max=100` | Maximum win % |

#### Date Period Values (date_closed_period)
- `today` - Current day
- `yesterday` - Previous day
- `this_week` - Current week (Sunday-Saturday)
- `last_week` - Previous week
- `this_month` - Current calendar month
- `last_month` - Previous calendar month
- `this_quarter` - Current fiscal quarter
- `last_quarter` - Previous fiscal quarter
- `this_year` - Current calendar year
- `last_year` - Previous calendar year

### 3.4 Grouping and Aggregation

#### Grouping Parameter
| Parameter | Type | Values | Example |
|-----------|------|--------|---------|
| `group_by` | string | `sales_stage`, `assigned_user_id`, `lead_source`, `account_id` | `?group_by=sales_stage` |

#### Aggregation Functions (Applied When Grouped)
| Aggregation | Field | Description |
|-------------|-------|-------------|
| `COUNT(*)` | - | Number of opportunities |
| `SUM(amount_usdollar)` | amount_usdollar | Total pipeline value |
| `AVG(amount_usdollar)` | amount_usdollar | Average deal size |
| `MIN(amount_usdollar)` | amount_usdollar | Smallest deal |
| `MAX(amount_usdollar)` | amount_usdollar | Largest deal |
| `AVG(probability)` | probability | Average win probability |
| `SUM(amount * probability / 100)` | calculated | Weighted pipeline |

#### Aggregation Response Format (When Grouped)
```json
{
  "data": [
    {
      "group_value": "Prospecting",
      "group_label": "Prospecting",
      "count": 45,
      "sum_amount_usdollar": "2250000.00",
      "avg_amount_usdollar": "50000.00",
      "min_amount_usdollar": "5000.00",
      "max_amount_usdollar": "500000.00",
      "avg_probability": 10,
      "weighted_pipeline": "225000.00"
    }
  ],
  "meta": {
    "total_count": 150,
    "group_by": "sales_stage",
    "aggregations": {
      "grand_total_amount": "5000000.00",
      "grand_avg_amount": "33333.33",
      "grand_weighted_pipeline": "3500000.00"
    }
  }
}
```

### 3.5 Pagination

#### Pagination Parameters
| Parameter | Type | Default | Max | Description |
|-----------|------|---------|-----|-------------|
| `page[number]` | int | 1 | - | Current page number |
| `page[size]` | int | 50 | 500 | Records per page |

#### Pagination Response Headers
```
X-Total-Count: 1247
X-Page-Count: 25
X-Current-Page: 1
X-Per-Page: 50
Link: <url?page[number]=2>; rel="next", <url?page[number]=25>; rel="last"
```

### 3.6 Sorting

#### Sorting Parameter
| Parameter | Type | Example | Description |
|-----------|------|---------|-------------|
| `sort` | string | `?sort=-amount_usdollar,date_closed` | Sort order (prefix `-` for DESC) |

**Supported Sort Fields**:
- `name`, `amount`, `amount_usdollar`, `sales_stage`, `probability`
- `date_closed`, `date_entered`, `date_modified`, `lead_source`
- `assigned_user_name`, `account_name`

---

## 4. Data Consumers

### 4.1 Primary Consumer: External Systems via API

**Target Integrations**:
1. **Business Intelligence Tools**
   - Tableau, Power BI, Looker, Qlik
   - ETL pipelines for data warehousing
   - Real-time dashboard feeds

2. **Mobile Applications**
   - iOS/Android native apps
   - Progressive Web Apps (PWA)
   - Offline-first sync architectures

3. **Third-Party Services**
   - Marketing automation platforms
   - Forecasting/analytics tools
   - Custom reporting dashboards

4. **Data Warehouses**
   - Scheduled data extraction
   - Historical trend analysis
   - Cross-system correlation

**Technical Requirements**:
- RESTful JSON API
- OAuth2 bearer token authentication
- Rate limiting: 1000 requests/hour per token
- Response time: < 2 seconds for 10,000 records
- Concurrent requests: Up to 10 per token

### 4.2 Secondary Consumer: SuiteCRM Dashboard

**Implementation**: Dashboard Dashlet
- **Location**: Home dashboard
- **Name**: "Opportunity Report Data"
- **Configuration**: Filter presets, refresh interval
- **Display**: Tabular summary with aggregations
- **Export**: CSV download button
- **Responsive**: Mobile-friendly layout

---

## 5. Response Format

### 5.1 JSON API Specification Compliance

The API will follow the [JSON API v1.0 specification](https://jsonapi.org/format/) for consistency with SuiteCRM V8 API patterns.

#### Success Response (200 OK)
```json
{
  "data": [
    {
      "type": "Opportunities",
      "id": "3fa85f64-5717-4562-b3fc-2c963f66afa6",
      "attributes": {
        "name": "Acme Corp - Enterprise License",
        "amount": "150000.00",
        "amount_usdollar": "150000.00",
        "sales_stage": "Proposal/Price Quote",
        "probability": 75,
        "date_closed": "2026-03-31",
        "lead_source": "Web Site",
        "date_entered": "2026-01-15T10:30:00+00:00",
        "date_modified": "2026-02-20T14:22:00+00:00",
        "assigned_user_id": "1",
        "assigned_user_name": "Admin User",
        "account_id": "abc-123-def-456",
        "account_name": "Acme Corporation"
      }
    }
  ],
  "meta": {
    "total_count": 1247,
    "returned_count": 50,
    "page": {
      "number": 1,
      "size": 50,
      "total_pages": 25
    },
    "aggregations": {
      "total_amount_usdollar": "52750000.00",
      "average_amount_usdollar": "42290.06",
      "total_count": 1247,
      "weighted_pipeline": "38500000.00"
    },
    "filters_applied": {
      "sales_stage": ["Prospecting", "Qualification"],
      "date_closed_period": "this_quarter"
    }
  },
  "links": {
    "self": "/Api/V8/custom/report-data/opportunities?page[number]=1&page[size]=50",
    "first": "/Api/V8/custom/report-data/opportunities?page[number]=1&page[size]=50",
    "next": "/Api/V8/custom/report-data/opportunities?page[number]=2&page[size]=50",
    "last": "/Api/V8/custom/report-data/opportunities?page[number]=25&page[size]=50"
  }
}
```

#### Error Response (400 Bad Request)
```json
{
  "errors": [
    {
      "status": "400",
      "code": "INVALID_PARAMETER",
      "title": "Invalid Query Parameter",
      "detail": "Parameter 'sales_stage' contains invalid value 'InvalidStage'",
      "source": {
        "parameter": "sales_stage"
      }
    }
  ]
}
```

#### Error Response (401 Unauthorized)
```json
{
  "errors": [
    {
      "status": "401",
      "code": "UNAUTHORIZED",
      "title": "Authentication Required",
      "detail": "Valid OAuth2 bearer token required. Obtain token via /Api/access_token endpoint."
    }
  ]
}
```

#### Error Response (403 Forbidden)
```json
{
  "errors": [
    {
      "status": "403",
      "code": "INSUFFICIENT_PERMISSIONS",
      "title": "Access Denied",
      "detail": "User does not have 'list' access to Opportunities module",
      "source": {
        "module": "Opportunities",
        "required_permission": "list"
      }
    }
  ]
}
```

### 5.2 CSV Export Format

When requested with `Accept: text/csv` header or `?format=csv` parameter:

```csv
ID,Name,Amount,Amount (USD),Sales Stage,Probability,Close Date,Lead Source,Account,Assigned To
"3fa85f64-5717-4562-b3fc-2c963f66afa6","Acme Corp - Enterprise License","150000.00","150000.00","Proposal/Price Quote","75","2026-03-31","Web Site","Acme Corporation","Admin User"
```

**CSV Specifications**:
- UTF-8 encoding with BOM
- RFC 4180 compliant
- Field delimiter: `,` (comma)
- Text qualifier: `"` (double quote)
- Line terminator: `\r\n` (CRLF)
- Header row: Yes (field labels)
- Date format: `Y-m-d` (ISO 8601)
- Datetime format: `Y-m-d\TH:i:sP` (ISO 8601 with timezone)
- Decimal separator: `.` (period)
- Thousand separator: None

---

## 6. Access Control Rules

### 6.1 Authentication

**Method**: OAuth2 Bearer Token
- **Token Endpoint**: `/Api/access_token`
- **Grant Type**: `password` (Resource Owner Password Credentials)
- **Scopes**: `read` (minimum), `write` (for future updates)
- **Token Lifetime**: 3600 seconds (1 hour)
- **Refresh Token**: Supported (7-day lifetime)

**Example Token Request**:
```http
POST /Api/access_token HTTP/1.1
Content-Type: application/x-www-form-urlencoded

grant_type=password&client_id=suitecrm_client&client_secret=secret&username=admin&password=pass&scope=read
```

### 6.2 Authorization (ACL Checks)

#### Module-Level Access
**Required Permission**: `list` access to `Opportunities` module

**Check Logic**:
```php
ACLController::checkAccess('Opportunities', 'list', true)
```

**Failure Response**: 403 Forbidden with error detail

#### Record-Level Access

**Security Rules Applied**:
1. **Team Assignment**: User must be member of opportunity's assigned team(s)
2. **Security Groups**: User must belong to security group with access
3. **Owner Filter**: Users see records where `assigned_user_id = current_user_id` OR record is shared
4. **Role Hierarchy**: Managers see subordinates' records

**Implementation**:
- Leverage `SugarBean::get_list()` with ACL enforcement
- Apply `SecurityGroup::filterRecordsBySecurityGroups()`
- Use `TeamSecurity::addTeamSecurityWhere()` for SQL filtering

### 6.3 Role-Based Access

| Role | Access Scope | Filter Applied |
|------|--------------|----------------|
| **Admin** | All opportunities | None (full access) |
| **Manager** | Department/team opportunities | `team_id IN (user_teams)` |
| **User** | Own + shared opportunities | `assigned_user_id = current_user_id OR shared = 1` |
| **Read-Only User** | View only (no export) | Same as User + export disabled |

### 6.4 Field-Level Security

**Sensitive Fields**: None restricted by default for Opportunities

**Future Enhancement**: Support for custom field-level ACL
- Admin configurable field visibility rules
- `$bean->ACLAccess('field_name')` checks

### 6.5 Rate Limiting

**Limits by User Role**:
| Role | Requests/Hour | Requests/Minute | Burst Limit |
|------|---------------|-----------------|-------------|
| Admin | 5000 | 100 | 20 |
| Manager | 2000 | 50 | 10 |
| User | 1000 | 30 | 5 |
| API User | 10000 | 200 | 50 |

**Exceeded Limit Response**: 429 Too Many Requests
```json
{
  "errors": [
    {
      "status": "429",
      "code": "RATE_LIMIT_EXCEEDED",
      "title": "Too Many Requests",
      "detail": "Rate limit of 1000 requests/hour exceeded. Retry after 3600 seconds.",
      "meta": {
        "retry_after": 3600,
        "limit": 1000,
        "remaining": 0,
        "reset": "2026-02-23T15:00:00+00:00"
      }
    }
  ]
}
```

---

## 7. Technical Implementation Details

### 7.1 API Endpoint

**Method**: `GET`
**Path**: `/Api/V8/custom/report-data/opportunities`
**Base URL**: `https://{suitecrm-domain}/Api/V8/custom/report-data/opportunities`

**Full Example**:
```
GET /Api/V8/custom/report-data/opportunities?sales_stage=Prospecting,Qualification&date_closed_period=this_quarter&page[size]=100&sort=-amount_usdollar
Authorization: Bearer {access_token}
Accept: application/vnd.api+json
```

### 7.2 Component Architecture

#### Backend Service
**File**: `custom/lib/ReportingData/OpportunityReportService.php`
**Namespace**: `SuiteCRM\Custom\ReportingData`
**Class**: `OpportunityReportService`

**Responsibilities**:
- Query building with filters, grouping, aggregations
- ACL enforcement (module, record, field levels)
- Data transformation (bean to DTO)
- Pagination logic
- Performance optimization (query caching, index hints)

**Key Methods**:
```php
public function getOpportunities(array $filters, array $options): array
public function getAggregatedReport(string $groupBy, array $filters): array
public function validateFilters(array $filters): void
public function applyACL(User $currentUser, array &$queryParts): void
public function buildQuery(array $filters, array $options): string
```

#### API Controller
**File**: `custom/Api/V8/Controller/ReportDataController.php`
**Namespace**: `SuiteCRM\Custom\Api\V8\Controller`
**Class**: `ReportDataController`

**Responsibilities**:
- HTTP request handling
- Parameter validation and sanitization
- Service invocation
- Response formatting (JSON API spec)
- Error handling and logging
- OAuth2 authentication verification

**Key Methods**:
```php
public function getOpportunityReport(Request $request, Response $response): Response
private function validateParameters(array $params): array
private function formatJsonApiResponse(array $data, array $meta): array
private function handleError(\Exception $e): Response
```

#### Parameter Middleware
**File**: `custom/Api/V8/Param/ReportDataParams.php`
**Namespace**: `SuiteCRM\Custom\Api\V8\Param`
**Class**: `ReportDataParams`

**Responsibilities**:
- Query parameter parsing
- Type coercion and validation
- Default value application
- Parameter sanitization

#### Route Configuration
**File**: `custom/Api/V8/Config/routes.php`

**Route Definition**:
```php
$app->get('/V8/report-data/opportunities', 'SuiteCRM\Custom\Api\V8\Controller\ReportDataController:getOpportunityReport')
    ->add($paramsMiddlewareFactory->bind(\SuiteCRM\Custom\Api\V8\Param\ReportDataParams::class));
```

### 7.3 Database Queries

#### Base Query (Non-Grouped)
```sql
SELECT
    o.id,
    o.name,
    o.amount,
    o.amount_usdollar,
    o.sales_stage,
    o.probability,
    o.date_closed,
    o.lead_source,
    o.date_entered,
    o.date_modified,
    o.assigned_user_id,
    CONCAT(u.first_name, ' ', u.last_name) as assigned_user_name,
    o.account_id,
    a.name as account_name
FROM opportunities o
LEFT JOIN users u ON o.assigned_user_id = u.id AND u.deleted = 0
LEFT JOIN accounts a ON o.account_id = a.id AND a.deleted = 0
WHERE o.deleted = 0
    AND [ACL_FILTERS]
    AND [USER_FILTERS]
ORDER BY [SORT_FIELDS]
LIMIT [OFFSET], [PAGE_SIZE]
```

#### Aggregated Query (Grouped)
```sql
SELECT
    o.sales_stage as group_value,
    COUNT(*) as count,
    SUM(o.amount_usdollar) as sum_amount_usdollar,
    AVG(o.amount_usdollar) as avg_amount_usdollar,
    MIN(o.amount_usdollar) as min_amount_usdollar,
    MAX(o.amount_usdollar) as max_amount_usdollar,
    AVG(o.probability) as avg_probability,
    SUM(o.amount_usdollar * o.probability / 100) as weighted_pipeline
FROM opportunities o
LEFT JOIN users u ON o.assigned_user_id = u.id AND u.deleted = 0
LEFT JOIN accounts a ON o.account_id = a.id AND a.deleted = 0
WHERE o.deleted = 0
    AND [ACL_FILTERS]
    AND [USER_FILTERS]
GROUP BY o.sales_stage
ORDER BY sum_amount_usdollar DESC
```

**Performance Considerations**:
- Index on: `opportunities.deleted`, `opportunities.sales_stage`, `opportunities.date_closed`, `opportunities.assigned_user_id`
- Query timeout: 30 seconds
- Result cache: 5 minutes (Memcached/Redis if available)
- Explain plan validation for queries > 10,000 records

---

## 8. Dashboard Dashlet Specification

### 8.1 Dashlet Details

**Name**: Opportunity Report Data Dashlet
**Module**: Home
**Location**: `custom/modules/Home/Dashlets/OpportunityReportDashlet/`
**Files**:
- `OpportunityReportDashlet.php` - Main class
- `OpportunityReportDashlet.meta.php` - Configuration metadata
- `OpportunityReportDashlet.tpl` - Smarty template

### 8.2 Dashlet Features

**Configuration Options**:
- Sales stage filter (multi-select)
- Date closed period (dropdown)
- Assigned user filter (user picker)
- Amount range (min/max inputs)
- Refresh interval (5/10/15/30 minutes)
- Display mode (table/aggregated)
- Records per page (10/25/50)

**Display**:
- Tabular view with sortable columns
- Aggregated totals row
- Color-coded sales stages
- Clickable opportunity names (link to DetailView)
- Export to CSV button
- Refresh button
- Configuration button

**Responsive Design**:
- Desktop: Full table with all columns
- Tablet: Reduced columns (name, amount, stage, close date)
- Mobile: Card-based layout with key fields

### 8.3 Dashlet Configuration Form

**Form Fields**:
```php
'sales_stages' => [
    'label' => 'Sales Stages',
    'type' => 'enum',
    'multi' => true,
    'options' => 'sales_stage_dom'
],
'date_period' => [
    'label' => 'Date Closed Period',
    'type' => 'enum',
    'options' => 'date_time_period_list',
    'default' => 'this_quarter'
],
'assigned_user' => [
    'label' => 'Assigned To',
    'type' => 'user',
    'default' => 'current_user'
],
'amount_min' => [
    'label' => 'Min Amount',
    'type' => 'currency',
    'default' => ''
],
'amount_max' => [
    'label' => 'Max Amount',
    'type' => 'currency',
    'default' => ''
],
'display_mode' => [
    'label' => 'Display Mode',
    'type' => 'enum',
    'options' => ['table' => 'Table', 'aggregated' => 'Aggregated'],
    'default' => 'table'
],
'refresh_interval' => [
    'label' => 'Auto Refresh',
    'type' => 'enum',
    'options' => [
        '0' => 'Disabled',
        '300' => '5 minutes',
        '600' => '10 minutes',
        '900' => '15 minutes',
        '1800' => '30 minutes'
    ],
    'default' => '0'
]
```

---

## 9. Success Criteria

### 9.1 Functional Requirements

✅ **FR-1**: API endpoint `/Api/V8/custom/report-data/opportunities` returns valid JSON
✅ **FR-2**: All specified filters work correctly and return filtered datasets
✅ **FR-3**: Grouping and aggregations produce accurate calculations
✅ **FR-4**: Pagination returns correct page subsets with proper metadata
✅ **FR-5**: Sorting works for all specified fields in ASC/DESC order
✅ **FR-6**: CSV export produces RFC 4180 compliant output
✅ **FR-7**: OAuth2 authentication is enforced (401 for missing/invalid tokens)
✅ **FR-8**: ACL checks prevent unauthorized access (403 for insufficient permissions)
✅ **FR-9**: Record-level security filters data based on team/owner/security groups
✅ **FR-10**: Dashboard dashlet displays report data with configuration options
✅ **FR-11**: All error responses follow JSON API error specification
✅ **FR-12**: Rate limiting enforces request limits per role

### 9.2 Non-Functional Requirements

✅ **NFR-1**: Response time < 2 seconds for 10,000 records (95th percentile)
✅ **NFR-2**: API can handle 10 concurrent requests per token
✅ **NFR-3**: Code follows PSR-4 autoloading standards
✅ **NFR-4**: All code resides in `custom/` directory (upgrade-safe)
✅ **NFR-5**: PHPDoc comments on all public methods
✅ **NFR-6**: No core SuiteCRM files modified
✅ **NFR-7**: Database queries use SuiteCRM DB abstraction (no raw SQL)
✅ **NFR-8**: Proper error logging to SuiteCRM log files
✅ **NFR-9**: Memory usage < 128MB for typical requests
✅ **NFR-10**: Code passes PHP_CodeSniffer PSR-12 checks

### 9.3 Testing Requirements

✅ **TR-1**: Unit tests for OpportunityReportService (PHPUnit)
✅ **TR-2**: Integration tests for API endpoint (Codeception)
✅ **TR-3**: ACL tests verify permission enforcement
✅ **TR-4**: Filter tests validate all query parameters
✅ **TR-5**: Aggregation tests verify calculation accuracy
✅ **TR-6**: Pagination tests ensure correct page boundaries
✅ **TR-7**: CSV export tests validate format compliance
✅ **TR-8**: Load tests confirm performance requirements
✅ **TR-9**: Security tests attempt unauthorized access
✅ **TR-10**: Dashlet tests verify configuration and display

---

## 10. Assumptions and Constraints

### 10.1 Assumptions

1. SuiteCRM 7.15 is running on PHP 8.1+ with required extensions
2. OAuth2 server is configured and functional
3. Opportunities module is enabled and contains data
4. Users have existing roles/permissions configured
5. Database has appropriate indexes on opportunities table
6. Memcached or Redis available for query caching (optional but recommended)
7. API consumers can handle JSON API specification format
8. External systems will respect rate limits
9. CSV exports will be used for datasets < 50,000 records
10. Dashboard dashlets will be used primarily by managers/executives

### 10.2 Constraints

1. **Upgrade Safety**: All code must reside in `custom/` directory
2. **Performance**: Queries must complete within 30 seconds (DB timeout)
3. **Memory**: PHP memory_limit must be >= 256MB
4. **Compatibility**: Must work with MySQL 5.7+, MariaDB 10.2+, MSSQL 2016+
5. **Security**: Must comply with OWASP Top 10 security guidelines
6. **Standards**: Must follow SuiteCRM coding standards and PSR-12
7. **Dependencies**: Cannot introduce new Composer dependencies
8. **Backward Compatibility**: Must not break existing AOR_Reports functionality
9. **Scalability**: Must handle up to 100,000 opportunities efficiently
10. **Browser Support**: Dashlet must work on Chrome, Firefox, Safari, Edge (latest 2 versions)

---

## 11. Risk Assessment

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| Performance degradation with large datasets | Medium | High | Implement pagination, query optimization, caching |
| ACL bypass vulnerability | Low | Critical | Thorough testing, code review, leverage existing ACL |
| OAuth2 token leakage | Low | High | HTTPS enforcement, secure token storage guidelines |
| Database query injection | Low | Critical | Use SuiteCRM query builder, parameterized queries |
| Rate limit bypass | Medium | Medium | Implement token-based tracking, redis counters |
| Upgrade breaks custom code | Medium | High | Follow upgrade-safe patterns, comprehensive testing |
| CSV export timeout for huge datasets | Medium | Medium | Implement async export via job queue (future) |
| Dashlet performance impact | Low | Medium | Lazy loading, configurable refresh intervals |
| Cross-origin request issues | Medium | Low | Configure CORS headers appropriately |
| Data exposure via error messages | Low | High | Sanitize error responses, log sensitive details only |

---

## 12. Dependencies

### 12.1 Technical Dependencies

- **SuiteCRM Core**: 7.15.x (current)
- **PHP**: 8.1 - 8.4
- **Slim Framework**: 3.8 (existing V8 API)
- **OAuth2 Server**: league/oauth2-server (existing)
- **Database**: MySQL 5.7+, MariaDB 10.2+, or MSSQL 2016+
- **Web Server**: Apache 2.4+ or IIS with URL rewrite

### 12.2 Module Dependencies

- **Opportunities**: Core module must be enabled
- **Users**: For assigned_user relationships
- **Accounts**: For account relationships (optional)
- **Teams**: For team security filtering
- **SecurityGroups**: For security group filtering (if module enabled)
- **ACLActions**: For permission checks

### 12.3 Configuration Dependencies

- **OAuth2 Clients**: At least one OAuth2 client configured
- **ACL Roles**: Roles must have Opportunities module permissions set
- **Team Assignments**: Opportunities must have team assignments for proper filtering
- **.htaccess**: API routes must be accessible (rewrite rules configured)

---

## 13. Delivery Checklist

### 13.1 Code Deliverables

- [ ] `custom/lib/ReportingData/OpportunityReportService.php`
- [ ] `custom/Api/V8/Controller/ReportDataController.php`
- [ ] `custom/Api/V8/Param/ReportDataParams.php`
- [ ] `custom/Api/V8/Config/routes.php`
- [ ] `custom/modules/Home/Dashlets/OpportunityReportDashlet/OpportunityReportDashlet.php`
- [ ] `custom/modules/Home/Dashlets/OpportunityReportDashlet/OpportunityReportDashlet.meta.php`
- [ ] `custom/modules/Home/Dashlets/OpportunityReportDashlet/OpportunityReportDashlet.tpl`
- [ ] `custom/Extension/modules/Home/Ext/Dashlets/OpportunityReportDashlet.php`

### 13.2 Documentation Deliverables

- [x] `custom/docs/story-10/REQUIREMENTS.md` (this document)
- [ ] `custom/docs/story-10/API_SPECIFICATION.md`
- [ ] `custom/docs/story-10/IMPLEMENTATION_NOTES.md`
- [ ] `custom/docs/story-10/TESTING_GUIDE.md`
- [ ] `custom/docs/story-10/USER_GUIDE.md`

### 13.3 Testing Deliverables

- [ ] `custom/tests/unit/lib/ReportingData/OpportunityReportServiceTest.php`
- [ ] `custom/tests/api/V8/ReportDataControllerCest.php`
- [ ] `custom/tests/acceptance/modules/Home/OpportunityReportDashletCest.php`

---

## 14. Approval Sign-Off

### 14.1 Requirements Approval

**Status**: ✅ LOCKED
**Date**: 2026-02-23
**Approved By**: Automated Analysis (No Human in Loop)
**Rationale**: Requirements derived from comprehensive codebase analysis and industry best practices

### 14.2 Change Control

Any changes to locked requirements must be documented as change requests with:
- Change request ID
- Reason for change
- Impact assessment
- Approval date
- Version increment

**Current Version**: 1.0
**Last Updated**: 2026-02-23
**Next Review**: Upon Step 5 completion

---

## 15. Glossary

| Term | Definition |
|------|------------|
| **ACL** | Access Control List - SuiteCRM's permission system |
| **AOR** | Advanced Open Reports - SuiteCRM's built-in reporting module |
| **Bean** | SuiteCRM's term for a data model object (extends SugarBean) |
| **Dashlet** | Widget that can be added to the SuiteCRM dashboard |
| **JSON API** | REST API specification (jsonapi.org) for structured JSON responses |
| **OAuth2** | Industry-standard authorization framework (RFC 6749) |
| **PSR-4** | PHP autoloading standard (PHP Framework Interop Group) |
| **SugarBean** | Base class for all SuiteCRM data models |
| **Team Security** | SuiteCRM's record-level security based on team membership |
| **V8 API** | SuiteCRM's modern REST API (8th generation) |
| **Vardefs** | Variable definitions - SuiteCRM's field metadata system |

---

## 16. References

- SuiteCRM 7.15 Documentation: https://docs.suitecrm.com/
- JSON API Specification v1.0: https://jsonapi.org/format/
- OAuth2 RFC 6749: https://tools.ietf.org/html/rfc6749
- PSR-4 Autoloading: https://www.php-fig.org/psr/psr-4/
- PSR-12 Coding Style: https://www.php-fig.org/psr/psr-12/
- RFC 4180 CSV Format: https://tools.ietf.org/html/rfc4180

---

**END OF REQUIREMENTS SPECIFICATION**
