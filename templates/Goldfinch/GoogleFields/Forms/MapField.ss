<div class="goldfinch-google-map" data-goldfinch-google-map-field="{$Name}">
	$SearchField.SmallFieldHolder
	$MapField.SmallFieldHolder
  <div class="ggm__extrafields form__fieldgroup <% if $extraClass %>$extraClass<% end_if %>" id="$ID" <% include SilverStripe/Forms/AriaAttributes %>>
	$LongitudeField.SmallFieldHolder
	$LatitudeField.SmallFieldHolder
	$ZoomField.SmallFieldHolder
  </div>
</div>
