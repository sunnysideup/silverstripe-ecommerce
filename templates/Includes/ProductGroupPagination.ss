<% if Products.MoreThanOnePage %>
	<div class="pageNumbers">
		<p>
			<span class="pagesLabel">Pages:</span>
	<% if Products.NotFirstPage %>
			<a class="prev" href="$Products.PrevLink" title="<% _t('ProductGroup.SHOWPREVIOUSPAGE','View the previous page') %>"><% _t('ProductGroup.PREVIOUS','previous') %></a>
	<% end_if %>
			<span>
	<% control Products.PaginationSummary(4) %>
				<% if CurrentBool %>$PageNum<% else %><% if Link %><a href="$Link" title="<% sprintf(_t("ProductGroup.GOTOPAGE","View page number %s"),$PageNum) %>">$PageNum</a><% else %>&hellip;<% end_if %><% end_if %>
	<% end_control %>
			</span>
	<% if Products.NotLastPage %>
			<a class="next" href="$Products.NextLink" title="<% _t('ProductGroup.SHOWNEXTPAGE','View the next page') %>"><% _t('ProductGroup.NEXT','next') %></a>
	<% end_if %>
		</p>
	</div>
<% end_if %>
