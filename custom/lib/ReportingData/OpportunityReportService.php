<?php
/**
 * Opportunity Report Service
 *
 * SuiteCRM Community Edition is a customer relationship management program developed by
 * SugarCRM, Inc. Copyright (C) 2004-2013 SugarCRM Inc.
 *
 * SuiteCRM is an extension to SugarCRM Community Edition developed by SalesAgility Ltd.
 * Copyright (C) 2011 - 2025 SalesAgility Ltd.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY SUITECRM, SUITECRM DISCLAIMS THE
 * WARRANTY OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License along with
 * this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 *
 * @package SuiteCRM\Custom\ReportingData
 */

namespace SuiteCRM\Custom\ReportingData;

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

/**
 * OpportunityReportService
 *
 * Provides programmatic access to Opportunity reporting data with filtering,
 * grouping, and aggregation capabilities while respecting ACL and team security.
 *
 * This service is designed for API consumption and returns normalized data
 * structures suitable for JSON API responses.
 *
 * @package SuiteCRM\Custom\ReportingData
 * @author SuiteCRM Development Team
 * @version 1.0.0
 */
class OpportunityReportService
{
    /**
     * Database manager instance
     * @var \DBManager
     */
    private $db;

    /**
     * Current authenticated user
     * @var \User
     */
    private $currentUser;

    /**
     * TimeDate utility for date handling
     * @var \TimeDate
     */
    private $timedate;

    /**
     * Logger instance
     * @var \LoggerManager
     */
    private $log;

    /**
     * Valid grouping fields
     * @var array
     */
    private const VALID_GROUP_BY_FIELDS = [
        'sales_stage',
        'assigned_user_id',
        'lead_source',
        'account_id'
    ];

    /**
     * Valid sort fields
     * @var array
     */
    private const VALID_SORT_FIELDS = [
        'name',
        'amount',
        'amount_usdollar',
        'sales_stage',
        'probability',
        'date_closed',
        'date_entered',
        'date_modified',
        'lead_source',
        'assigned_user_name',
        'account_name'
    ];

    /**
     * Core fields to select
     * @var array
     */
    private const CORE_FIELDS = [
        'o.id',
        'o.name',
        'o.amount',
        'o.amount_usdollar',
        'o.sales_stage',
        'o.probability',
        'o.date_closed',
        'o.lead_source',
        'o.date_entered',
        'o.date_modified',
        'o.assigned_user_id',
        'o.account_id'
    ];

    /**
     * Constructor
     *
     * @param \User|null $currentUser Current user (uses global if not provided)
     */
    public function __construct($currentUser = null)
    {
        global $current_user, $log, $timedate;

        $this->db = \DBManagerFactory::getInstance();
        $this->currentUser = $currentUser ?? $current_user;
        $this->timedate = $timedate ?? \TimeDate::getInstance();
        $this->log = $log ?? \LoggerManager::getLogger();

        if (!$this->currentUser || empty($this->currentUser->id)) {
            throw new \Exception('User not authenticated');
        }
    }

    /**
     * Get detailed Opportunity records with filtering and pagination
     *
     * @param array $filters Filter parameters (sales_stage, date_closed, amount, etc.)
     * @param array $options Pagination, sorting, field selection options
     * @return array Array with 'data', 'meta', and 'links' keys
     * @throws \Exception on ACL denial or query errors
     */
    public function getOpportunities(array $filters = [], array $options = []): array
    {
        // Validate filters
        $filters = $this->validateFilters($filters);

        // Check ACL
        $this->checkModuleAccess();

        // Build query
        $queryParts = $this->buildQuery($filters, $options);

        // Apply ACL filtering
        $this->applyACLFiltering($queryParts);

        // Get total count (for pagination)
        $totalCount = $this->getCount($queryParts);

        // Apply pagination
        $this->applyPagination($options, $queryParts);

        // Execute query
        $query = $this->assembleQuery($queryParts);
        $this->log->debug('OpportunityReportService: Executing query: ' . $query);

        $result = $this->db->query($query);
        if (!$result) {
            throw new \Exception('Query execution failed: ' . $this->db->lastError());
        }

        // Fetch and format results
        $data = [];
        while ($row = $this->db->fetchByAssoc($result)) {
            $data[] = $this->formatOpportunityRow($row);
        }

        // Calculate aggregations for metadata
        $aggregations = $this->calculateGlobalAggregations($queryParts);

        // Build response
        return [
            'data' => $data,
            'meta' => [
                'total_count' => (int)$totalCount,
                'returned_count' => count($data),
                'page' => $this->buildPaginationMeta($totalCount, $options),
                'aggregations' => $aggregations,
                'filters_applied' => $this->getAppliedFilters($filters)
            ]
        ];
    }

    /**
     * Get aggregated Opportunity report grouped by field
     *
     * @param string $groupBy Field to group by (sales_stage, assigned_user_id, etc.)
     * @param array $filters Filter parameters
     * @param array $options Sorting options
     * @return array Array with 'data' and 'meta' keys
     * @throws \Exception on invalid group field or ACL denial
     */
    public function getAggregatedReport(string $groupBy, array $filters = [], array $options = []): array
    {
        // Validate group by field
        if (!in_array($groupBy, self::VALID_GROUP_BY_FIELDS)) {
            throw new \InvalidArgumentException(
                "Invalid group_by field: $groupBy. Valid values: " . implode(', ', self::VALID_GROUP_BY_FIELDS)
            );
        }

        // Validate filters
        $filters = $this->validateFilters($filters);

        // Check ACL
        $this->checkModuleAccess();

        // Build aggregated query
        $queryParts = $this->buildAggregatedQuery($groupBy, $filters, $options);

        // Apply ACL filtering
        $this->applyACLFiltering($queryParts);

        // Execute query
        $query = $this->assembleQuery($queryParts);
        $this->log->debug('OpportunityReportService: Executing aggregated query: ' . $query);

        $result = $this->db->query($query);
        if (!$result) {
            throw new \Exception('Aggregated query execution failed: ' . $this->db->lastError());
        }

        // Fetch and format results
        $data = [];
        $grandTotals = [
            'count' => 0,
            'sum_amount_usdollar' => 0.0,
            'weighted_pipeline' => 0.0
        ];

        while ($row = $this->db->fetchByAssoc($result)) {
            $formattedRow = $this->formatAggregatedRow($row, $groupBy);
            $data[] = $formattedRow;

            // Accumulate grand totals
            $grandTotals['count'] += (int)$row['count'];
            $grandTotals['sum_amount_usdollar'] += (float)$row['sum_amount_usdollar'];
            $grandTotals['weighted_pipeline'] += (float)$row['weighted_pipeline'];
        }

        // Calculate grand average
        if ($grandTotals['count'] > 0) {
            $grandTotals['avg_amount_usdollar'] = number_format(
                $grandTotals['sum_amount_usdollar'] / $grandTotals['count'],
                2,
                '.',
                ''
            );
        } else {
            $grandTotals['avg_amount_usdollar'] = '0.00';
        }

        // Format grand totals
        $grandTotals['sum_amount_usdollar'] = number_format($grandTotals['sum_amount_usdollar'], 2, '.', '');
        $grandTotals['weighted_pipeline'] = number_format($grandTotals['weighted_pipeline'], 2, '.', '');

        // Build response
        return [
            'data' => $data,
            'meta' => [
                'total_groups' => count($data),
                'group_by' => $groupBy,
                'grand_totals' => $grandTotals,
                'filters_applied' => $this->getAppliedFilters($filters)
            ]
        ];
    }

    /**
     * Validate and sanitize filter parameters
     *
     * @param array $filters Raw filter parameters
     * @return array Validated and sanitized filters
     * @throws \InvalidArgumentException if validation fails
     */
    public function validateFilters(array $filters): array
    {
        $validated = [];

        // Sales stage filter
        if (!empty($filters['sales_stage'])) {
            $validated['sales_stage'] = $this->validateEnumFilter($filters['sales_stage'], 'sales_stage_dom');
        }

        // Sales stage exclude filter
        if (!empty($filters['sales_stage_exclude'])) {
            $validated['sales_stage_exclude'] = $this->validateEnumFilter(
                $filters['sales_stage_exclude'],
                'sales_stage_dom'
            );
        }

        // Date filters
        if (!empty($filters['date_closed_from'])) {
            $validated['date_closed_from'] = $this->validateDateFilter($filters['date_closed_from']);
        }

        if (!empty($filters['date_closed_to'])) {
            $validated['date_closed_to'] = $this->validateDateFilter($filters['date_closed_to']);
        }

        if (!empty($filters['date_closed_period'])) {
            $validated['date_closed_period'] = $this->validatePeriodFilter($filters['date_closed_period']);
        }

        // Amount filters
        if (isset($filters['amount_min']) && $filters['amount_min'] !== '') {
            $validated['amount_min'] = $this->validateDecimalFilter($filters['amount_min']);
        }

        if (isset($filters['amount_max']) && $filters['amount_max'] !== '') {
            $validated['amount_max'] = $this->validateDecimalFilter($filters['amount_max']);
        }

        // Probability filters
        if (isset($filters['probability_min']) && $filters['probability_min'] !== '') {
            $validated['probability_min'] = $this->validateIntegerFilter(
                $filters['probability_min'],
                0,
                100
            );
        }

        if (isset($filters['probability_max']) && $filters['probability_max'] !== '') {
            $validated['probability_max'] = $this->validateIntegerFilter(
                $filters['probability_max'],
                0,
                100
            );
        }

        // User filter
        if (!empty($filters['assigned_user_id'])) {
            $validated['assigned_user_id'] = $this->validateUUIDFilter($filters['assigned_user_id']);
        }

        if (!empty($filters['assigned_user_id_current'])) {
            $validated['assigned_user_id_current'] = (bool)$filters['assigned_user_id_current'];
        }

        // Lead source filter
        if (!empty($filters['lead_source'])) {
            $validated['lead_source'] = $this->validateEnumFilter($filters['lead_source'], 'lead_source_dom');
        }

        // Account filter
        if (!empty($filters['account_id'])) {
            $validated['account_id'] = $this->validateUUIDFilter($filters['account_id']);
        }

        return $validated;
    }

    /**
     * Build SQL query parts for detailed report
     *
     * @param array $filters Validated filters
     * @param array $options Query options
     * @return array Query parts (select, from, join, where, orderby, groupby)
     */
    private function buildQuery(array $filters, array $options): array
    {
        $queryParts = [
            'select' => $this->buildSelectClause($options),
            'from' => 'opportunities o',
            'join' => $this->buildJoinClause(),
            'where' => ['o.deleted = 0'],
            'orderby' => $this->buildOrderByClause($options),
            'groupby' => ''
        ];

        // Apply filters to WHERE clause
        $this->applyFiltersToQuery($filters, $queryParts);

        return $queryParts;
    }

    /**
     * Build SQL query parts for aggregated report
     *
     * @param string $groupBy Group by field
     * @param array $filters Validated filters
     * @param array $options Query options
     * @return array Query parts
     */
    private function buildAggregatedQuery(string $groupBy, array $filters, array $options): array
    {
        $queryParts = [
            'select' => $this->buildAggregatedSelectClause($groupBy),
            'from' => 'opportunities o',
            'join' => $this->buildJoinClause(),
            'where' => ['o.deleted = 0'],
            'orderby' => 'sum_amount_usdollar DESC',
            'groupby' => "o.$groupBy"
        ];

        // Apply filters to WHERE clause
        $this->applyFiltersToQuery($filters, $queryParts);

        return $queryParts;
    }

    /**
     * Build SELECT clause for detailed query
     *
     * @param array $options Query options
     * @return string SELECT clause
     */
    private function buildSelectClause(array $options): string
    {
        $fields = self::CORE_FIELDS;

        // Add user name
        $fields[] = "CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as assigned_user_name";

        // Add account name
        $fields[] = 'a.name as account_name';

        return implode(', ', $fields);
    }

    /**
     * Build SELECT clause for aggregated query
     *
     * @param string $groupBy Group by field
     * @return string SELECT clause
     */
    private function buildAggregatedSelectClause(string $groupBy): string
    {
        $fields = [
            "o.$groupBy as group_value",
            'COUNT(*) as count',
            'SUM(o.amount_usdollar) as sum_amount_usdollar',
            'AVG(o.amount_usdollar) as avg_amount_usdollar',
            'MIN(o.amount_usdollar) as min_amount_usdollar',
            'MAX(o.amount_usdollar) as max_amount_usdollar',
            'AVG(o.probability) as avg_probability',
            'SUM(o.amount_usdollar * o.probability / 100) as weighted_pipeline'
        ];

        // Add label field for certain group types
        if ($groupBy === 'assigned_user_id') {
            $fields[] = "CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as group_label";
        } elseif ($groupBy === 'account_id') {
            $fields[] = 'a.name as group_label';
        }

        return implode(', ', $fields);
    }

    /**
     * Build JOIN clause for related tables
     *
     * @return string JOIN clause
     */
    private function buildJoinClause(): string
    {
        return "LEFT JOIN users u ON o.assigned_user_id = u.id AND u.deleted = 0\n" .
               "LEFT JOIN accounts a ON o.account_id = a.id AND a.deleted = 0";
    }

    /**
     * Build ORDER BY clause
     *
     * @param array $options Query options with 'sort' key
     * @return string ORDER BY clause
     */
    private function buildOrderByClause(array $options): string
    {
        if (empty($options['sort'])) {
            return 'o.date_modified DESC';
        }

        $sortFields = is_array($options['sort']) ? $options['sort'] : explode(',', $options['sort']);
        $orderByClauses = [];

        foreach ($sortFields as $sortField) {
            $sortField = trim($sortField);
            $direction = 'ASC';

            if (substr($sortField, 0, 1) === '-') {
                $direction = 'DESC';
                $sortField = substr($sortField, 1);
            }

            if (in_array($sortField, self::VALID_SORT_FIELDS)) {
                // Map to table alias
                if ($sortField === 'assigned_user_name') {
                    $orderByClauses[] = "CONCAT(u.first_name, ' ', u.last_name) $direction";
                } elseif ($sortField === 'account_name') {
                    $orderByClauses[] = "a.name $direction";
                } else {
                    $orderByClauses[] = "o.$sortField $direction";
                }
            }
        }

        return !empty($orderByClauses) ? implode(', ', $orderByClauses) : 'o.date_modified DESC';
    }

    /**
     * Apply filters to query WHERE clause
     *
     * @param array $filters Validated filters
     * @param array &$queryParts Query parts to modify
     */
    private function applyFiltersToQuery(array $filters, array &$queryParts): void
    {
        // Sales stage filter
        if (!empty($filters['sales_stage'])) {
            $values = array_map([$this->db, 'quote'], $filters['sales_stage']);
            $queryParts['where'][] = 'o.sales_stage IN (' . implode(', ', $values) . ')';
        }

        // Sales stage exclude filter
        if (!empty($filters['sales_stage_exclude'])) {
            $values = array_map([$this->db, 'quote'], $filters['sales_stage_exclude']);
            $queryParts['where'][] = 'o.sales_stage NOT IN (' . implode(', ', $values) . ')';
        }

        // Date filters
        $this->applyDateFilters($filters, $queryParts);

        // Amount filters
        if (isset($filters['amount_min'])) {
            $queryParts['where'][] = 'o.amount_usdollar >= ' . $this->db->quote($filters['amount_min']);
        }

        if (isset($filters['amount_max'])) {
            $queryParts['where'][] = 'o.amount_usdollar <= ' . $this->db->quote($filters['amount_max']);
        }

        // Probability filters
        if (isset($filters['probability_min'])) {
            $queryParts['where'][] = 'o.probability >= ' . (int)$filters['probability_min'];
        }

        if (isset($filters['probability_max'])) {
            $queryParts['where'][] = 'o.probability <= ' . (int)$filters['probability_max'];
        }

        // User filters
        if (!empty($filters['assigned_user_id_current']) && $filters['assigned_user_id_current'] === true) {
            $queryParts['where'][] = 'o.assigned_user_id = ' . $this->db->quote($this->currentUser->id);
        } elseif (!empty($filters['assigned_user_id'])) {
            $queryParts['where'][] = 'o.assigned_user_id = ' . $this->db->quote($filters['assigned_user_id']);
        }

        // Lead source filter
        if (!empty($filters['lead_source'])) {
            $values = array_map([$this->db, 'quote'], $filters['lead_source']);
            $queryParts['where'][] = 'o.lead_source IN (' . implode(', ', $values) . ')';
        }

        // Account filter
        if (!empty($filters['account_id'])) {
            $queryParts['where'][] = 'o.account_id = ' . $this->db->quote($filters['account_id']);
        }
    }

    /**
     * Apply date filters to query
     *
     * Date period takes precedence over explicit from/to dates.
     * Adapted from AOR_Reports module date handling logic.
     *
     * @param array $filters Validated filters
     * @param array &$queryParts Query parts to modify
     */
    private function applyDateFilters(array $filters, array &$queryParts): void
    {
        if (!empty($filters['date_closed_period'])) {
            // Use period-based filtering
            $period = $filters['date_closed_period'];
            $dateFrom = $this->getPeriodDate($period);
            $dateTo = $this->getPeriodEndDate($period);

            if ($dateFrom) {
                $queryParts['where'][] = 'o.date_closed >= ' . $this->db->quote($dateFrom->format('Y-m-d'));
            }

            if ($dateTo) {
                $queryParts['where'][] = 'o.date_closed <= ' . $this->db->quote($dateTo->format('Y-m-d'));
            }
        } else {
            // Use explicit from/to dates
            if (!empty($filters['date_closed_from'])) {
                $queryParts['where'][] = 'o.date_closed >= ' . $this->db->quote($filters['date_closed_from']);
            }

            if (!empty($filters['date_closed_to'])) {
                $queryParts['where'][] = 'o.date_closed <= ' . $this->db->quote($filters['date_closed_to']);
            }
        }
    }

    /**
     * Apply ACL filtering to query
     *
     * This adds WHERE clauses to enforce team security and record-level access.
     *
     * @param array &$queryParts Query parts to modify
     */
    private function applyACLFiltering(array &$queryParts): void
    {
        // Team security (if SecurityGroups module is enabled)
        if (class_exists('SecurityGroup')) {
            $securityGroupWhere = \SecurityGroup::getSecurityWhere('Opportunities', $this->currentUser->id);
            if (!empty($securityGroupWhere)) {
                $queryParts['where'][] = $securityGroupWhere;
            }
        }

        // For non-admin users, apply additional restrictions
        if (!$this->currentUser->isAdmin()) {
            // Note: Team security is complex in SuiteCRM. For MVP, we rely on
            // SecurityGroup filtering above. In production, additional team_set_id
            // filtering may be needed based on specific SuiteCRM configuration.
        }
    }

    /**
     * Apply pagination to query
     *
     * @param array $options Query options with page settings
     * @param array &$queryParts Query parts to modify
     */
    private function applyPagination(array $options, array &$queryParts): void
    {
        $pageNumber = max(1, (int)($options['page']['number'] ?? 1));
        $pageSize = min(500, max(1, (int)($options['page']['size'] ?? 50)));

        $offset = ($pageNumber - 1) * $pageSize;

        $queryParts['limit'] = "LIMIT $offset, $pageSize";
    }

    /**
     * Assemble final SQL query from parts
     *
     * @param array $queryParts Query parts
     * @return string Complete SQL query
     */
    private function assembleQuery(array $queryParts): string
    {
        $query = "SELECT {$queryParts['select']}\n";
        $query .= "FROM {$queryParts['from']}\n";

        if (!empty($queryParts['join'])) {
            $query .= $queryParts['join'] . "\n";
        }

        if (!empty($queryParts['where'])) {
            $query .= 'WHERE ' . implode(' AND ', $queryParts['where']) . "\n";
        }

        if (!empty($queryParts['groupby'])) {
            $query .= "GROUP BY {$queryParts['groupby']}\n";
        }

        if (!empty($queryParts['orderby'])) {
            $query .= "ORDER BY {$queryParts['orderby']}\n";
        }

        if (!empty($queryParts['limit'])) {
            $query .= $queryParts['limit'];
        }

        return $query;
    }

    /**
     * Get total count for pagination
     *
     * @param array $queryParts Query parts (without limit)
     * @return int Total count
     */
    private function getCount(array $queryParts): int
    {
        $countQuery = "SELECT COUNT(*) as total\n";
        $countQuery .= "FROM {$queryParts['from']}\n";

        if (!empty($queryParts['join'])) {
            $countQuery .= $queryParts['join'] . "\n";
        }

        if (!empty($queryParts['where'])) {
            $countQuery .= 'WHERE ' . implode(' AND ', $queryParts['where']);
        }

        $result = $this->db->query($countQuery);
        if (!$result) {
            return 0;
        }

        $row = $this->db->fetchByAssoc($result);
        return (int)($row['total'] ?? 0);
    }

    /**
     * Calculate global aggregations for metadata
     *
     * @param array $queryParts Query parts (without limit)
     * @return array Aggregation data
     */
    private function calculateGlobalAggregations(array $queryParts): array
    {
        $aggQuery = "SELECT \n";
        $aggQuery .= "    COUNT(*) as total_count,\n";
        $aggQuery .= "    SUM(o.amount_usdollar) as total_amount_usdollar,\n";
        $aggQuery .= "    AVG(o.amount_usdollar) as average_amount_usdollar,\n";
        $aggQuery .= "    SUM(o.amount_usdollar * o.probability / 100) as weighted_pipeline\n";
        $aggQuery .= "FROM {$queryParts['from']}\n";

        if (!empty($queryParts['join'])) {
            $aggQuery .= $queryParts['join'] . "\n";
        }

        if (!empty($queryParts['where'])) {
            $aggQuery .= 'WHERE ' . implode(' AND ', $queryParts['where']);
        }

        $result = $this->db->query($aggQuery);
        if (!$result) {
            return [];
        }

        $row = $this->db->fetchByAssoc($result);

        return [
            'total_amount_usdollar' => number_format((float)($row['total_amount_usdollar'] ?? 0), 2, '.', ''),
            'average_amount_usdollar' => number_format((float)($row['average_amount_usdollar'] ?? 0), 2, '.', ''),
            'total_count' => (int)($row['total_count'] ?? 0),
            'weighted_pipeline' => number_format((float)($row['weighted_pipeline'] ?? 0), 2, '.', '')
        ];
    }

    /**
     * Format opportunity row for output
     *
     * @param array $row Database row
     * @return array Formatted row
     */
    private function formatOpportunityRow(array $row): array
    {
        return [
            'id' => $row['id'] ?? '',
            'name' => $row['name'] ?? '',
            'amount' => number_format((float)($row['amount'] ?? 0), 2, '.', ''),
            'amount_usdollar' => number_format((float)($row['amount_usdollar'] ?? 0), 2, '.', ''),
            'sales_stage' => $row['sales_stage'] ?? '',
            'probability' => (int)($row['probability'] ?? 0),
            'date_closed' => $row['date_closed'] ?? '',
            'lead_source' => $row['lead_source'] ?? '',
            'date_entered' => $row['date_entered'] ?? '',
            'date_modified' => $row['date_modified'] ?? '',
            'assigned_user_id' => $row['assigned_user_id'] ?? '',
            'assigned_user_name' => $row['assigned_user_name'] ?? '',
            'account_id' => $row['account_id'] ?? '',
            'account_name' => $row['account_name'] ?? ''
        ];
    }

    /**
     * Format aggregated row for output
     *
     * @param array $row Database row
     * @param string $groupBy Group by field
     * @return array Formatted row
     */
    private function formatAggregatedRow(array $row, string $groupBy): array
    {
        $formatted = [
            'group_field' => $groupBy,
            'group_value' => $row['group_value'] ?? '',
            'group_label' => $row['group_label'] ?? $row['group_value'] ?? '',
            'count' => (int)($row['count'] ?? 0),
            'sum_amount_usdollar' => number_format((float)($row['sum_amount_usdollar'] ?? 0), 2, '.', ''),
            'avg_amount_usdollar' => number_format((float)($row['avg_amount_usdollar'] ?? 0), 2, '.', ''),
            'min_amount_usdollar' => number_format((float)($row['min_amount_usdollar'] ?? 0), 2, '.', ''),
            'max_amount_usdollar' => number_format((float)($row['max_amount_usdollar'] ?? 0), 2, '.', ''),
            'avg_probability' => (int)($row['avg_probability'] ?? 0),
            'weighted_pipeline' => number_format((float)($row['weighted_pipeline'] ?? 0), 2, '.', '')
        ];

        return $formatted;
    }

    /**
     * Build pagination metadata
     *
     * @param int $totalCount Total record count
     * @param array $options Query options
     * @return array Pagination metadata
     */
    private function buildPaginationMeta(int $totalCount, array $options): array
    {
        $pageNumber = max(1, (int)($options['page']['number'] ?? 1));
        $pageSize = min(500, max(1, (int)($options['page']['size'] ?? 50)));
        $totalPages = $pageSize > 0 ? (int)ceil($totalCount / $pageSize) : 0;

        return [
            'number' => $pageNumber,
            'size' => $pageSize,
            'total_pages' => $totalPages
        ];
    }

    /**
     * Get list of applied filters for metadata
     *
     * @param array $filters Validated filters
     * @return array Applied filters
     */
    private function getAppliedFilters(array $filters): array
    {
        $applied = [];

        foreach ($filters as $key => $value) {
            if ($key === 'assigned_user_id_current' && $value === true) {
                $applied[$key] = 'current_user';
            } elseif (is_array($value)) {
                $applied[$key] = $value;
            } elseif ($value !== '' && $value !== null) {
                $applied[$key] = $value;
            }
        }

        return $applied;
    }

    /**
     * Check module-level ACL access
     *
     * @throws \Exception if user lacks access
     */
    private function checkModuleAccess(): void
    {
        if (!\ACLController::checkAccess('Opportunities', 'list', true)) {
            throw new \Exception('Insufficient permissions: User does not have list access to Opportunities module');
        }
    }

    // =========================================================================
    // VALIDATION METHODS
    // =========================================================================

    /**
     * Validate enum filter (sales_stage, lead_source)
     *
     * @param mixed $value Filter value (string or array)
     * @param string $listName App list string name
     * @return array Array of valid values
     */
    private function validateEnumFilter($value, string $listName): array
    {
        global $app_list_strings;

        $values = is_array($value) ? $value : explode(',', (string)$value);
        $values = array_map('trim', $values);

        $validValues = $app_list_strings[$listName] ?? [];

        foreach ($values as $val) {
            if (!isset($validValues[$val])) {
                throw new \InvalidArgumentException(
                    "Invalid value '$val' for $listName. Valid values: " . implode(', ', array_keys($validValues))
                );
            }
        }

        return $values;
    }

    /**
     * Validate date filter (YYYY-MM-DD format)
     *
     * @param string $value Date value
     * @return string Validated date
     * @throws \InvalidArgumentException if invalid
     */
    private function validateDateFilter(string $value): string
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            throw new \InvalidArgumentException("Invalid date format: $value. Expected YYYY-MM-DD");
        }

        $date = \DateTime::createFromFormat('Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value) {
            throw new \InvalidArgumentException("Invalid date: $value");
        }

        return $value;
    }

    /**
     * Validate period filter
     *
     * @param string $value Period value
     * @return string Validated period
     * @throws \InvalidArgumentException if invalid
     */
    private function validatePeriodFilter(string $value): string
    {
        $validPeriods = [
            'today',
            'yesterday',
            'this_week',
            'last_week',
            'this_month',
            'last_month',
            'this_quarter',
            'last_quarter',
            'this_year',
            'last_year'
        ];

        if (!in_array($value, $validPeriods)) {
            throw new \InvalidArgumentException(
                "Invalid period: $value. Valid values: " . implode(', ', $validPeriods)
            );
        }

        return $value;
    }

    /**
     * Validate decimal filter (amount)
     *
     * @param mixed $value Decimal value
     * @return float Validated decimal
     * @throws \InvalidArgumentException if invalid
     */
    private function validateDecimalFilter($value): float
    {
        if (!is_numeric($value)) {
            throw new \InvalidArgumentException("Invalid decimal value: $value");
        }

        $decimal = (float)$value;

        if ($decimal < 0) {
            throw new \InvalidArgumentException("Decimal value must be non-negative: $value");
        }

        return $decimal;
    }

    /**
     * Validate integer filter (probability)
     *
     * @param mixed $value Integer value
     * @param int $min Minimum value
     * @param int $max Maximum value
     * @return int Validated integer
     * @throws \InvalidArgumentException if invalid
     */
    private function validateIntegerFilter($value, int $min, int $max): int
    {
        if (!is_numeric($value)) {
            throw new \InvalidArgumentException("Invalid integer value: $value");
        }

        $integer = (int)$value;

        if ($integer < $min || $integer > $max) {
            throw new \InvalidArgumentException("Integer value must be between $min and $max: $value");
        }

        return $integer;
    }

    /**
     * Validate UUID filter
     *
     * @param string $value UUID value
     * @return string Validated UUID
     * @throws \InvalidArgumentException if invalid
     */
    private function validateUUIDFilter(string $value): string
    {
        // SuiteCRM uses 36-character UUIDs with dashes
        if (!preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $value)) {
            // Also accept UUIDs without dashes (32 characters)
            if (!preg_match('/^[a-f0-9]{32}$/i', $value)) {
                throw new \InvalidArgumentException("Invalid UUID: $value");
            }
        }

        return $value;
    }

    // =========================================================================
    // DATE/TIME UTILITIES
    // Adapted from modules/AOR_Reports/aor_utils.php
    // =========================================================================

    /**
     * Get start date for period
     *
     * Adapted from AOR_Reports module (aor_utils.php::getPeriodDate)
     * Handles relative date periods like this_quarter, last_month, etc.
     *
     * @param string $period Period identifier
     * @return \DateTime|null Start date or null
     */
    private function getPeriodDate(string $period): ?\DateTime
    {
        global $sugar_config;

        $datetime = new \DateTime();

        // Setup quarter configuration
        $quarters = $this->calculateQuarters(
            $sugar_config['aor']['quarters_begin'] ?? 1
        );

        switch ($period) {
            case 'today':
                return $datetime;

            case 'yesterday':
                return $datetime->sub(new \DateInterval('P1D'));

            case 'this_week':
                return $datetime->setTimestamp(strtotime('this week'));

            case 'last_week':
                return $datetime->setTimestamp(strtotime('last week'));

            case 'this_month':
                return $datetime->setDate(
                    (int)$datetime->format('Y'),
                    (int)$datetime->format('m'),
                    1
                );

            case 'last_month':
                return $datetime->modify('first day of last month');

            case 'this_quarter':
                return $this->getQuarterStart($datetime, $quarters, 'current');

            case 'last_quarter':
                return $this->getQuarterStart($datetime, $quarters, 'previous');

            case 'this_year':
                return $datetime->setDate((int)$datetime->format('Y'), 1, 1);

            case 'last_year':
                return $datetime->setDate((int)$datetime->format('Y') - 1, 1, 1);

            default:
                return null;
        }
    }

    /**
     * Get end date for period
     *
     * Adapted from AOR_Reports module (aor_utils.php::getPeriodEndDate)
     *
     * @param string $period Period identifier
     * @return \DateTime|null End date or null
     */
    private function getPeriodEndDate(string $period): ?\DateTime
    {
        global $sugar_config;

        $datetime = new \DateTime();

        // Setup quarter configuration
        $quarters = $this->calculateQuarters(
            $sugar_config['aor']['quarters_begin'] ?? 1
        );

        switch ($period) {
            case 'today':
                return $datetime;

            case 'yesterday':
                return $datetime->sub(new \DateInterval('P1D'));

            case 'this_week':
                return $datetime->setTimestamp(strtotime('this week'))->add(new \DateInterval('P6D'));

            case 'last_week':
                return $datetime->setTimestamp(strtotime('last week'))->add(new \DateInterval('P6D'));

            case 'this_month':
                return $datetime->setDate(
                    (int)$datetime->format('Y'),
                    (int)$datetime->format('m'),
                    (int)$datetime->format('t')
                );

            case 'last_month':
                return $datetime->modify('last day of last month');

            case 'this_quarter':
                return $this->getQuarterEnd($datetime, $quarters, 'current');

            case 'last_quarter':
                return $this->getQuarterEnd($datetime, $quarters, 'previous');

            case 'this_year':
                return $datetime->setDate((int)$datetime->format('Y'), 12, 31);

            case 'last_year':
                return $datetime->setDate((int)$datetime->format('Y') - 1, 12, 31);

            default:
                return null;
        }
    }

    /**
     * Calculate quarter start and end dates
     *
     * Adapted from AOR_Reports module (aor_utils.php::calculateQuarters)
     *
     * @param int $startMonth First month of fiscal year (1-12)
     * @return array Quarter data with start/end dates
     */
    private function calculateQuarters(int $startMonth = 1): array
    {
        $year = (int)date('Y');
        $quarters = [];

        for ($i = 1; $i <= 4; $i++) {
            $quarterStartMonth = (($i - 1) * 3) + $startMonth;
            $quarterEndMonth = $quarterStartMonth + 2;

            // Handle year overflow
            if ($quarterStartMonth > 12) {
                $quarterStartMonth -= 12;
                $startYear = $year + 1;
            } else {
                $startYear = $year;
            }

            if ($quarterEndMonth > 12) {
                $quarterEndMonth -= 12;
                $endYear = $year + 1;
            } else {
                $endYear = $year;
            }

            $startDate = new \DateTime();
            $startDate->setDate($startYear, $quarterStartMonth, 1);

            $endDate = new \DateTime();
            $endDate->setDate($endYear, $quarterEndMonth, (int)$endDate->format('t'));

            $quarters[$i] = [
                'start' => $startDate,
                'end' => $endDate
            ];
        }

        return $quarters;
    }

    /**
     * Get quarter start date
     *
     * @param \DateTime $datetime Reference date
     * @param array $quarters Quarter definitions
     * @param string $which 'current' or 'previous'
     * @return \DateTime Quarter start date
     */
    private function getQuarterStart(\DateTime $datetime, array $quarters, string $which): \DateTime
    {
        $thisMonth = new \DateTime($datetime->format('Y-m-01'));

        foreach ([1, 2, 3, 4] as $q) {
            if ($thisMonth >= $quarters[$q]['start'] && $thisMonth <= $quarters[$q]['end']) {
                if ($which === 'current') {
                    return clone $quarters[$q]['start'];
                } else {
                    // Previous quarter
                    $prevQ = $q === 1 ? 4 : $q - 1;
                    $prevStart = clone $quarters[$prevQ]['start'];
                    if ($prevQ === 4) {
                        $prevStart->modify('-1 year');
                    }
                    return $prevStart;
                }
            }
        }

        // Default to Q1 start
        return clone $quarters[1]['start'];
    }

    /**
     * Get quarter end date
     *
     * @param \DateTime $datetime Reference date
     * @param array $quarters Quarter definitions
     * @param string $which 'current' or 'previous'
     * @return \DateTime Quarter end date
     */
    private function getQuarterEnd(\DateTime $datetime, array $quarters, string $which): \DateTime
    {
        $thisMonth = new \DateTime($datetime->format('Y-m-01'));

        foreach ([1, 2, 3, 4] as $q) {
            if ($thisMonth >= $quarters[$q]['start'] && $thisMonth <= $quarters[$q]['end']) {
                if ($which === 'current') {
                    return clone $quarters[$q]['end'];
                } else {
                    // Previous quarter
                    $prevQ = $q === 1 ? 4 : $q - 1;
                    $prevEnd = clone $quarters[$prevQ]['end'];
                    if ($prevQ === 4) {
                        $prevEnd->modify('-1 year');
                    }
                    return $prevEnd;
                }
            }
        }

        // Default to Q1 end
        return clone $quarters[1]['end'];
    }
}
