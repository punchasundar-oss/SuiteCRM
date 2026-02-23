<?php
/**
 * Custom Services Configuration
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

use Psr\Container\ContainerInterface as Container;
use SuiteCRM\Custom\ReportingData\OpportunityReportService;
use SuiteCRM\Custom\Api\V8\Service\ReportDataService;

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

/**
 * Custom Service Registrations
 *
 * This file is merged with core services via CustomLoader.
 * Register custom service classes for dependency injection here.
 */
return [
    // Backend reporting service
    OpportunityReportService::class => function (Container $container) {
        return new OpportunityReportService();
    },

    // API layer service (transforms backend results to JSON API format)
    ReportDataService::class => function (Container $container) {
        return new ReportDataService(
            $container->get(OpportunityReportService::class)
        );
    },
];
