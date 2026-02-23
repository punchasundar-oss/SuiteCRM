<?php
/**
 * Custom Parameters Configuration
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

use Api\V8\BeanDecorator\BeanManager;
use Api\V8\Factory\ValidatorFactory;
use Psr\Container\ContainerInterface as Container;
use SuiteCRM\Custom\Api\V8\Param\ReportDataParams;

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

/**
 * Custom Parameter Registrations
 *
 * This file is merged with core params via CustomLoader.
 * Register custom parameter classes for dependency injection here.
 */
return [
    // Report Data Parameters
    ReportDataParams::class => function (Container $container) {
        return new ReportDataParams(
            $container->get(ValidatorFactory::class),
            $container->get(BeanManager::class)
        );
    },
];
