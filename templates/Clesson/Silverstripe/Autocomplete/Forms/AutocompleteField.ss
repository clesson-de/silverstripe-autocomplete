<div id="$ID"
     class="ss-autocomplete-field $extraClass"
     data-endpoint="$EndpointURL"
     data-source-type="$SourceType"
     data-source-class="$SourceClass"
     data-source-property="$SourceProperty"
     data-callback-key="$CallbackKey"
     data-limit="$Limit"
     data-min-chars="$MinChars">
    <input type="text"
           name="$Name"
           value="$Value"
           class="text ss-autocomplete-field__input"
           placeholder="<%t Clesson\Silverstripe\Autocomplete\Forms\AutocompleteField.PLACEHOLDER 'Start typing...' %>"
           autocomplete="off" />
    <ul class="ss-autocomplete-field__list" hidden></ul>
</div>

