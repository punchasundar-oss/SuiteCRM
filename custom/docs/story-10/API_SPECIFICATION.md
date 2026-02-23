# Story 10: Reporting Data - API Specification

## Document Information
- **Version**: 1.0
- **Date**: 2026-02-23
- **Status**: Locked
- **Related Documents**: REQUIREMENTS.md

---

## 1. Overview

This document provides the complete API specification for the Opportunity Reporting Data endpoint. It includes request/response formats, authentication flows, error handling, and example usage.

---

## 2. Base Information

### 2.1 Endpoint Details

| Property | Value |
|----------|-------|
| **Method** | GET |
| **Path** | `/Api/V8/report-data/opportunities` |
| **Base URL** | `https://{domain}/Api/V8/report-data/opportunities` |
| **Protocol** | HTTPS (required) |
| **Content Type** | application/vnd.api+json |
| **Authentication** | OAuth2 Bearer Token |
| **Rate Limit** | 1000 requests/hour (User), 5000 requests/hour (Admin) |

### 2.2 OpenAPI Summary

```yaml
openapi: 3.0.0
info:
  title: SuiteCRM Opportunity Report Data API
  version: 1.0.0
  description: Programmatic access to Opportunity reporting data
servers:
  - url: https://{domain}/Api/V8
    variables:
      domain:
        default: suitecrm.example.com
paths:
  /report-data/opportunities:
    get:
      summary: Get Opportunity Report Data
      operationId: getOpportunityReportData
      security:
        - oauth2: [read]
      parameters: [...]
      responses: [...]
```

---

## 3. Authentication

### 3.1 Obtaining Access Token

**Endpoint**: `POST /Api/access_token`

**Request**:
```http
POST /Api/access_token HTTP/1.1
Host: suitecrm.example.com
Content-Type: application/x-www-form-urlencoded

grant_type=password&
client_id=suitecrm_client&
client_secret=secret&
username=admin&
password=admin_password&
scope=read
```

**Response**:
```json
{
  "token_type": "Bearer",
  "expires_in": 3600,
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "refresh_token": "def502004a8c7d4c2..."
}
```

### 3.2 Using Access Token

**Request**:
```http
GET /Api/V8/report-data/opportunities HTTP/1.1
Host: suitecrm.example.com
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
Accept: application/vnd.api+json
```

---

## 4. Request Parameters

### 4.1 Filter Parameters

#### sales_stage (string[])
Filter by one or more sales stages.

**Format**: Comma-separated list
**Example**: `?sales_stage=Prospecting,Qualification`
**Valid Values**: Any value from `sales_stage_dom` list
**Default**: All stages included

#### sales_stage_exclude (string[])
Exclude specific sales stages.

**Format**: Comma-separated list
**Example**: `?sales_stage_exclude=Closed Lost,Closed Won`
**Valid Values**: Any value from `sales_stage_dom` list
**Default**: No exclusions

#### date_closed_from (date)
Minimum close date (inclusive).

**Format**: `YYYY-MM-DD` (ISO 8601)
**Example**: `?date_closed_from=2026-01-01`
**Validation**: Must be valid date, cannot be after `date_closed_to`
**Default**: No minimum

#### date_closed_to (date)
Maximum close date (inclusive).

**Format**: `YYYY-MM-DD` (ISO 8601)
**Example**: `?date_closed_to=2026-12-31`
**Validation**: Must be valid date, cannot be before `date_closed_from`
**Default**: No maximum

#### date_closed_period (enum)
Relative date period for close date.

**Format**: String value from predefined list
**Example**: `?date_closed_period=this_quarter`
**Valid Values**:
- `today` - Current day
- `yesterday` - Previous day
- `this_week` - Sunday to Saturday of current week
- `last_week` - Previous week
- `this_month` - Current calendar month
- `last_month` - Previous calendar month
- `this_quarter` - Current fiscal quarter
- `last_quarter` - Previous fiscal quarter
- `this_year` - Current calendar year
- `last_year` - Previous calendar year

**Note**: Overrides `date_closed_from` and `date_closed_to` if specified

#### amount_min (decimal)
Minimum opportunity amount (inclusive).

**Format**: Decimal number with up to 2 decimal places
**Example**: `?amount_min=10000.00`
**Validation**: Must be >= 0, cannot exceed `amount_max`
**Default**: No minimum

#### amount_max (decimal)
Maximum opportunity amount (inclusive).

**Format**: Decimal number with up to 2 decimal places
**Example**: `?amount_max=1000000.00`
**Validation**: Must be >= 0, cannot be less than `amount_min`
**Default**: No maximum

#### assigned_user_id (uuid)
Filter by specific user assignment.

**Format**: UUID (36 characters)
**Example**: `?assigned_user_id=3fa85f64-5717-4562-b3fc-2c963f66afa6`
**Validation**: Must be valid user ID in system
**Default**: All users (subject to ACL)

#### assigned_user_id_current (boolean)
Filter to current authenticated user's opportunities only.

**Format**: `1` or `true` for enabled, `0` or `false` for disabled
**Example**: `?assigned_user_id_current=1`
**Default**: `false`
**Note**: Overrides `assigned_user_id` if set to true

#### lead_source (string[])
Filter by one or more lead sources.

**Format**: Comma-separated list
**Example**: `?lead_source=Web Site,Cold Call`
**Valid Values**: Any value from `lead_source_dom` list
**Default**: All sources included

#### account_id (uuid)
Filter by specific account relationship.

**Format**: UUID (36 characters)
**Example**: `?account_id=abc-123-def-456`
**Validation**: Must be valid account ID
**Default**: All accounts

#### probability_min (integer)
Minimum win probability percentage.

**Format**: Integer 0-100
**Example**: `?probability_min=50`
**Validation**: 0 <= value <= 100, cannot exceed `probability_max`
**Default**: 0

#### probability_max (integer)
Maximum win probability percentage.

**Format**: Integer 0-100
**Example**: `?probability_max=90`
**Validation**: 0 <= value <= 100, cannot be less than `probability_min`
**Default**: 100

### 4.2 Grouping Parameter

#### group_by (enum)
Group results and return aggregations.

**Format**: Single field name
**Example**: `?group_by=sales_stage`
**Valid Values**:
- `sales_stage` - Group by pipeline stage
- `assigned_user_id` - Group by owner
- `lead_source` - Group by source
- `account_id` - Group by account

**Default**: No grouping (detail records returned)
**Note**: When specified, response contains aggregated data instead of individual records

### 4.3 Pagination Parameters

#### page[number] (integer)
Current page number (1-indexed).

**Format**: Positive integer >= 1
**Example**: `?page[number]=2`
**Validation**: Must be >= 1
**Default**: `1`

#### page[size] (integer)
Records per page.

**Format**: Positive integer 1-500
**Example**: `?page[size]=100`
**Validation**: 1 <= value <= 500
**Default**: `50`

### 4.4 Sorting Parameters

#### sort (string)
Sort fields and directions.

**Format**: Comma-separated field names, prefix `-` for descending
**Example**: `?sort=-amount_usdollar,date_closed`
**Valid Fields**:
- `name`, `amount`, `amount_usdollar`, `sales_stage`, `probability`
- `date_closed`, `date_entered`, `date_modified`
- `lead_source`, `assigned_user_name`, `account_name`

**Default**: `date_modified` (descending)

### 4.5 Field Selection Parameters

#### fields (enum)
Include extended field set.

**Format**: `extended` or omit for core fields only
**Example**: `?fields=extended`
**Valid Values**: `extended`
**Default**: Core fields only

### 4.6 Format Parameters

#### format (enum)
Response format override.

**Format**: `json` or `csv`
**Example**: `?format=csv`
**Valid Values**:
- `json` - JSON API response (default)
- `csv` - CSV file download

**Alternative**: Use `Accept` header (`text/csv`)

---

## 5. Response Formats

### 5.1 Success Response - Detail Records (200 OK)

**Content-Type**: `application/vnd.api+json`

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
    },
    "sort": ["-amount_usdollar", "date_closed"]
  },
  "links": {
    "self": "/Api/V8/report-data/opportunities?page[number]=1&page[size]=50&sales_stage=Prospecting,Qualification",
    "first": "/Api/V8/report-data/opportunities?page[number]=1&page[size]=50&sales_stage=Prospecting,Qualification",
    "prev": null,
    "next": "/Api/V8/report-data/opportunities?page[number]=2&page[size]=50&sales_stage=Prospecting,Qualification",
    "last": "/Api/V8/report-data/opportunities?page[number]=25&page[size]=50&sales_stage=Prospecting,Qualification"
  }
}
```

### 5.2 Success Response - Grouped/Aggregated (200 OK)

**Request**: `?group_by=sales_stage`

```json
{
  "data": [
    {
      "type": "OpportunityAggregation",
      "id": "group-sales_stage-prospecting",
      "attributes": {
        "group_field": "sales_stage",
        "group_value": "Prospecting",
        "group_label": "Prospecting",
        "count": 245,
        "sum_amount_usdollar": "12250000.00",
        "avg_amount_usdollar": "50000.00",
        "min_amount_usdollar": "5000.00",
        "max_amount_usdollar": "500000.00",
        "avg_probability": 10,
        "weighted_pipeline": "1225000.00"
      }
    },
    {
      "type": "OpportunityAggregation",
      "id": "group-sales_stage-qualification",
      "attributes": {
        "group_field": "sales_stage",
        "group_value": "Qualification",
        "group_label": "Qualification",
        "count": 189,
        "sum_amount_usdollar": "9450000.00",
        "avg_amount_usdollar": "50000.00",
        "min_amount_usdollar": "10000.00",
        "max_amount_usdollar": "300000.00",
        "avg_probability": 20,
        "weighted_pipeline": "1890000.00"
      }
    }
  ],
  "meta": {
    "total_groups": 7,
    "group_by": "sales_stage",
    "grand_totals": {
      "count": 1247,
      "sum_amount_usdollar": "52750000.00",
      "avg_amount_usdollar": "42290.06",
      "weighted_pipeline": "38500000.00"
    },
    "filters_applied": {
      "date_closed_period": "this_quarter"
    }
  },
  "links": {
    "self": "/Api/V8/report-data/opportunities?group_by=sales_stage"
  }
}
```

### 5.3 Success Response - CSV Format (200 OK)

**Content-Type**: `text/csv; charset=utf-8`
**Content-Disposition**: `attachment; filename="opportunities_report_2026-02-23.csv"`

```csv
ID,Name,Amount,Amount (USD),Sales Stage,Probability,Close Date,Lead Source,Account,Assigned To,Date Entered,Date Modified
"3fa85f64-5717-4562-b3fc-2c963f66afa6","Acme Corp - Enterprise License","150000.00","150000.00","Proposal/Price Quote","75","2026-03-31","Web Site","Acme Corporation","Admin User","2026-01-15T10:30:00+00:00","2026-02-20T14:22:00+00:00"
```

---

## 6. Error Responses

### 6.1 Error Response Format

All errors follow JSON API error specification:

```json
{
  "errors": [
    {
      "status": "400",
      "code": "ERROR_CODE",
      "title": "Error Title",
      "detail": "Detailed error message",
      "source": {
        "parameter": "parameter_name"
      }
    }
  ]
}
```

### 6.2 Error Codes

#### 400 Bad Request - Invalid Parameter

```json
{
  "errors": [
    {
      "status": "400",
      "code": "INVALID_PARAMETER",
      "title": "Invalid Query Parameter",
      "detail": "Parameter 'sales_stage' contains invalid value 'InvalidStage'. Valid values: Prospecting, Qualification, Needs Analysis, Value Proposition, Id. Decision Makers, Perception Analysis, Proposal/Price Quote, Negotiation/Review, Closed Won, Closed Lost",
      "source": {
        "parameter": "sales_stage"
      }
    }
  ]
}
```

#### 400 Bad Request - Invalid Date Range

```json
{
  "errors": [
    {
      "status": "400",
      "code": "INVALID_DATE_RANGE",
      "title": "Invalid Date Range",
      "detail": "Parameter 'date_closed_from' (2026-12-31) cannot be after 'date_closed_to' (2026-01-01)",
      "source": {
        "parameter": "date_closed_from"
      }
    }
  ]
}
```

#### 400 Bad Request - Invalid Pagination

```json
{
  "errors": [
    {
      "status": "400",
      "code": "INVALID_PAGINATION",
      "title": "Invalid Pagination Parameter",
      "detail": "Parameter 'page[size]' must be between 1 and 500. Received: 1000",
      "source": {
        "parameter": "page[size]"
      }
    }
  ]
}
```

#### 401 Unauthorized - Missing Token

```json
{
  "errors": [
    {
      "status": "401",
      "code": "MISSING_AUTHORIZATION",
      "title": "Authentication Required",
      "detail": "Authorization header with Bearer token is required. Obtain token via POST /Api/access_token"
    }
  ]
}
```

#### 401 Unauthorized - Invalid Token

```json
{
  "errors": [
    {
      "status": "401",
      "code": "INVALID_TOKEN",
      "title": "Invalid Access Token",
      "detail": "The provided access token is invalid, expired, or revoked. Please obtain a new token."
    }
  ]
}
```

#### 403 Forbidden - Insufficient Permissions

```json
{
  "errors": [
    {
      "status": "403",
      "code": "INSUFFICIENT_PERMISSIONS",
      "title": "Access Denied",
      "detail": "User 'john.doe' does not have 'list' access to Opportunities module",
      "source": {
        "module": "Opportunities",
        "required_permission": "list",
        "user_id": "user-123"
      }
    }
  ]
}
```

#### 429 Too Many Requests - Rate Limit Exceeded

```json
{
  "errors": [
    {
      "status": "429",
      "code": "RATE_LIMIT_EXCEEDED",
      "title": "Too Many Requests",
      "detail": "Rate limit of 1000 requests per hour exceeded. Please retry after 3600 seconds.",
      "meta": {
        "limit": 1000,
        "remaining": 0,
        "reset": "2026-02-23T15:00:00+00:00",
        "retry_after": 3600
      }
    }
  ]
}
```

**Response Headers**:
```
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 0
X-RateLimit-Reset: 1708707600
Retry-After: 3600
```

#### 500 Internal Server Error

```json
{
  "errors": [
    {
      "status": "500",
      "code": "INTERNAL_ERROR",
      "title": "Internal Server Error",
      "detail": "An unexpected error occurred while processing your request. Please contact support with error ID: err_abc123xyz",
      "meta": {
        "error_id": "err_abc123xyz",
        "timestamp": "2026-02-23T12:34:56+00:00"
      }
    }
  ]
}
```

#### 503 Service Unavailable - Database Timeout

```json
{
  "errors": [
    {
      "status": "503",
      "code": "DATABASE_TIMEOUT",
      "title": "Service Temporarily Unavailable",
      "detail": "Database query timeout after 30 seconds. Please reduce the scope of your request or try again later.",
      "meta": {
        "timeout_seconds": 30,
        "retry_after": 60
      }
    }
  ]
}
```

---

## 7. Complete Request Examples

### 7.1 Example 1: Basic Request - Current Quarter Opportunities

**Request**:
```http
GET /Api/V8/report-data/opportunities?date_closed_period=this_quarter&page[size]=25 HTTP/1.1
Host: suitecrm.example.com
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
Accept: application/vnd.api+json
```

**cURL**:
```bash
curl -X GET "https://suitecrm.example.com/Api/V8/report-data/opportunities?date_closed_period=this_quarter&page[size]=25" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..." \
  -H "Accept: application/vnd.api+json"
```

### 7.2 Example 2: Filtered Request - High-Value Deals

**Request**:
```http
GET /Api/V8/report-data/opportunities?amount_min=100000&probability_min=75&sales_stage=Proposal/Price Quote,Negotiation/Review&sort=-amount_usdollar HTTP/1.1
Host: suitecrm.example.com
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
Accept: application/vnd.api+json
```

**cURL**:
```bash
curl -X GET "https://suitecrm.example.com/Api/V8/report-data/opportunities?amount_min=100000&probability_min=75&sales_stage=Proposal/Price%20Quote,Negotiation/Review&sort=-amount_usdollar" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..." \
  -H "Accept: application/vnd.api+json"
```

### 7.3 Example 3: Aggregated Request - Pipeline by Stage

**Request**:
```http
GET /Api/V8/report-data/opportunities?group_by=sales_stage&date_closed_period=this_year&sales_stage_exclude=Closed Lost HTTP/1.1
Host: suitecrm.example.com
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
Accept: application/vnd.api+json
```

**cURL**:
```bash
curl -X GET "https://suitecrm.example.com/Api/V8/report-data/opportunities?group_by=sales_stage&date_closed_period=this_year&sales_stage_exclude=Closed%20Lost" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..." \
  -H "Accept: application/vnd.api+json"
```

### 7.4 Example 4: CSV Export - My Opportunities

**Request**:
```http
GET /Api/V8/report-data/opportunities?assigned_user_id_current=1&format=csv HTTP/1.1
Host: suitecrm.example.com
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
Accept: text/csv
```

**cURL**:
```bash
curl -X GET "https://suitecrm.example.com/Api/V8/report-data/opportunities?assigned_user_id_current=1&format=csv" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..." \
  -H "Accept: text/csv" \
  -o my_opportunities.csv
```

### 7.5 Example 5: Multi-User Pipeline Report

**Request**:
```http
GET /Api/V8/report-data/opportunities?group_by=assigned_user_id&date_closed_from=2026-03-01&date_closed_to=2026-03-31&sales_stage_exclude=Closed Lost,Closed Won HTTP/1.1
Host: suitecrm.example.com
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
Accept: application/vnd.api+json
```

---

## 8. Response Headers

### 8.1 Standard Headers (All Responses)

```
Content-Type: application/vnd.api+json; charset=utf-8
X-Request-ID: req_abc123xyz
X-Response-Time: 247ms
Cache-Control: no-cache, private
Vary: Accept, Authorization
```

### 8.2 Rate Limiting Headers

```
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 873
X-RateLimit-Reset: 1708707600
```

### 8.3 Pagination Headers (Link)

```
Link: </Api/V8/report-data/opportunities?page[number]=2>; rel="next",
      </Api/V8/report-data/opportunities?page[number]=25>; rel="last",
      </Api/V8/report-data/opportunities?page[number]=1>; rel="first"
```

### 8.4 CSV Headers

```
Content-Type: text/csv; charset=utf-8
Content-Disposition: attachment; filename="opportunities_report_2026-02-23_143022.csv"
Content-Length: 52847
```

---

## 9. HTTP Status Code Summary

| Code | Meaning | When Used |
|------|---------|-----------|
| 200 | OK | Successful request with data returned |
| 400 | Bad Request | Invalid parameters, validation errors |
| 401 | Unauthorized | Missing or invalid authentication token |
| 403 | Forbidden | Insufficient permissions, ACL denial |
| 404 | Not Found | Endpoint not available (wrong URL) |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Internal Server Error | Unexpected server error |
| 503 | Service Unavailable | Database timeout, service down |

---

## 10. Client Implementation Guidelines

### 10.1 Authentication Flow

1. Obtain access token via `POST /Api/access_token`
2. Store token securely (encrypted, not in localStorage for web apps)
3. Include token in `Authorization: Bearer {token}` header
4. Handle 401 responses by refreshing token
5. Implement token refresh before expiration (proactive)

### 10.2 Error Handling

```javascript
async function fetchOpportunityReport(filters) {
  try {
    const response = await fetch(`/Api/V8/report-data/opportunities?${new URLSearchParams(filters)}`, {
      headers: {
        'Authorization': `Bearer ${accessToken}`,
        'Accept': 'application/vnd.api+json'
      }
    });

    if (!response.ok) {
      const errorData = await response.json();

      if (response.status === 401) {
        // Refresh token and retry
        await refreshAccessToken();
        return fetchOpportunityReport(filters);
      }

      if (response.status === 429) {
        // Rate limited - wait and retry
        const retryAfter = response.headers.get('Retry-After');
        await sleep(retryAfter * 1000);
        return fetchOpportunityReport(filters);
      }

      throw new ApiError(errorData.errors);
    }

    return await response.json();
  } catch (error) {
    console.error('API Error:', error);
    throw error;
  }
}
```

### 10.3 Pagination Handling

```javascript
async function fetchAllOpportunities(filters) {
  let allData = [];
  let page = 1;
  let hasMore = true;

  while (hasMore) {
    const response = await fetchOpportunityReport({
      ...filters,
      'page[number]': page,
      'page[size]': 500 // Max page size
    });

    allData = allData.concat(response.data);

    hasMore = response.links.next !== null;
    page++;

    // Respect rate limits
    if (hasMore) {
      await sleep(100); // 100ms delay between requests
    }
  }

  return allData;
}
```

### 10.4 Caching Strategy

```javascript
const cache = new Map();
const CACHE_TTL = 5 * 60 * 1000; // 5 minutes

async function fetchOpportunityReportCached(filters) {
  const cacheKey = JSON.stringify(filters);
  const cached = cache.get(cacheKey);

  if (cached && Date.now() - cached.timestamp < CACHE_TTL) {
    return cached.data;
  }

  const data = await fetchOpportunityReport(filters);
  cache.set(cacheKey, {
    data,
    timestamp: Date.now()
  });

  return data;
}
```

---

## 11. Performance Considerations

### 11.1 Query Optimization Tips

1. **Use specific filters** - Reduce dataset before aggregation
2. **Limit page size** - Start with smaller pages (50-100 records)
3. **Leverage date periods** - Use relative periods instead of broad date ranges
4. **Group for summaries** - Use `group_by` instead of fetching all records
5. **Cache aggressively** - Results change infrequently for historical data

### 11.2 Expected Response Times

| Scenario | Record Count | Response Time |
|----------|--------------|---------------|
| Simple filter, small result | < 1000 | < 500ms |
| Complex filter, medium result | 1000-10000 | 500ms-2s |
| Aggregated query | 10000+ | < 1s |
| CSV export | < 50000 | < 5s |
| CSV export, large | 50000+ | 5-30s |

---

## 12. Versioning and Deprecation

**Current Version**: v1.0
**API Version Namespace**: `/Api/V8/` (SuiteCRM V8 API)
**Versioning Strategy**: URL-based (part of V8 API family)

**Future Versions**:
- Breaking changes will be introduced in V9 namespace
- v1.0 will be supported for minimum 24 months
- Deprecation notices will be provided 12 months in advance
- Deprecation communicated via `Sunset` HTTP header

---

## 13. Security Considerations

### 13.1 HTTPS Requirement

All requests MUST use HTTPS. HTTP requests will be rejected with 403 Forbidden.

### 13.2 CORS Configuration

```
Access-Control-Allow-Origin: https://trusted-domain.com
Access-Control-Allow-Methods: GET, OPTIONS
Access-Control-Allow-Headers: Authorization, Content-Type, Accept
Access-Control-Max-Age: 86400
```

### 13.3 SQL Injection Prevention

All queries use parameterized statements via SuiteCRM DB abstraction layer. User input is never directly interpolated into SQL.

### 13.4 Data Exposure Prevention

- Sensitive fields (passwords, API keys) are never included
- ACL checks ensure users only see authorized data
- Error messages do not expose internal system details
- Stack traces never included in production responses

---

**END OF API SPECIFICATION**
