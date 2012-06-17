<% if MenuChildGroups %>
	<div class="menuChildGroups">
		<h3><% _t("Product.INTHISSECTION", "In this section") %></h3>
		<ul class="menuChildGroupsList">
	<% control MenuChildGroups %>
			<li>
				<h4><a href="$Link">$Title</a></h4>
				<div class="childGroupContent">
					$Content.Summary <a href="$Link">read more ...</a>
				</div>

			</li>
	<% end_control %>
		</ul>
	</div>
<% end_if %>
