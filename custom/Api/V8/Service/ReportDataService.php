<?php
/**
 * Report Data Service
 *
 * SuiteCRM is a customer relationship management program developed by SalesAgility Ltd.
 * Copyright (C) 2011 - 2025 SalesAgility Ltd.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation.
 *
 * @package SuiteCRM\Custom\Api\V8\Service
 */

namespace SuiteCRM\Custom\Api\V8\Service;

use Api\V8\JsonApi\Response\AttributeResponse;
use Api\V8\JsonApi\Response\DataResponse;
use Api\V8\JsonApi\Response\DocumentResponse;
use Api\V8\JsonApi\Response\MetaResponse;
use Api\V8\JsonApi\Response\LinksResponse;
use SuiteCRM\Custom\Api\V8\Param\ReportDataParams;
use SuiteCRM\Custom\ReportingData\OpportunityReportService;

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

/**
 * Report Data Service
 *
 * Transforms OpportunityReportService results into JSON API format.
 * Handles request parameter extraction and response formatting.
 *
 * @package SuiteCRM\Custom\Api\V8\Service
 */
class ReportDataService
{
    /**
     * Opportunity report service
     * @var OpportunityReportService
     */
    private $opportunityReportService;

    /**
     * Constructor
     *
     * @param OpportunityReportService $opportunityReportService Backend reporting service
     */
    public function __construct(OpportunityReportService $opportunityReportService)
    {
        $this->opportunityReportService = $opportunityReportService;
    }

    /**
     * Generate Opportunity report
     *
     * @param ReportDataParams $params Validated parameters
     * @param string $path Request path for links
     * @return DocumentResponse JSON API document
     * @throws \Exception On service errors
     */
    public function generateOpportunityReport(ReportDataParams $params, string $path): DocumentResponse
    {
        // Extract filters from parameters
        $filters = $this->buildFiltersArray($params);

        // Extract options from parameters
        $options = $this->buildOptionsArray($params);

        // Check if this is an aggregated report request
        $groupBy = $params->getGroupBy();

        if ($groupBy !== null) {
            // Generate aggregated report
            $result = $this->opportunityReportService->getAggregatedReport($groupBy, $filters, $options);
            return $this->formatAggregatedResponse($result, $path);
        } else {
            // Generate detailed report
            $result = $this->opportunityReportService->getOpportunities($filters, $options);
            return $this->formatDetailedResponse($result, $path);
        }
    }

    /**
     * Build filters array from parameters
     *
     * @param ReportDataParams $params Parameters
     * @return array Filters
     */
    private function buildFiltersArray(ReportDataParams $params): array
    {
        $filters = [];

        if ($params->getSalesStage() !== null) {
            $filters['sales_stage'] = explode(',', $params->getSalesStage());
        }

        if ($params->getSalesStageExclude() !== null) {
            $filters['sales_stage_exclude'] = explode(',', $params->getSalesStageExclude());
        }

        if ($params->getDateClosedFrom() !== null) {
            $filters['date_closed_from'] = $params->getDateClosedFrom();
        }

        if ($params->getDateClosedTo() !== null) {
            $filters['date_closed_to'] = $params->getDateClosedTo();
        }

        if ($params->getDateClosedPeriod() !== null) {
            $filters['date_closed_period'] = $params->getDateClosedPeriod();
        }

        if ($params->getAmountMin() !== null) {
            $filters['amount_min'] = $params->getAmountMin();
        }

        if ($params->getAmountMax() !== null) {
            $filters['amount_max'] = $params->getAmountMax();
        }

        if ($params->getProbabilityMin() !== null) {
            $filters['probability_min'] = $params->getProbabilityMin();
        }

        if ($params->getProbabilityMax() !== null) {
            $filters['probability_max'] = $params->getProbabilityMax();
        }

        if ($params->getAssignedUserId() !== null) {
            $filters['assigned_user_id'] = $params->getAssignedUserId();
        }

        if ($params->getAssignedUserIdCurrent() === true) {
            $filters['assigned_user_id_current'] = true;
        }

        if ($params->getLeadSource() !== null) {
            $filters['lead_source'] = explode(',', $params->getLeadSource());
        }

        if ($params->getAccountId() !== null) {
            $filters['account_id'] = $params->getAccountId();
        }

        return $filters;
    }

    /**
     * Build options array from parameters
     *
     * @param ReportDataParams $params Parameters
     * @return array Options
     */
    private function buildOptionsArray(ReportDataParams $params): array
    {
        $options = [];

        // Pagination
        $pageNumber = $params->getPageNumber();
        $pageSize = $params->getPageSize();

        if ($pageNumber !== null || $pageSize !== null) {
            $options['page'] = [
                'number' => $pageNumber ?? 1,
                'size' => $pageSize ?? 50
            ];
        }

        // Sorting
        if ($params->getSort() !== null) {
            $options['sort'] = $params->getSort();
        }

        return $options;
    }

    /**
     * Format detailed response (non-aggregated)
     *
     * @param array $result Service result
     * @param string $path Request path
     * @return DocumentResponse JSON API document
     */
    private function formatDetailedResponse(array $result, string $path): DocumentResponse
    {
        $dataResponses = [];

        foreach ($result['data'] as $opportunity) {
            $dataResponse = new DataResponse('Opportunities', $opportunity['id']);
            $dataResponse->setAttributes(new AttributeResponse([
                'name' => $opportunity['name'],
                'amount' => $opportunity['amount'],
                'amount_usdollar' => $opportunity['amount_usdollar'],
                'sales_stage' => $opportunity['sales_stage'],
                'probability' => $opportunity['probability'],
                'date_closed' => $opportunity['date_closed'],
                'lead_source' => $opportunity['lead_source'],
                'date_entered' => $opportunity['date_entered'],
                'date_modified' => $opportunity['date_modified'],
                'assigned_user_id' => $opportunity['assigned_user_id'],
                'assigned_user_name' => $opportunity['assigned_user_name'],
                'account_id' => $opportunity['account_id'],
                'account_name' => $opportunity['account_name']
            ]));

            $dataResponses[] = $dataResponse;
        }

        // Build document
        $document = new DocumentResponse();
        $document->setData($dataResponses);

        // Add metadata
        $meta = new MetaResponse();
        $meta->total_count = $result['meta']['total_count'];
        $meta->returned_count = $result['meta']['returned_count'];
        $meta->page = $result['meta']['page'];

        if (!empty($result['meta']['aggregations'])) {
            $meta->aggregations = $result['meta']['aggregations'];
        }

        if (!empty($result['meta']['filters_applied'])) {
            $meta->filters_applied = $result['meta']['filters_applied'];
        }

        $document->setMeta($meta);

        // Add links
        $links = new LinksResponse();
        $links->setSelf($path);
        $document->setLinks($links);

        return $document;
    }

    /**
     * Format aggregated response (grouped)
     *
     * @param array $result Service result
     * @param string $path Request path
     * @return DocumentResponse JSON API document
     */
    private function formatAggregatedResponse(array $result, string $path): DocumentResponse
    {
        $dataResponses = [];

        foreach ($result['data'] as $group) {
            // Create unique ID for this aggregation group
            $id = 'agg-' . md5($group['group_field'] . '-' . $group['group_value']);

            $dataResponse = new DataResponse('OpportunityAggregation', $id);
            $dataResponse->setAttributes(new AttributeResponse([
                'group_field' => $group['group_field'],
                'group_value' => $group['group_value'],
                'group_label' => $group['group_label'],
                'count' => $group['count'],
                'sum_amount_usdollar' => $group['sum_amount_usdollar'],
                'avg_amount_usdollar' => $group['avg_amount_usdollar'],
                'min_amount_usdollar' => $group['min_amount_usdollar'],
                'max_amount_usdollar' => $group['max_amount_usdollar'],
                'avg_probability' => $group['avg_probability'],
                'weighted_pipeline' => $group['weighted_pipeline']
            ]));

            $dataResponses[] = $dataResponse;
        }

        // Build document
        $document = new DocumentResponse();
        $document->setData($dataResponses);

        // Add metadata
        $meta = new MetaResponse();
        $meta->total_groups = $result['meta']['total_groups'];
        $meta->group_by = $result['meta']['group_by'];
        $meta->grand_totals = $result['meta']['grand_totals'];

        if (!empty($result['meta']['filters_applied'])) {
            $meta->filters_applied = $result['meta']['filters_applied'];
        }

        $document->setMeta($meta);

        // Add links
        $links = new LinksResponse();
        $links->setSelf($path);
        $document->setLinks($links);

        return $document;
    }
}
