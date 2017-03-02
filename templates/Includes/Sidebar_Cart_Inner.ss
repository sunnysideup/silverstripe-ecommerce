<!--
NOTE:
Any element with the following classname: $AJAXDefinitions.SideBarCartID
will be set to the contents of this file when the cart is updated using AJAX
If you are not using this snippet then theme it and remove its content to speed up your AJAX cart.
You can also change the JSON response not to include this using YML config setting:
CartResponse.cart_responses_required
-->
<div class="sidebarCartInner">
<% if Items %>
    <table id="InformationTable" class="editable infotable">
        <thead></thead>
        <tbody>
    <% loop Items %>
        <% if ShowInTable %>
            <tr id="$AJAXDefinitions.TableID" class="$Classes hideOnZeroItems orderItemHolder">
                <td class="product title">
                    <% if Link %>
                        <a id="$AJAXDefinitions.CartTitleID" href="$Link">$CartTitle</a>
                    <% else %>
                        <span id="$AJAXDefinitions.CartTitleID">$CartTitle</span>
                    <% end_if %>
                    <div class="tableSubTitle" id="$AJAXDefinitions.CartSubTitleID">$CartSubTitle</div>
                </td>
                <td class="center quantity">
                    $QuantityField
                </td>
                <td class="right total" id="$AJAXDefinitions.TableTotalID">$TotalAsMoney.NiceDefaultFormat</td>
            </tr>
        <% end_if %>
    <% end_loop %>
        </tbody>
        <tfoot>
            <tr class="gap summary hideOnZeroItems subTotal">
                <th colspan="2" scope="row"><% _t("Order.SUBTOTAL","Sub-total") %></th>
                <td class="right" id="$AJAXDefinitions.TableSubTotalID">$SubTotalAsMoney.NiceDefaultFormat</td>
            </tr>
            <tr class="showOnZeroItems"<% if Items %> style="display: none"<% end_if %>>
                <td colspan="3" class="center"><% _t("Order.NOITEMS","There are <strong>no</strong> items in your cart.") %></td>
            </tr>
        </tfoot>
    </table>
    <p class="goToCart"><a href="$EcomConfig.CheckoutLink" class="action goToCheckoutLink"><% _t("Order.GOTOCHECKOUTLINK","Go to the checkout") %></a></p>
<% else %>
    <p class="noItems"><% _t("Order.NOITEMS","There are <strong>no</strong> items in your cart.") %></p>
<% end_if %>
</div>
