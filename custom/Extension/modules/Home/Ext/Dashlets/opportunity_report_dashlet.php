<?php
/**
 * Opportunity Report Dashlet Registration
 *
 * Registers the OpportunityReportDashlet with SuiteCRM's dashlet system.
 * This file is auto-merged into custom/modules/Home/Ext/Dashlets/dashlets.ext.php
 * by the Extension framework.
 */

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

$dashletMeta['OpportunityReportDashlet'] = array(
    'module' => 'Home',
    'title' => 'Opportunity Report',
    'description' => 'View and filter opportunity report data with customizable filters',
    'category' => 'Tools',
);
