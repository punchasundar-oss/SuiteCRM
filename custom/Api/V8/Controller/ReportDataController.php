<?php
/**
 * Report Data Controller
 *
 * SuiteCRM is a customer relationship management program developed by SalesAgility Ltd.
 * Copyright (C) 2011 - 2025 SalesAgility Ltd.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY SUITECRM, SUITECRM DISCLAIMS THE
 * WARRANTY OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 *
 * @package SuiteCRM\Custom\Api\V8\Controller
 */

namespace SuiteCRM\Custom\Api\V8\Controller;

use Api\V8\Controller\BaseController;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use SuiteCRM\Custom\Api\V8\Service\ReportDataService;
use SuiteCRM\Custom\Api\V8\Param\ReportDataParams;

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

/**
 * Report Data Controller
 *
 * Handles HTTP requests for reporting data endpoints.
 * Provides access to Opportunity report data via V8 REST API.
 *
 * @package SuiteCRM\Custom\Api\V8\Controller
 */
class ReportDataController extends BaseController
{
    /**
     * Report data service
     * @var ReportDataService
     */
    private $reportDataService;

    /**
     * Constructor
     *
     * @param ReportDataService $reportDataService Report data service
     */
    public function __construct(ReportDataService $reportDataService)
    {
        $this->reportDataService = $reportDataService;
    }

    /**
     * Get Opportunity report data
     *
     * Retrieves Opportunity records with filtering, aggregation, and pagination.
     * Enforces OAuth2 authentication and ACL permissions.
     *
     * @param Request $request HTTP request
     * @param Response $response HTTP response
     * @param array $args Route arguments
     * @param ReportDataParams|null $params Validated parameters
     * @return Response JSON API response
     */
    public function getOpportunityReport(
        Request $request,
        Response $response,
        array $args,
        ReportDataParams $params = null
    ): Response {
        try {
            // Generate report using service
            $jsonResponse = $this->reportDataService->generateOpportunityReport(
                $params,
                $request->getUri()->getPath()
            );

            // Return successful response
            return $this->generateResponse($response, $jsonResponse, 200);
        } catch (\InvalidArgumentException $exception) {
            // Validation errors - 400 Bad Request
            return $this->generateErrorResponse($response, $exception, 400);
        } catch (\RuntimeException $exception) {
            // Permission errors - 403 Forbidden
            return $this->generateErrorResponse($response, $exception, 403);
        } catch (\Exception $exception) {
            // Other errors - 500 Internal Server Error
            return $this->generateErrorResponse($response, $exception, 500);
        }
    }
}
