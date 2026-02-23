<?php
/**
 * Opportunity Report Dashlet
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
 * @package SuiteCRM\Custom\Home\Dashlets
 */

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

require_once('include/Dashlets/DashletGeneric.php');

/**
 * OpportunityReportDashlet
 *
 * Displays Opportunity report data with filtering capabilities.
 * Integrates with V8 REST API endpoint for data retrieval.
 *
 * @package SuiteCRM\Custom\Home\Dashlets
 */
class OpportunityReportDashlet extends DashletGeneric
{
    /**
     * Template file for dashlet display
     * @var string
     */
    protected $templateFile = 'custom/modules/Home/Dashlets/OpportunityReportDashlet/OpportunityReportDashlet.tpl';

    /**
     * Dashlet title
     * @var string
     */
    public $title = 'Opportunity Report';

    /**
     * Constructor
     *
     * @param string $id Dashlet ID
     * @param array $def Dashlet definition
     */
    public function __construct($id, $def = null)
    {
        global $current_user, $app_strings, $dashletStrings;

        require_once('custom/modules/Home/Dashlets/OpportunityReportDashlet/OpportunityReportDashlet.en_us.lang.php');

        parent::__construct($id, $def);

        if (empty($this->title)) {
            $this->title = !empty($dashletStrings['OpportunityReportDashlet']['LBL_TITLE'])
                ? $dashletStrings['OpportunityReportDashlet']['LBL_TITLE']
                : 'Opportunity Report';
        }

        $this->isConfigurable = true;
        $this->hasScript = false;
    }

    /**
     * Display the dashlet
     *
     * @param string $text Additional text to display
     * @return string HTML content
     */
    public function display($text = '')
    {
        global $current_user;

        // Get filter values from configuration
        $filters = $this->getFiltersFromConfig();

        // Fetch data from API
        $reportData = $this->fetchReportData($filters);

        // Prepare template variables
        $this->ss->assign('DASHLET_ID', $this->id);
        $this->ss->assign('TITLE', $this->title);

        if (!empty($reportData) && !isset($reportData['error'])) {
            $this->ss->assign('opportunities', $reportData['data']);
            $this->ss->assign('meta', $reportData['meta']);
            $this->ss->assign('error', false);
        } else {
            $this->ss->assign('opportunities', array());
            $this->ss->assign('meta', array());
            $this->ss->assign('error', true);
            $this->ss->assign('error_message', !empty($reportData['error'])
                ? $reportData['error']
                : 'Unable to load report data');
        }

        // Assign filter options for form
        $this->ss->assign('sales_stages', $this->getSalesStageOptions());
        $this->ss->assign('date_periods', $this->getDatePeriodOptions());

        // Assign current filter values
        $this->ss->assign('current_filters', $filters);

        return parent::display($text);
    }

    /**
     * Process form submissions and update configuration
     *
     * @param array $lvsParams ListView parameters
     * @return void
     */
    public function process($lvsParams = array())
    {
        global $current_user;

        // Check if filters were submitted
        if (!empty($_REQUEST['apply_filters']) && $_REQUEST['dashlet_id'] == $this->id) {
            // Update configuration with new filter values
            $this->saveFiltersToConfig($_REQUEST);
        }

        parent::process($lvsParams);
    }

    /**
     * Display options for dashlet configuration
     *
     * @return string HTML for configuration form
     */
    public function displayOptions()
    {
        global $app_strings, $dashletStrings;

        $this->ss->assign('sales_stages', $this->getSalesStageOptions());
        $this->ss->assign('date_periods', $this->getDatePeriodOptions());
        $this->ss->assign('page_sizes', array(10 => 10, 25 => 25, 50 => 50, 100 => 100));

        // Assign current configuration values
        $this->ss->assign('config_sales_stage', !empty($this->salesStage) ? $this->salesStage : '');
        $this->ss->assign('config_date_period', !empty($this->datePeriod) ? $this->datePeriod : '');
        $this->ss->assign('config_amount_min', !empty($this->amountMin) ? $this->amountMin : '');
        $this->ss->assign('config_my_opportunities', !empty($this->myOpportunities));
        $this->ss->assign('config_page_size', !empty($this->pageSize) ? $this->pageSize : 25);

        return parent::displayOptions();
    }

    /**
     * Save configuration options
     *
     * @param array $req Request parameters
     * @return void
     */
    public function saveOptions($req)
    {
        // Save filter preferences
        if (isset($req['sales_stage'])) {
            $this->salesStage = $req['sales_stage'];
        }
        if (isset($req['date_period'])) {
            $this->datePeriod = $req['date_period'];
        }
        if (isset($req['amount_min'])) {
            $this->amountMin = $req['amount_min'];
        }
        if (isset($req['my_opportunities'])) {
            $this->myOpportunities = (bool)$req['my_opportunities'];
        }
        if (isset($req['page_size'])) {
            $this->pageSize = (int)$req['page_size'];
        }

        parent::saveOptions($req);
    }

    /**
     * Get filters from current configuration
     *
     * @return array Filter parameters
     */
    private function getFiltersFromConfig()
    {
        $filters = array();

        if (!empty($this->salesStage)) {
            $filters['sales_stage'] = $this->salesStage;
        }
        if (!empty($this->datePeriod)) {
            $filters['date_closed_period'] = $this->datePeriod;
        }
        if (!empty($this->amountMin)) {
            $filters['amount_min'] = $this->amountMin;
        }
        if (!empty($this->myOpportunities)) {
            $filters['assigned_user_id_current'] = true;
        }

        // Pagination
        $filters['page'] = array(
            'number' => 1,
            'size' => !empty($this->pageSize) ? $this->pageSize : 25
        );

        return $filters;
    }

    /**
     * Save filters to configuration
     *
     * @param array $req Request parameters
     * @return void
     */
    private function saveFiltersToConfig($req)
    {
        if (isset($req['sales_stage'])) {
            $this->salesStage = $req['sales_stage'];
        }
        if (isset($req['date_period'])) {
            $this->datePeriod = $req['date_period'];
        }
        if (isset($req['amount_min'])) {
            $this->amountMin = $req['amount_min'];
        }
        $this->myOpportunities = isset($req['my_opportunities']);

        // Save to user preferences
        $this->saveOptions($req);
    }

    /**
     * Fetch report data from backend service
     *
     * @param array $filters Filter parameters
     * @return array Report data or error
     */
    private function fetchReportData($filters)
    {
        global $current_user;

        try {
            // Load backend service
            require_once('custom/lib/ReportingData/OpportunityReportService.php');

            // Create service instance with current user
            $service = new \SuiteCRM\Custom\ReportingData\OpportunityReportService($current_user);

            // Prepare options for service
            $options = array(
                'page' => isset($filters['page']) ? $filters['page']['number'] : 1,
                'page_size' => isset($filters['page']) ? $filters['page']['size'] : 25,
                'sort' => 'date_closed', // Default sort
            );

            // Remove page from filters for service call
            $serviceFilters = $filters;
            unset($serviceFilters['page']);

            // Call backend service
            $result = $service->getOpportunities($serviceFilters, $options);

            // Transform to JSON API v1.0 format for template consistency
            $jsonApiData = array(
                'data' => array(),
                'meta' => $result['meta']
            );

            foreach ($result['data'] as $record) {
                $jsonApiData['data'][] = array(
                    'type' => 'Opportunities',
                    'id' => $record['id'],
                    'attributes' => $record
                );
            }

            return $jsonApiData;

        } catch (Exception $e) {
            $GLOBALS['log']->error('OpportunityReportDashlet: Exception: ' . $e->getMessage());
            return array('error' => 'An error occurred while fetching data: ' . $e->getMessage());
        }
    }

    /**
     * Get sales stage options
     *
     * @return array Sales stage options
     */
    private function getSalesStageOptions()
    {
        global $app_list_strings;

        if (!empty($app_list_strings['sales_stage_dom'])) {
            return $app_list_strings['sales_stage_dom'];
        }

        // Fallback options
        return array(
            'Prospecting' => 'Prospecting',
            'Qualification' => 'Qualification',
            'Needs Analysis' => 'Needs Analysis',
            'Value Proposition' => 'Value Proposition',
            'Id. Decision Makers' => 'Id. Decision Makers',
            'Perception Analysis' => 'Perception Analysis',
            'Proposal/Price Quote' => 'Proposal/Price Quote',
            'Negotiation/Review' => 'Negotiation/Review',
            'Closed Won' => 'Closed Won',
            'Closed Lost' => 'Closed Lost',
        );
    }

    /**
     * Get date period options
     *
     * @return array Date period options
     */
    private function getDatePeriodOptions()
    {
        return array(
            '' => '-- Select Period --',
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            'this_week' => 'This Week',
            'last_week' => 'Last Week',
            'this_month' => 'This Month',
            'last_month' => 'Last Month',
            'this_quarter' => 'This Quarter',
            'last_quarter' => 'Last Quarter',
            'this_year' => 'This Year',
            'last_year' => 'Last Year',
        );
    }
}
