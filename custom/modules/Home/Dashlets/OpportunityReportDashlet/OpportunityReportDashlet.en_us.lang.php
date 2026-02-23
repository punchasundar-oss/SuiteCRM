<?php
/**
 * Opportunity Report Dashlet Language File (English US)
 *
 * Provides translatable strings for the dashlet
 */

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

$dashletStrings['OpportunityReportDashlet'] = array(
    'LBL_TITLE' => 'Opportunity Report',
    'LBL_DESCRIPTION' => 'View and filter opportunity report data',
    'LBL_CONFIGURE_TITLE' => 'Opportunity Report Settings',
    'LBL_CONFIGURE_DESCRIPTION' => 'Configure default filters and display options for the opportunity report',

    // Filter labels
    'LBL_SALES_STAGE' => 'Sales Stage',
    'LBL_DATE_RANGE' => 'Date Range',
    'LBL_AMOUNT_MIN' => 'Minimum Amount',
    'LBL_MY_OPPORTUNITIES' => 'Show Only My Opportunities',
    'LBL_PAGE_SIZE' => 'Records Per Page',

    // Button labels
    'LBL_APPLY_FILTERS' => 'Apply Filters',
    'LBL_CLEAR_FILTERS' => 'Clear Filters',
    'LBL_REFRESH' => 'Refresh',

    // Column headers
    'LBL_NAME' => 'Name',
    'LBL_AMOUNT' => 'Amount',
    'LBL_STAGE' => 'Sales Stage',
    'LBL_CLOSE_DATE' => 'Close Date',
    'LBL_ASSIGNED_TO' => 'Assigned To',

    // Messages
    'LBL_NO_DATA' => 'No opportunities found matching your criteria',
    'LBL_ERROR_LOADING' => 'Error loading report data',
    'LBL_LOADING' => 'Loading...',
);

$dashletMeta['OpportunityReportDashlet'] = array(
    'title' => $dashletStrings['OpportunityReportDashlet']['LBL_TITLE'],
    'description' => $dashletStrings['OpportunityReportDashlet']['LBL_DESCRIPTION'],
);
