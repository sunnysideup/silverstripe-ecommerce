<% if MenuChildGroups %>
<div id="MenuChildGroupsList" class="close openCloseSection">
	<ul>
	<% loop MenuChildGroups %>
		<li>
		<% if Image %>
			<div class="productGroupImage">
				<a href="$Link">
					<img class="productGroupSmallImage" src="$Image.SmallImage.URL" alt="<%t Product.IMAGE '{name} image' name=$Title.ATT %>" />
				</a>
			</div>
		<% end_if %>
			<h4><a href="$Link">$Title</a></h4>
			<div class="childGroupContent">
				$Content.Summary
			</div>
		</li>
	<% end_loop %>
	</ul>
</div>
<% end_if %>
