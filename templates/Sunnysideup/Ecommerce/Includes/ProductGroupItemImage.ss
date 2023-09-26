<div class="productImage">
<% if Image %>
    <a href="$Link"><img src="$Image.URL" alt="<%t Product.IMAGE '{name} image' name=$Title.ATT %>" width="$Image.Width" height="$Image.Height" /></a>
<% else %>
    <a href="$Link" class="noImage"><img src="$EcomConfig.DefaultImageLink" alt="<% _t("Product.NOIMAGEAVAILABLE","no image available") %>" width="$DummyImage.Width" height="$DummyImage.Height" /></a>
<% end_if %>
</div>


