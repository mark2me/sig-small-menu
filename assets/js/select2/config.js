jQuery(document).ready(function($) {

    function iconFromValue(icon){
        return $('<span><i class="' + $(icon.element).val() + '"></i> ' + icon.text + '</span>');
    }

    $('.menu_item_icon.select2').select2({
        templateResult: iconFromValue,
        templateSelection: iconFromValue,
        escapeMarkup: function(m) { return m; }
    });
});