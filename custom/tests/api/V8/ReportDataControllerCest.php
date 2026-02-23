<?php
/**
 * Report Data API Integration Tests
 *
 * SuiteCRM is a customer relationship management program developed by SalesAgility Ltd.
 * Copyright (C) 2011 - 2025 SalesAgility Ltd.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation.
 *
 * @package SuiteCRM\Custom\Tests\Api\V8
 */

namespace Test\Api\V8;

use ApiTester;
use Codeception\Example;

/**
 * Report Data Controller API Tests
 *
 * Tests the Opportunity report data endpoint for:
 * - Authentication and authorization
 * - Filter parameter validation
 * - Pagination and sorting
 * - Response format compliance (JSON API v1.0)
 * - Aggregation accuracy
 * - Error handling
 */
#[\AllowDynamicProperties]
class ReportDataControllerCest
{
    /**
     * Setup before each test
     *
     * @param ApiTester $I
     * @throws \Codeception\Exception\ModuleException
     */
    public function _before(ApiTester $I)
    {
        $I->login();
    }

    /**
     * Test: Endpoint requires authentication
     *
     * @param ApiTester $I
     */
    public function testAuthenticationRequired(ApiTester $I)
    {
        $I->logout();
        $I->sendGET($I->getInstanceURL() . '/Api/V8/report-data/opportunities');
        $I->seeResponseCodeIs(401);
        $I->seeResponseIsJson();
    }

    /**
     * Test: Basic request returns valid JSON API response
     *
     * @param ApiTester $I
     */
    public function testBasicRequestReturnsValidResponse(ApiTester $I)
    {
        $I->sendGET($I->getInstanceURL() . '/Api/V8/report-data/opportunities');
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->seeResponseMatchesJsonType([
            'data' => 'array',
            'meta' => 'array',
            'links' => 'array'
        ]);
    }

    /**
     * Test: Response contains required meta fields
     *
     * @param ApiTester $I
     */
    public function testResponseContainsMetadata(ApiTester $I)
    {
        $I->sendGET($I->getInstanceURL() . '/Api/V8/report-data/opportunities');
        $I->seeResponseCodeIs(200);
        $I->seeResponseMatchesJsonType([
            'meta' => [
                'total_count' => 'integer',
                'returned_count' => 'integer',
                'page' => 'array'
            ]
        ]);
    }

    /**
     * Test: Sales stage filter works correctly
     *
     * @param ApiTester $I
     */
    public function testSalesStageFilter(ApiTester $I)
    {
        $I->sendGET($I->getInstanceURL() . '/Api/V8/report-data/opportunities?sales_stage=Prospecting,Qualification');
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();

        // Verify filters_applied in meta
        $I->seeResponseContainsJson([
            'meta' => [
                'filters_applied' => [
                    'sales_stage' => ['Prospecting', 'Qualification']
                ]
            ]
        ]);
    }

    /**
     * Test: Date range filter works correctly
     *
     * @param ApiTester $I
     */
    public function testDateRangeFilter(ApiTester $I)
    {
        $I->sendGET($I->getInstanceURL() . '/Api/V8/report-data/opportunities?date_closed_from=2026-01-01&date_closed_to=2026-12-31');
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();

        // Verify filters are applied
        $I->seeResponseContainsJson([
            'meta' => [
                'filters_applied' => [
                    'date_closed_from' => '2026-01-01',
                    'date_closed_to' => '2026-12-31'
                ]
            ]
        ]);
    }

    /**
     * Test: Amount range filter works correctly
     *
     * @param ApiTester $I
     */
    public function testAmountRangeFilter(ApiTester $I)
    {
        $I->sendGET($I->getInstanceURL() . '/Api/V8/report-data/opportunities?amount_min=10000&amount_max=100000');
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
    }

    /**
     * Test: Pagination works correctly
     *
     * @param ApiTester $I
     */
    public function testPagination(ApiTester $I)
    {
        $I->sendGET($I->getInstanceURL() . '/Api/V8/report-data/opportunities?page[number]=1&page[size]=10');
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();

        // Verify page metadata
        $I->seeResponseMatchesJsonType([
            'meta' => [
                'page' => [
                    'number' => 'integer',
                    'size' => 'integer'
                ]
            ]
        ]);
    }

    /**
     * Test: Sorting works correctly
     *
     * @param ApiTester $I
     */
    public function testSorting(ApiTester $I)
    {
        $I->sendGET($I->getInstanceURL() . '/Api/V8/report-data/opportunities?sort=-amount_usdollar');
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();

        // Verify returned records are in descending order by amount
        $response = json_decode($I->grabResponse(), true);
        if (!empty($response['data']) && count($response['data']) > 1) {
            $amounts = array_column(array_column($response['data'], 'attributes'), 'amount_usdollar');
            $sortedAmounts = $amounts;
            rsort($sortedAmounts);
            $I->assertEquals($sortedAmounts, $amounts, 'Records should be sorted by amount descending');
        }
    }

    /**
     * Test: Grouped aggregation returns correct format
     *
     * @param ApiTester $I
     */
    public function testGroupedAggregation(ApiTester $I)
    {
        $I->sendGET($I->getInstanceURL() . '/Api/V8/report-data/opportunities?group_by=sales_stage');
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();

        // Verify aggregation response structure
        $I->seeResponseMatchesJsonType([
            'data' => 'array',
            'meta' => [
                'total_groups' => 'integer',
                'group_by' => 'string',
                'grand_totals' => 'array'
            ]
        ]);

        // Verify each group has required aggregation fields
        $response = json_decode($I->grabResponse(), true);
        if (!empty($response['data'])) {
            $I->seeResponseMatchesJsonType([
                'data' => [
                    '*' => [
                        'type' => 'string',
                        'id' => 'string',
                        'attributes' => [
                            'group_field' => 'string',
                            'group_value' => 'string',
                            'group_label' => 'string',
                            'count' => 'integer',
                            'sum_amount_usdollar' => 'float|integer',
                            'avg_amount_usdollar' => 'float|integer|null',
                            'weighted_pipeline' => 'float|integer'
                        ]
                    ]
                ]
            ]);
        }
    }

    /**
     * Test: Invalid filter parameters return 400
     *
     * @param ApiTester $I
     * @param Example $example
     * @dataProvider invalidParametersProvider
     */
    public function testInvalidParametersReturnError(ApiTester $I, Example $example)
    {
        $I->sendGET($I->getInstanceURL() . $example['endpoint']);
        $I->seeResponseCodeIs(400);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'errors' => [
                'status' => 400
            ]
        ]);
    }

    /**
     * Data provider for invalid parameter tests
     *
     * @return array
     */
    protected function invalidParametersProvider()
    {
        return [
            [
                'case' => 'invalid_group_by',
                'endpoint' => '/Api/V8/report-data/opportunities?group_by=invalid_field'
            ],
            [
                'case' => 'invalid_date_format',
                'endpoint' => '/Api/V8/report-data/opportunities?date_closed_from=invalid-date'
            ],
            [
                'case' => 'invalid_page_size',
                'endpoint' => '/Api/V8/report-data/opportunities?page[size]=9999'
            ],
            [
                'case' => 'invalid_sort_field',
                'endpoint' => '/Api/V8/report-data/opportunities?sort=invalid_field'
            ]
        ];
    }

    /**
     * Test: Multiple filters combine correctly (AND logic)
     *
     * @param ApiTester $I
     */
    public function testMultipleFiltersCombine(ApiTester $I)
    {
        $I->sendGET($I->getInstanceURL() . '/Api/V8/report-data/opportunities?sales_stage=Prospecting&amount_min=10000&date_closed_from=2026-01-01');
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();

        // Verify all filters are applied
        $I->seeResponseContainsJson([
            'meta' => [
                'filters_applied' => [
                    'sales_stage' => ['Prospecting'],
                    'amount_min' => 10000.0,
                    'date_closed_from' => '2026-01-01'
                ]
            ]
        ]);
    }

    /**
     * Test: Current user filter works
     *
     * @param ApiTester $I
     */
    public function testCurrentUserFilter(ApiTester $I)
    {
        $I->sendGET($I->getInstanceURL() . '/Api/V8/report-data/opportunities?assigned_user_id_current=1');
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
    }

    /**
     * Test: Date period filter works (relative periods)
     *
     * @param ApiTester $I
     */
    public function testDatePeriodFilter(ApiTester $I)
    {
        $I->sendGET($I->getInstanceURL() . '/Api/V8/report-data/opportunities?date_closed_period=this_quarter');
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();

        // Verify period is applied
        $I->seeResponseContainsJson([
            'meta' => [
                'filters_applied' => [
                    'date_closed_period' => 'this_quarter'
                ]
            ]
        ]);
    }

    /**
     * Test: Empty result set returns valid response
     *
     * @param ApiTester $I
     */
    public function testEmptyResultSet(ApiTester $I)
    {
        // Use filters that likely return no results
        $I->sendGET($I->getInstanceURL() . '/Api/V8/report-data/opportunities?sales_stage=NonExistentStage999');
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();

        // Verify empty data array
        $I->seeResponseMatchesJsonType([
            'data' => 'array',
            'meta' => [
                'total_count' => 'integer',
                'returned_count' => 'integer'
            ]
        ]);

        $response = json_decode($I->grabResponse(), true);
        $I->assertEquals(0, $response['meta']['total_count'], 'Total count should be 0 for empty result');
        $I->assertEmpty($response['data'], 'Data array should be empty');
    }

    /**
     * Test: Response type is correct (Opportunities vs OpportunityAggregation)
     *
     * @param ApiTester $I
     */
    public function testResponseTypeCorrect(ApiTester $I)
    {
        // Detailed view
        $I->sendGET($I->getInstanceURL() . '/Api/V8/report-data/opportunities');
        $I->seeResponseCodeIs(200);
        $response = json_decode($I->grabResponse(), true);
        if (!empty($response['data'])) {
            $I->assertEquals('Opportunities', $response['data'][0]['type'], 'Detailed view should have type Opportunities');
        }

        // Aggregated view
        $I->sendGET($I->getInstanceURL() . '/Api/V8/report-data/opportunities?group_by=sales_stage');
        $I->seeResponseCodeIs(200);
        $response = json_decode($I->grabResponse(), true);
        if (!empty($response['data'])) {
            $I->assertEquals('OpportunityAggregation', $response['data'][0]['type'], 'Aggregated view should have type OpportunityAggregation');
        }
    }

    /**
     * Test: Probability filter works
     *
     * @param ApiTester $I
     */
    public function testProbabilityFilter(ApiTester $I)
    {
        $I->sendGET($I->getInstanceURL() . '/Api/V8/report-data/opportunities?probability_min=50&probability_max=100');
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
    }

    /**
     * Test: Lead source filter works
     *
     * @param ApiTester $I
     */
    public function testLeadSourceFilter(ApiTester $I)
    {
        $I->sendGET($I->getInstanceURL() . '/Api/V8/report-data/opportunities?lead_source=Web,Email');
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
    }

    /**
     * Test: Account ID filter works
     *
     * @param ApiTester $I
     */
    public function testAccountIdFilter(ApiTester $I)
    {
        // Note: This test would need a valid account ID to properly validate
        // For now, just verify the parameter is accepted
        $I->sendGET($I->getInstanceURL() . '/Api/V8/report-data/opportunities?account_id=test-account-123');
        // Should return 200 even if no records match
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
    }

    /**
     * Test: Sales stage exclude filter works
     *
     * @param ApiTester $I
     */
    public function testSalesStageExcludeFilter(ApiTester $I)
    {
        $I->sendGET($I->getInstanceURL() . '/Api/V8/report-data/opportunities?sales_stage_exclude=Closed Won,Closed Lost');
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();

        // Verify excluded stages in response
        $response = json_decode($I->grabResponse(), true);
        if (!empty($response['data'])) {
            foreach ($response['data'] as $record) {
                $stage = $record['attributes']['sales_stage'] ?? null;
                $I->assertNotEquals('Closed Won', $stage, 'Should not include Closed Won');
                $I->assertNotEquals('Closed Lost', $stage, 'Should not include Closed Lost');
            }
        }
    }
}
