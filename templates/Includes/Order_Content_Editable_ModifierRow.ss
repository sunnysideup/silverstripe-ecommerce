<tr  class="$Classes hideOnZeroItems<% if HideInAjaxUpdate %> hideForNow<% end_if %>"  id="$AJAXDefinitions.TableID">
	<td colspan="3" scope="row">
		<% if MoreInfoPage %>
			<a class="tableTitle" id="$AJAXDefinitions.TableTitleID" href="$MoreInfoPage.Link" >$TableTitle</a>
		<% else %>
			<span class="tableTitle" id="$AJAXDefinitions.TableTitleID">$TableTitle</span>
		<% end_if %>
		<div class="tableSubTitle" id="$AJAXDefinitions.TableSubTitleID">$TableSubTitle</div >
	</td>
	<td class="right total" id="$AJAXDefinitions.TableTotalID">$TableValue.Nice</td>
	<td class="right remove">
		<% if CanBeRemoved %>
			<strong>
				<a class="ajaxQuantityLink" href="$RemoveLink" title="<% sprintf(_t("Order.REMOVE","Remove &quot;%s&quot; from your order"),$TableTitle) %>">
					<img src="ecommerce/images/remove.gif" alt="x" />
				</a>
			</strong>
		<% end_if %>
	</td>
</tr>
