<?php
/**
 * Custom API Routes
 *
 * SuiteCRM is a customer relationship management program developed by SalesAgility Ltd.
 * Copyright (C) 2011 - 2025 SalesAgility Ltd.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation.
 *
 * @package SuiteCRM\Custom\Api\V8\Config
 */

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

use Api\V8\Factory\ParamsMiddlewareFactory;
use SuiteCRM\Custom\Api\V8\Param\ReportDataParams;

/**
 * Custom API Routes
 *
 * This file is loaded by CustomLoader and has access to the $app instance.
 * Register custom API endpoints here.
 */

/** @var \Slim\App $app */
/** @var ParamsMiddlewareFactory $paramsMiddlewareFactory */
$paramsMiddlewareFactory = $app->getContainer()->get(ParamsMiddlewareFactory::class);

/**
 * Opportunity Report Data Endpoint
 *
 * GET /Api/V8/report-data/opportunities
 *
 * Returns Opportunity report data with filtering, aggregation, and pagination.
 * Requires OAuth2 authentication and 'list' access to Opportunities module.
 *
 * Query Parameters:
 * - sales_stage: Filter by sales stage(s) (comma-separated)
 * - sales_stage_exclude: Exclude sales stage(s) (comma-separated)
 * - date_closed_from: Filter by close date from (YYYY-MM-DD)
 * - date_closed_to: Filter by close date to (YYYY-MM-DD)
 * - date_closed_period: Filter by relative period (this_quarter, last_month, etc.)
 * - amount_min: Filter by minimum amount
 * - amount_max: Filter by maximum amount
 * - probability_min: Filter by minimum probability (0-100)
 * - probability_max: Filter by maximum probability (0-100)
 * - assigned_user_id: Filter by assigned user ID
 * - assigned_user_id_current: Filter by current user (1/0)
 * - lead_source: Filter by lead source(s) (comma-separated)
 * - account_id: Filter by account ID
 * - group_by: Group results by field (sales_stage, assigned_user_id, lead_source, account_id)
 * - page[number]: Page number (default: 1)
 * - page[size]: Page size (default: 50, max: 500)
 * - sort: Sort fields (e.g., -amount_usdollar,date_closed)
 *
 * Response: JSON API v1.0 format with data, meta, and links
 */
$app->get(
    '/report-data/opportunities',
    'SuiteCRM\Custom\Api\V8\Controller\ReportDataController:getOpportunityReport'
)->add($paramsMiddlewareFactory->bind(ReportDataParams::class));
