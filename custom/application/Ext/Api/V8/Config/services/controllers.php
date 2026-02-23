<?php
/**
 * Custom Controllers Configuration
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
use SuiteCRM\Custom\Api\V8\Controller\ReportDataController;
use SuiteCRM\Custom\Api\V8\Service\ReportDataService;

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

/**
 * Custom Controller Registrations
 *
 * This file is merged with core controllers via CustomLoader.
 * Register custom controller classes for dependency injection here.
 */
return [
    // Report Data API Controller
    ReportDataController::class => function (Container $container) {
        return new ReportDataController(
            $container->get(ReportDataService::class)
        );
    },
];
