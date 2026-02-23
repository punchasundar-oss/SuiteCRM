<?php
/**
 * Report Data Parameters
 *
 * SuiteCRM is a customer relationship management program developed by SalesAgility Ltd.
 * Copyright (C) 2011 - 2025 SalesAgility Ltd.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation.
 *
 * @package SuiteCRM\Custom\Api\V8\Param
 */

namespace SuiteCRM\Custom\Api\V8\Param;

use Api\V8\Param\BaseParam;
use Symfony\Component\OptionsResolver\OptionsResolver;

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

/**
 * Report Data Parameters
 *
 * Validates and provides access to report data request parameters.
 *
 * @package SuiteCRM\Custom\Api\V8\Param
 */
class ReportDataParams extends BaseParam
{
    /**
     * Get sales_stage filter
     * @return string|null
     */
    public function getSalesStage(): ?string
    {
        return $this->parameters['sales_stage'] ?? null;
    }

    /**
     * Get sales_stage_exclude filter
     * @return string|null
     */
    public function getSalesStageExclude(): ?string
    {
        return $this->parameters['sales_stage_exclude'] ?? null;
    }

    /**
     * Get date_closed_from filter
     * @return string|null
     */
    public function getDateClosedFrom(): ?string
    {
        return $this->parameters['date_closed_from'] ?? null;
    }

    /**
     * Get date_closed_to filter
     * @return string|null
     */
    public function getDateClosedTo(): ?string
    {
        return $this->parameters['date_closed_to'] ?? null;
    }

    /**
     * Get date_closed_period filter
     * @return string|null
     */
    public function getDateClosedPeriod(): ?string
    {
        return $this->parameters['date_closed_period'] ?? null;
    }

    /**
     * Get amount_min filter
     * @return float|null
     */
    public function getAmountMin(): ?float
    {
        return isset($this->parameters['amount_min']) ? (float)$this->parameters['amount_min'] : null;
    }

    /**
     * Get amount_max filter
     * @return float|null
     */
    public function getAmountMax(): ?float
    {
        return isset($this->parameters['amount_max']) ? (float)$this->parameters['amount_max'] : null;
    }

    /**
     * Get probability_min filter
     * @return int|null
     */
    public function getProbabilityMin(): ?int
    {
        return isset($this->parameters['probability_min']) ? (int)$this->parameters['probability_min'] : null;
    }

    /**
     * Get probability_max filter
     * @return int|null
     */
    public function getProbabilityMax(): ?int
    {
        return isset($this->parameters['probability_max']) ? (int)$this->parameters['probability_max'] : null;
    }

    /**
     * Get assigned_user_id filter
     * @return string|null
     */
    public function getAssignedUserId(): ?string
    {
        return $this->parameters['assigned_user_id'] ?? null;
    }

    /**
     * Get assigned_user_id_current filter
     * @return bool
     */
    public function getAssignedUserIdCurrent(): bool
    {
        return !empty($this->parameters['assigned_user_id_current']);
    }

    /**
     * Get lead_source filter
     * @return string|null
     */
    public function getLeadSource(): ?string
    {
        return $this->parameters['lead_source'] ?? null;
    }

    /**
     * Get account_id filter
     * @return string|null
     */
    public function getAccountId(): ?string
    {
        return $this->parameters['account_id'] ?? null;
    }

    /**
     * Get group_by parameter
     * @return string|null
     */
    public function getGroupBy(): ?string
    {
        return $this->parameters['group_by'] ?? null;
    }

    /**
     * Get page number
     * @return int|null
     */
    public function getPageNumber(): ?int
    {
        if (isset($this->parameters['page']) && is_array($this->parameters['page'])) {
            return isset($this->parameters['page']['number']) ? (int)$this->parameters['page']['number'] : null;
        }
        return null;
    }

    /**
     * Get page size
     * @return int|null
     */
    public function getPageSize(): ?int
    {
        if (isset($this->parameters['page']) && is_array($this->parameters['page'])) {
            return isset($this->parameters['page']['size']) ? (int)$this->parameters['page']['size'] : null;
        }
        return null;
    }

    /**
     * Get sort parameter
     * @return string|null
     */
    public function getSort(): ?string
    {
        return $this->parameters['sort'] ?? null;
    }

    /**
     * Configure parameters
     *
     * @param OptionsResolver $resolver Options resolver
     */
    protected function configureParameters(OptionsResolver $resolver)
    {
        // Define all optional parameters
        $resolver->setDefined([
            'sales_stage',
            'sales_stage_exclude',
            'date_closed_from',
            'date_closed_to',
            'date_closed_period',
            'amount_min',
            'amount_max',
            'probability_min',
            'probability_max',
            'assigned_user_id',
            'assigned_user_id_current',
            'lead_source',
            'account_id',
            'group_by',
            'page',
            'sort'
        ]);

        // Set allowed types for type safety
        $resolver->setAllowedTypes('sales_stage', ['string', 'null']);
        $resolver->setAllowedTypes('sales_stage_exclude', ['string', 'null']);
        $resolver->setAllowedTypes('date_closed_from', ['string', 'null']);
        $resolver->setAllowedTypes('date_closed_to', ['string', 'null']);
        $resolver->setAllowedTypes('date_closed_period', ['string', 'null']);
        $resolver->setAllowedTypes('amount_min', ['string', 'int', 'float', 'null']);
        $resolver->setAllowedTypes('amount_max', ['string', 'int', 'float', 'null']);
        $resolver->setAllowedTypes('probability_min', ['string', 'int', 'null']);
        $resolver->setAllowedTypes('probability_max', ['string', 'int', 'null']);
        $resolver->setAllowedTypes('assigned_user_id', ['string', 'null']);
        $resolver->setAllowedTypes('assigned_user_id_current', ['string', 'bool', 'int', 'null']);
        $resolver->setAllowedTypes('lead_source', ['string', 'null']);
        $resolver->setAllowedTypes('account_id', ['string', 'null']);
        $resolver->setAllowedTypes('group_by', ['string', 'null']);
        $resolver->setAllowedTypes('page', ['array', 'null']);
        $resolver->setAllowedTypes('sort', ['string', 'null']);

        // Set defaults
        $resolver->setDefaults([
            'sales_stage' => null,
            'sales_stage_exclude' => null,
            'date_closed_from' => null,
            'date_closed_to' => null,
            'date_closed_period' => null,
            'amount_min' => null,
            'amount_max' => null,
            'probability_min' => null,
            'probability_max' => null,
            'assigned_user_id' => null,
            'assigned_user_id_current' => false,
            'lead_source' => null,
            'account_id' => null,
            'group_by' => null,
            'page' => null,
            'sort' => null
        ]);
    }
}
