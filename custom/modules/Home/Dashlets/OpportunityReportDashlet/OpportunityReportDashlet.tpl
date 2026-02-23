{*
 * Opportunity Report Dashlet Template
 *
 * Displays Opportunity report data with filtering controls
 *}

<div class="dashletPanelBody" id="dashlet_{$DASHLET_ID}">
    {if $error}
        <div class="alert alert-danger" style="padding: 10px; margin: 10px; background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24;">
            <strong>Error:</strong> {$error_message}
            <br/>
            <button type="button" onclick="location.reload();" style="margin-top: 5px;">Retry</button>
        </div>
    {else}
        {* Filter Form *}
        <form method="post" action="index.php" class="edit view" style="margin: 10px 0; padding: 10px; background-color: #f8f9fa; border: 1px solid #dee2e6;">
            <input type="hidden" name="module" value="Home" />
            <input type="hidden" name="action" value="DashletRefresh" />
            <input type="hidden" name="dashlet_id" value="{$DASHLET_ID}" />
            <input type="hidden" name="apply_filters" value="1" />

            <table width="100%" cellpadding="4" cellspacing="0">
                <tr>
                    <td width="25%">
                        <label for="sales_stage_{$DASHLET_ID}">Sales Stage:</label>
                        <select name="sales_stage" id="sales_stage_{$DASHLET_ID}" style="width: 100%;">
                            <option value="">-- All Stages --</option>
                            {foreach from=$sales_stages key=stage_key item=stage_label}
                                <option value="{$stage_key}" {if $current_filters.sales_stage == $stage_key}selected{/if}>
                                    {$stage_label}
                                </option>
                            {/foreach}
                        </select>
                    </td>
                    <td width="25%">
                        <label for="date_period_{$DASHLET_ID}">Date Range:</label>
                        <select name="date_period" id="date_period_{$DASHLET_ID}" style="width: 100%;">
                            {foreach from=$date_periods key=period_key item=period_label}
                                <option value="{$period_key}" {if $current_filters.date_closed_period == $period_key}selected{/if}>
                                    {$period_label}
                                </option>
                            {/foreach}
                        </select>
                    </td>
                    <td width="25%">
                        <label for="amount_min_{$DASHLET_ID}">Min Amount:</label>
                        <input type="number" name="amount_min" id="amount_min_{$DASHLET_ID}"
                               value="{$current_filters.amount_min|default:''}"
                               style="width: 100%;" placeholder="0.00" step="0.01" />
                    </td>
                    <td width="25%" style="text-align: right;">
                        <label>
                            <input type="checkbox" name="my_opportunities" value="1"
                                   {if $current_filters.assigned_user_id_current}checked{/if} />
                            My Opportunities
                        </label>
                        <br/>
                        <button type="submit" class="button" style="margin-top: 5px;">Apply Filters</button>
                    </td>
                </tr>
            </table>
        </form>

        {* Data Table *}
        {if $opportunities|@count > 0}
            <div style="overflow-x: auto;">
                <table class="list view table" cellpadding="0" cellspacing="0" width="100%">
                    <thead>
                        <tr>
                            <th scope="col" width="30%">Name</th>
                            <th scope="col" width="15%">Amount</th>
                            <th scope="col" width="20%">Sales Stage</th>
                            <th scope="col" width="15%">Close Date</th>
                            <th scope="col" width="20%">Assigned To</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach from=$opportunities item=opp name=opp_loop}
                            <tr class="{if $smarty.foreach.opp_loop.iteration % 2 == 0}evenListRowS1{else}oddListRowS1{/if}">
                                <td>
                                    <a href="index.php?module=Opportunities&action=DetailView&record={$opp.id}"
                                       title="View {$opp.attributes.name}">
                                        {$opp.attributes.name|escape}
                                    </a>
                                </td>
                                <td>
                                    ${$opp.attributes.amount_usdollar|number_format:2}
                                </td>
                                <td>
                                    {$opp.attributes.sales_stage|escape}
                                </td>
                                <td>
                                    {$opp.attributes.date_closed|escape}
                                </td>
                                <td>
                                    {$opp.attributes.assigned_user_name|default:'Unassigned'|escape}
                                </td>
                            </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>

            {* Summary Information *}
            {if $meta.total_count}
                <div style="margin: 10px; padding: 5px; background-color: #f8f9fa; border-top: 1px solid #dee2e6;">
                    <strong>Total Opportunities:</strong> {$meta.total_count}
                    {if $meta.aggregations.total_amount_usdollar}
                        | <strong>Total Amount:</strong> ${$meta.aggregations.total_amount_usdollar|number_format:2}
                    {/if}
                    {if $meta.aggregations.weighted_pipeline}
                        | <strong>Weighted Pipeline:</strong> ${$meta.aggregations.weighted_pipeline|number_format:2}
                    {/if}
                </div>
            {/if}
        {else}
            <div class="alert alert-info" style="padding: 10px; margin: 10px; background-color: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460;">
                No opportunities found matching your criteria.
            </div>
        {/if}
    {/if}
</div>
