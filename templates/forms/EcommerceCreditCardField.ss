<span id="{$Name}_Holder" class="creditCardField">
	<input name="{$Name}[0]" class="ecommercecreditcard" type="text" value="{$ValueOne}" pattern="[0-9]{4}"  $TabIndexHTML(0) maxlength="4" />
	<span class="enDash">-</span>
	<input name="{$Name}[1]" class="ecommercecreditcard" type="text" value="{$ValueTwo}" pattern="[0-9]{4}"  $TabIndexHTML(1) maxlength="4" />
	<span class="enDash">-</span>
	<input name="{$Name}[2]" class="ecommercecreditcard" type="text" value="{$ValueThree}" pattern="[0-9]{4}"  $TabIndexHTML(2) maxlength="4"  />
	<span class="enDash">-</span>
	<input name="{$Name}[3]" class="ecommercecreditcard" type="text" value="{$ValueFour}" pattern="[0-9]{1,4}"  $TabIndexHTML(3) maxlength="4" />
</span>
