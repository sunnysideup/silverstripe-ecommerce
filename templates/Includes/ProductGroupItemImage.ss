<div class="productImage">
<% if Image %>
	<a href="$Link"><img src="$Image.ContentImage.URL" alt="<% sprintf(_t("Product.IMAGE","%s image"),$Title) %>" width="$Image.ContentImage.Width" /></a>
<% else %>
	<a href="$Link" class="noImage"><img src="$DefaultImageLink" alt="<% _t("Product.NOIMAGEAVAILABLE","no image available") %>" width="$DummyImage.ContentWidth" ></a>
<% end_if %>
</div>


