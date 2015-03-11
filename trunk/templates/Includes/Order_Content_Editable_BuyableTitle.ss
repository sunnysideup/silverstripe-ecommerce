	<div class="itemTitleAndSubTitle">
		<% if Link %>
			<a id="$AJAXDefinitions.TableTitleID" href="$Link" title="<%t Order.READMORE 'Click here to read more on {name}' name=$TableTitle %>">$TableTitle</a>
		<% else %>
			<span id="$AJAXDefinitions.TableTitleID">$TableTitle</span>
		<% end_if %>
		<div class="tableSubTitle" id="$AJAXDefinitions.TableSubTitleID">$TableSubTitle</div>
	</div>
