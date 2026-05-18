<% if Products.MoreThanOnePage %>
<div class="pageNumbers $AjaxDefinitions.ProductListAjaxifiedLinkClassName">
    <small>
        <span class="pagesLabel"><%t ProductGroup.PAGES 'Pages:' %></span>
    <% if Products.NotFirstPage %>
            <a class="prev" href="$Products.PrevLink" title="<%t ProductGroup.SHOWPREVIOUSPAGE 'View the previous page' %>"><%t ProductGroup.PREVIOUS 'previous' %></a>
    <% end_if %>
        <span>
    <% loop Products.PaginationSummary(4) %>
        <% if CurrentBool %>$PageNum<% else_if Link %><a href="$Link" title="<%t ProductGroup.GOTOPAGE 'View page number {number}.' number=$PageNum %>">$PageNum</a><% else %>&hellip;<% end_if %>
    <% end_loop %>
        </span>
    <% if Products.NotLastPage %>
        <a class="next" href="$Products.NextLink" title="<%t ProductGroup.SHOWNEXTPAGE 'View the next page' %>"><%t ProductGroup.NEXT 'next' %></a>
    <% end_if %>
    </small>
</div>
 <% end_if %>
 <% if TotalCountGreaterThanMax %><p class="message warning"><%t ProductGroup.TOTALCOUNTGREATERTHANMAX 'This list has been limited to {TotalCount} products.' TotalCount=$TotalCount %></p><% end_if %>
