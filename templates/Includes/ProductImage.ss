		<div class="productImage">
<% if Image %>
			<a href="$Image.LargeImage.Link"><img class="realImage" src="$Image.ContentImage.URL" alt="<% sprintf(_t("Product.IMAGE","%s image"),$Title) %>" /></a>
<% end_if %>
		</div>
