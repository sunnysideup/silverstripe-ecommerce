<tr  class="$Classes hideOnZeroItems<% if HideInAjaxUpdate %> hideForNow<% end_if %>"  id="$AJAXDefinitions.TableID">
	<td colspan="3" class="firstThreeCols">
			<% if ShowFormInEditableOrderTable %>
				<div class="modifierForm">$ModifierForm</div>
			<% else %>
				<span class="tableTitle" id="$AJAXDefinitions.TableTitleID">$TableTitle</span>
				<div class="tableSubTitle" id="$AJAXDefinitions.TableSubTitleID">$TableSubTitle</div >
			<% end_if %>
		<% if MoreInfoPage %>
			<div class="moreInfoLink"><a href="$MoreInfoPage.Link" ><% _t("Order.FIND_OUT_MORE","Find out more") %></a></div>
		<% end_if %>
	</td>
	<td class="right total" id="$AJAXDefinitions.TableTotalID">$TableValueAsMoney.NiceDefaultFormat</td>
	<td class="right remove">
		<% if CanBeRemoved %>
			<strong>
				<a class="ajaxQuantityLink ajaxRemoveFromCart" href="$RemoveLink">

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: /images/ (case sensitive)
  * NEW: /client/images/ (COMPLEX)
  * EXP: Check new location, also see: https://docs.silverstripe.org/en/4/developer_guides/templates/requirements/#direct-resource-urls
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
					<img src="ecommerce/client/images/remove.gif" alt="x" />
				</a>
			</strong>
		<% end_if %>
	</td>
</tr>
