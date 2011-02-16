<?php
/**
 * German (Germany) language pack
 * @package modules: ecommerce
 * @subpackage i18n
 */

i18n::include_locale_file('modules: ecommerce', 'en_US');

global $lang;

if(array_key_exists('de_DE', $lang) && is_array($lang['de_DE'])) {
	$lang['de_DE'] = array_merge($lang['en_US'], $lang['de_DE']);
} else {
	$lang['de_DE'] = $lang['en_US'];
}

$lang['de_DE']['AccountPage']['LINKTOACCOUNTPAGE'] = 'Gehen Sie zum ';
$lang['de_DE']['AccountPage']['LOGINGAGAIN'] = 'Sie wurden automatisch ausgeloggt. Bitte loggen Sie sich wieder ein.';
$lang['de_DE']['AccountPage']['MESSAGE'] = 'Sie müssen sich einloggen um auf Ihr Konto zugreifen zu können. Falls Sie nicht registriert sind, können Sie erst nach Ihrer ersten Bestellung auf Ihr Konto zugreifen. Fall Sie bereits registriert sind, geben Sie folgend Ihre Daten ein.';
$lang['de_DE']['AccountPage']['NOPAGE'] = 'Keine AccountPage auf dieser Website - erstellen Sie bitte eine!';
$lang['de_DE']['AccountPage']['ORDERNOTFOUND'] = 'Bestellung wurde nicht gefunden!';
$lang['de_DE']['AccountPage.ss']['COMPLETED'] = 'Abgeschlossene Bestellungen';
$lang['de_DE']['AccountPage.ss']['HISTORY'] = 'Ihr Bestellhistorie';
$lang['de_DE']['AccountPage.ss']['INCOMPLETE'] = 'Offene Bestellungen';
$lang['de_DE']['AccountPage.ss']['NOCOMPLETED'] = 'Es konnten keine abgeschlossenen Bestellungen gefunden werden.';
$lang['de_DE']['AccountPage.ss']['NOINCOMPLETE'] = 'Es konnten keine offenen Bestellungen gefunden werden.';
$lang['de_DE']['AccountPage.ss']['ORDER'] = 'Bestellung Nr.';
$lang['de_DE']['AccountPage.ss']['STATUS'] = 'Status';
$lang['de_DE']['AccountPage.ss']['READMORE'] = 'Zur Detail-Ansicht der Bestellung #%s';
$lang['de_DE']['Cart']['ADDONE'] = 'Hinzufügen eine oder mehr von  &quot;%s&quot;  in den Warenkorb';
$lang['de_DE']['Cart']['CHECKOUTCLICK'] = 'Hier klicken um zur Kasse zu gehen.';
$lang['de_DE']['Cart']['CHECKOUTGOTO'] = 'Zur Kasse';
$lang['de_DE']['Cart']['HEADLINE'] = 'Mein Warenkorb';
$lang['de_DE']['Cart']['NOITEMS'] = 'In Ihrem Warenkorb befinden sich zur Zeit keine Artikel';
$lang['de_DE']['Cart']['PRICE'] = 'Preis';
$lang['de_DE']['Cart']['QUANTITY'] = 'Menge';
$lang['de_DE']['Cart']['READMORE'] = 'Erfahren Sie hier mehr über &quot;%s&quot;';
$lang['de_DE']['Cart']['REMOVE'] = '&quot;%s&quot; aus Ihrem Warenkorb entfernen';
$lang['de_DE']['Cart']['REMOVEALL'] = 'Alle &quot;%s&quot; aus Warenkorb entfernen';
$lang['de_DE']['Cart']['REMOVEONE'] = 'Entfernen Sie eines von &quot;%s&quot; aus Ihrem Warenkorb';
$lang['de_DE']['Cart']['SHIPPING'] = 'Versandkosten';
$lang['de_DE']['Cart']['SUBTOTAL'] = 'Zwischensumme';
$lang['de_DE']['Cart']['TOTAL'] = 'Summe';
$lang['de_DE']['CheckoutPage']['NOPAGE'] = 'Auf dieser Site existiert keine Seite zum Ausschecken - bitte erstellen Sie eine neue Seite!';
$lang['de_DE']['CheckoutPage.ss']['CHECKOUT'] = 'Kasse';
$lang['de_DE']['CheckoutPage.ss']['ORDERSTATUS'] = 'Bestellstatus';
$lang['de_DE']['CheckoutPage.ss']['PROCESS'] = 'Prozess';
$lang['de_DE']['CheckoutPage_OrderIncomplete.ss']['BACKTOCHECKOUT'] = 'Klicken Sie hier um zur Kasse zurückzukehren';
$lang['de_DE']['CheckoutPage_OrderIncomplete.ss']['CHECKOUT'] = 'Kasse';
$lang['de_DE']['CheckoutPage_OrderIncomplete.ss']['CHEQUEINSTRUCTIONS'] = 'Falls Sie die Bezahlung per Scheck gewählt haben erhalten Sie eine E-Mail mit weiteren Details zur Abwicklung.';
$lang['de_DE']['CheckoutPage_OrderIncomplete.ss']['DETAILSSUBMITTED'] = 'Hier sind Ihre übermittelten Details';
$lang['de_DE']['CheckoutPage_OrderIncomplete.ss']['INCOMPLETE'] = 'Bestellung nicht vollständig';
$lang['de_DE']['CheckoutPage_OrderIncomplete.ss']['ORDERSTATUS'] = 'Bestellstatus';
$lang['de_DE']['CheckoutPage_OrderIncomplete.ss']['PROCESS'] = 'Prozess';
$lang['de_DE']['CheckoutPage_OrderSuccessful.ss']['BACKTOCHECKOUT'] = 'Klicken Sie hier um zur Kasse zurückzukehren';
$lang['de_DE']['CheckoutPage_OrderSuccessful.ss']['CHECKOUT'] = 'Auschecken';
$lang['de_DE']['CheckoutPage_OrderSuccessful.ss']['EMAILDETAILS'] = 'Zur Bestätigung wurde eine Kopie an Ihre E-Mail Adresse verschickt';
$lang['de_DE']['CheckoutPage_OrderSuccessful.ss']['ORDERSTATUS'] = 'Bestellstatus';
$lang['de_DE']['CheckoutPage_OrderSuccessful.ss']['PROCESS'] = 'Prozess';
$lang['de_DE']['CheckoutPage_OrderSuccessful.ss']['SUCCESSFULl'] = 'Bestellung erfolgreich durchgeführt';
$lang['de_DE']['ChequePayment']['MESSAGE'] = 'Bezahlung per Scheck (Vorkasse). Bitte beachten: Der Versand der Produkte erfolgt erst nach Zahlungseingang.';
$lang['de_DE']['DataReport']['EXPORTCSV'] = 'Export als CSV';
$lang['de_DE']['EcommerceRole']['PERSONALINFORMATION'] = 'Ihre Daten';
$lang['de_DE']['EcommerceRole']['COUNTRY'] = 'Land';
$lang['de_DE']['EcommerceRole']['FIRSTNAME'] = 'Vorname';
$lang['de_DE']['EcommerceRole']['SURNAME'] = 'Nachname';
$lang['de_DE']['EcommerceRole']['HOMEPHONE'] = 'Tel.';
$lang['de_DE']['EcommerceRole']['MOBILEPHONE'] = 'Mobil';
$lang['de_DE']['EcommerceRole']['EMAIL'] = 'Email';
$lang['de_DE']['EcommerceRole']['ADDRESS'] = 'Adresse';
$lang['de_DE']['EcommerceRole']['ADDRESSLINE2'] = '&nbsp;';
$lang['de_DE']['EcommerceRole']['CITY'] = 'Stadt';
$lang['de_DE']['EcommerceRole']['POSTALCODE'] = 'PLZ';
$lang['de_DE']['EcomQuantityField.ss']['ADDONE'] = '1 &quot;%s&quot; zum Warenkorb hinzufügen';
$lang['de_DE']['EcomQuantityField.ss']['REMOVEONE'] = '1 &quot;%s&quot; aus dem Warenkorb entfernen';
$lang['de_DE']['FindOrderReport']['DATERANGE'] = 'Zeitraum';
$lang['de_DE']['MemberForm']['DETAILSSAVED'] = 'Ihre Daten wurden gespeichert';
$lang['de_DE']['MemberForm']['LOGGEDIN'] = 'Sie sind angemeldet als ';
$lang['de_DE']['MemberForm']['LOGOUT'] = 'Klicken Sie <a href="Security/logout" title="Klicken Sie hier um sich abzumelden">hier</a> um sich abzumelden.';
$lang['de_DE']['MemberForm']['LOGINDETAILS'] = 'Konto Details';
$lang['de_DE']['MemberForm']['PASSWORD'] = 'Passwort';
$lang['de_DE']['MemberForm']['SAVE'] = 'Speichern';
$lang['de_DE']['MemberForm']['SAVEANDPROCEED'] = 'Speichern und Bestellung abschließen';
$lang['de_DE']['Order']['CANCELORDER'] = 'Bestellung stornieren';
$lang['de_DE']['Order.ss']['TOTALOUTSTANDING'] = 'Gesamt ausstehend';
$lang['de_DE']['Order.ss']['CUSTOMERORDERNOTE'] = 'Kunden Benachrichtigung';
$lang['de_DE']['Order_Content.ss']['NOITEMS'] = 'Ihre Bestellung weist <strong>keine</strong> Artikel auf';
$lang['de_DE']['Order_Content.ss']['PRICE'] = 'Preis';
$lang['de_DE']['Order_Content.ss']['PRODUCT'] = 'Produkt';
$lang['de_DE']['Order_Content.ss']['QUANTITY'] = 'Menge';
$lang['de_DE']['Order_Content.ss']['READMORE'] = 'Wenn zu mehr über &quot;%s&quot; erfahren willst, klick hier';
$lang['de_DE']['Order_Content.ss']['SUBTOTAL'] = 'Zwischensumme';
$lang['de_DE']['Order_Content.ss']['TOTAL'] = 'Gesamt';
$lang['de_DE']['Order_Content.ss']['TOTALPRICE'] = 'Gesamtpreis';
$lang['de_DE']['Order_Content_Editable.ss']['NOITEMS'] = 'Es sind <strong>keine</strong> Artikel in Ihrem Warenkorb';
$lang['de_DE']['Order_Content_Editable.ss']['ORDERINFORMATION'] = 'Bestellinformationen';
$lang['de_DE']['Order_Content_Editable.ss']['PRICE'] = 'Preis';
$lang['de_DE']['Order_Content_Editable.ss']['PRODUCT'] = 'Produkt';
$lang['de_DE']['Order_Content_Editable.ss']['QUANTITY'] = 'Menge';
$lang['de_DE']['Order_Content_Editable.ss']['READMORE'] = 'Erfahren Sie hier mehr über &quot;%s&quot;';
$lang['de_DE']['Order_Content_Editable.ss']['REMOVE'] = '&quot;%s&quot; aus Ihrem Warenkorb entfernen';
$lang['de_DE']['Order_Content_Editable.ss']['REMOVEALL'] = '&quot;%s&quot; komplett aus dem Warenkorb entfernen';
$lang['de_DE']['Order_Content_Editable.ss']['SHIPPING'] = 'Versandkosten';
$lang['de_DE']['Order_Content_Editable.ss']['SHIPPINGTO'] = 'an';
$lang['de_DE']['Order_Content_Editable.ss']['SUBTOTAL'] = 'Zwischensumme';
$lang['de_DE']['Order_Content_Editable.ss']['TABLESUMMARY'] = 'Hier werden alle Artikel im Warenkorb und eine Zusammenfassung aller für die Bestellung anfallender Gebühren angezeigt. Außerdem wird ein Überblick aller Zahlungsmöglichkeiten angezeigt.';
$lang['de_DE']['Order_Content_Editable.ss']['TOTAL'] = 'Gesamt';
$lang['de_DE']['Order_Content_Editable.ss']['TOTALPRICE'] = 'Gesamtpreis';
$lang['de_DE']['Order_Member.ss']['ADDRESS'] = 'Adresse';
$lang['de_DE']['Order_Member.ss']['CITY'] = 'Stadt';
$lang['de_DE']['Order_Member.ss']['COUNTRY'] = 'Land';
$lang['de_DE']['Order_Member.ss']['EMAIL'] = 'E-Mail';
$lang['de_DE']['Order_Member.ss']['MOBILE'] = 'Handy';
$lang['de_DE']['Order_Member.ss']['NAME'] = 'Name';
$lang['de_DE']['Order_Member.ss']['PHONE'] = 'Telefon';
$lang['de_DE']['Order_Payments.ss']['PAYMENTMETHOD'] = 'Zahlart';
$lang['de_DE']['Order_Payments.ss']['PAYMENTSTATUS'] = 'Bezahlstatus';
$lang['de_DE']['Order_Payments.ss']['PAYMENTS'] = 'Zahlart';
$lang['de_DE']['Order_Payments.ss']['PAYMENTNOTE'] = 'Anmerkung';
$lang['de_DE']['Order_Payments.ss']['DATE'] = 'Datum';
$lang['de_DE']['Order_Payments.ss']['AMOUNT'] = 'Betrag';
$lang['de_DE']['Order_Shipping.ss']['SHIPTO'] = 'Lieferadresse (falls abweichend)';
$lang['de_DE']['Order_Shipping.ss']['PURCHASEDBY'] = 'Rechnungsadresse';
$lang['de_DE']['OrderForm']['USEDIFFERENTSHIPPINGADDRESS'] = 'andere Lieferadresse wählen';
$lang['de_DE']['OrderForm']['PROCESSORDER'] = 'Bestellung ausführen';
$lang['de_DE']['OrderForm']['MEMBERSHIPDETAILS'] = 'Kunden-Konto Details';
$lang['de_DE']['OrderForm']['PASSWORD'] = 'Passwort';
$lang['de_DE']['OrderForm']['COMPLETEORDER'] = 'Bestellung abschließen';
$lang['de_DE']['OrderForm']['ACCOUNTINFO'] = 'Bitte wählen Sie ein Passwort, damit Sie sich zukünftig einloggen können und Ihre Bestellhistorie anschauen können.';
$lang['de_DE']['OrderForm']['MEMBERINFO'] = 'Haben Sie bereits ein Kunden-Konto?';
$lang['de_DE']['OrderForm']['LOGIN'] = 'Loggen Sie sich ein.';
$lang['de_DE']['OrderForm']['COUNTRY'] = 'Land';
$lang['de_DE']['OrderForm']['NAME'] = 'Name';
$lang['de_DE']['OrderForm']['CITY'] = 'Stadt';
$lang['de_DE']['OrderForm']['ADDRESS'] = 'Adresse';
$lang['de_DE']['OrderForm']['ADDRESS2'] = '&nbsp;';
$lang['de_DE']['OrderForm']['SHIPPINGPOSTALCODE'] = 'PLZ';
$lang['de_DE']['OrderForm']['SENDGOODSTODIFFERENTADDRESS'] = 'Abweichende Lieferadresse';
$lang['de_DE']['OrderForm']['SHIPPINGNOTE'] = 'Die Bestellung wird an folgende Adresse versendet.';
$lang['de_DE']['OrderForm']['HELP'] = 'Sie können dies für Geschenke benutzen. Es werden keine Rechnungsinformationen mit versandt.';
$lang['de_DE']['OrderForm']['CUSTOMERNOTE'] = 'Bemerkungen';
$lang['de_DE']['OrderForm']['AGREEWITHTERMS1'] = 'Ich habe die Informationen zur Bestellung und die ';
$lang['de_DE']['OrderForm']['AGREEWITHTERMS2'] = ' gelesen und verstanden.';
$lang['de_DE']['OrderForm']['NOITEMSINCART'] = 'Sie haben keine Produkte ausgewählt. Bitte legen Sie Produkte in den Warenkorb.';
$lang['de_DE']['OrderForm']['PRICEUPDATED'] = 'Die Bestellsumme wurde aktualisiert.';
$lang['de_DE']['Order_ReceiptEmail.ss']['HEADLINE'] = 'Auftragsbestätigung';
$lang['de_DE']['Order_ReceiptEmail.ss']['TITLE'] = 'Shop Empfangsbestätigung';
$lang['de_DE']['Order_StatusEmail.ss']['HEADLINE'] = 'Shop-Status Änderung';
$lang['de_DE']['Order_StatusEmail.ss']['STATUSCHANGE'] = 'Status geändert zu "%s" für Bestellung Nr.';
$lang['de_DE']['Order_StatusEmail.ss']['TITLE'] = 'Shop-Status Änderung';
$lang['de_DE']['Payment']['Incomplete'] = 'Unvollständig';
$lang['de_DE']['Payment']['Success'] = 'Erfolg';
$lang['de_DE']['Payment']['Failure'] = 'Misserfolg';
$lang['de_DE']['Payment']['Pending'] = 'Warteschleife';
$lang['de_DE']['Payment']['Paid'] = 'Bezahlt';
$lang['de_DE']['Product']['ADD'] = '&quot;%s&quot; zum Warenkorb hinzufügen';
$lang['de_DE']['Product']['ADDLINK'] = 'Diesen Artikel zum Warenkorb hinzufügen';
$lang['de_DE']['Product']['ADDONE'] = '&quot;%s&quot; zum Warenkorb hinzufügen';
$lang['de_DE']['Product']['AUTHOR'] = 'Autor';
$lang['de_DE']['Product']['FEATURED'] = 'Wir empfehlen diesen Artikel.';
$lang['de_DE']['Product']['GOTOPAGE'] = 'Zur %s Seite';
$lang['de_DE']['Product']['GOTOCHECKOUT'] = 'Jetzt zur Kasse gehen';
$lang['de_DE']['Product']['GOTOCHECKOUTLINK'] = '&raquo; Zur Kasse';
$lang['de_DE']['Product']['IMAGE'] = '%s Bild';
$lang['de_DE']['Product']['ITEMID'] = 'Artikel Nr.';
$lang['de_DE']['Product']['MODEL'] = 'Typ';
$lang['de_DE']['Product']['NOIMAGE'] = 'Keine Produktabbildung vorhanden für &quot;%s&quot;';
$lang['de_DE']['Product']['QUANTITYCART'] = 'Menge im Warenkorb';
$lang['de_DE']['Product']['REMOVE'] = '&quot;%s&quot; aus dem Warenkorb entfernen';
$lang['de_DE']['Product']['REMOVEALL'] = '&quot;%s&quot; aus dem Warenkorb entfernen';
$lang['de_DE']['Product']['REMOVELINK'] = '&raquo; aus dem Warenkorb entfernen';
$lang['de_DE']['Product']['SIZE'] = 'Größe';
$lang['de_DE']['ProductGroup']['GOTOPAGE'] = 'Gehe zu Seite %s';
$lang['de_DE']['ProductGroup']['OTHER'] = 'Andere Produkte';
$lang['de_DE']['ProductGroup']['PREVIOUS'] = 'Vorherige';
$lang['de_DE']['ProductGroup']['SORTBY'] = 'Sortieren nach';
$lang['de_DE']['ProductGroup']['SORTBYALPHABETICAL'] = 'Alphabet';
$lang['de_DE']['ProductGroup']['SORTBYFEATURED'] = 'Empfohlene Artikel';
$lang['de_DE']['ProductGroup']['SORTBYLOWESTPRICE'] = 'billigstem Preis';
$lang['de_DE']['ProductGroup']['SORTBYMOSTPOPULAR'] = 'Beliebtheit';
$lang['de_DE']['ProductGroup']['SHOWNEXTPAGE'] = 'Nächste Seite';
$lang['de_DE']['ProductGroup']['SHOWPREVIOUSPAGE'] = 'Vorherige Seite';
$lang['de_DE']['ProductGroup']['VIEWGROUP'] = 'Produktgruppe &quot;%s&quot; anzeigen';
$lang['de_DE']['ProductGroupItem.ss']['ADD'] = '&quot;%s&quot; zum Warenkorb hinzufügen';
$lang['de_DE']['ProductGroupItem.ss']['ADDLINK'] = 'Diesen Artikel zum Warenkorb hinzufügen';
$lang['de_DE']['ProductGroupItem.ss']['ADDONE'] = '&quot;%s&quot; zum Warenkorb hinzufügen';
$lang['de_DE']['ProductGroupItem.ss']['AUTHOR'] = 'Autor';
$lang['de_DE']['ProductGroupItem.ss']['GOTOCHECKOUT'] = 'Zur Kasse';
$lang['de_DE']['ProductGroupItem.ss']['GOTOCHECKOUTLINK'] = '&raquo; Zur Kasse';
$lang['de_DE']['ProductGroupItem.ss']['IMAGE'] = '%s Bild';
$lang['de_DE']['ProductGroupItem.ss']['NOIMAGE'] = 'Keine Produktabbildung vorhanden für &quot;%s&quot;';
$lang['de_DE']['ProductGroupItem.ss']['QUANTITY'] = 'Menge';
$lang['de_DE']['ProductGroupItem.ss']['QUANTITYCART'] = 'Menge im Warenkorb';
$lang['de_DE']['ProductGroupItem.ss']['READMORE'] = 'Erfahren Sie hier mehr über &quot;%s&quot;';
$lang['de_DE']['ProductGroupItem.ss']['READMORECONTENT'] = 'mehr &raquo;';
$lang['de_DE']['ProductGroupItem.ss']['REMOVE'] = '&quot;%s&quot; vom Warenkorb entfernen.';
$lang['de_DE']['ProductGroupItem.ss']['REMOVEALL'] = '1 Einheit von &quot;%s&quot; aus dem Warenkorb entferne';
$lang['de_DE']['ProductGroupItem.ss']['REMOVELINK'] = '&raquo; Aus dem Warenkorb entfernen';
$lang['de_DE']['ProductGroupItem.ss']['REMOVEONE'] = '&quot;%s&quot; vom Warenkorb entferrnen';
$lang['de_DE']['ShopAccountForm']['DETAILSSAVED'] = 'Ihre Daten wurden gespeichert';
$lang['de_DE']['ShopAccountForm']['LOGGEDIN'] = 'Sie sind angemeldet als ';
$lang['de_DE']['ShopAccountForm']['LOGOUT'] = 'Klicken Sie <a href="Security/logout" title="Klicken Sie hier um sich abzumelden">hier</a> um sich abzumelden.';
$lang['de_DE']['ShopAccountForm']['LOGINDETAILS'] = 'Konto Details';
$lang['de_DE']['ShopAccountForm']['PASSWORD'] = 'Passwort';
$lang['de_DE']['ShopAccountForm']['SAVE'] = 'Speichern';
$lang['de_DE']['ShopAccountForm']['SAVEANDPROCEED'] = 'Speichern und Bestellung abschließen';
$lang['de_DE']['SSReport']['ALLCLICKHERE'] = 'Klicken Sie hier, um alle Produkte anzuzeigen';
$lang['de_DE']['SSReport']['INVOICE'] = 'Rechnung';
$lang['de_DE']['SSReport']['PRINT'] = 'drucken';
$lang['de_DE']['SSReport']['VIEW'] = 'anzeigen';
$lang['de_DE']['ViewAllProducts.ss']['AUTHOR'] = 'Autor';
$lang['de_DE']['ViewAllProducts.ss']['CATEGORIES'] = 'Kategorien';
$lang['de_DE']['ViewAllProducts.ss']['IMAGE'] = '%s Bild';
$lang['de_DE']['ViewAllProducts.ss']['LASTEDIT'] = 'Zuletzt bearbeitet';
$lang['de_DE']['ViewAllProducts.ss']['LINK'] = 'Link';
$lang['de_DE']['ViewAllProducts.ss']['NOCONTENT'] = 'Keine Inhalte vorhanden.';
$lang['de_DE']['ViewAllProducts.ss']['NOIMAGE'] = 'Kein Bild für &quot;%s&quot; vorhanden.';
$lang['de_DE']['ViewAllProducts.ss']['NOSUBJECTS'] = 'Keine Produkte vorhanden.';
$lang['de_DE']['ViewAllProducts.ss']['PRICE'] = 'Preis';
$lang['de_DE']['ViewAllProducts.ss']['PRODUCTID'] = 'Produkt ID';
$lang['de_DE']['ViewAllProducts.ss']['WEIGHT'] = 'Gewicht';
