/**
 * Add error handling to core function
 * @see js/mage/adminhtml/tools.js submitAndReloadArea()
 * 
 */
function submitAndReloadBillsafeArea(area, url) {
    if ($(area)) {
        var fields = $(area).select('input', 'select', 'textarea');
        var data = Form.serializeElements(fields, true);
        url = url + (url.match(new RegExp('\\?')) ? '&isAjax=true' : '?isAjax=true');
        new Ajax.Request(url, {
            parameters: $H(data),
            loaderArea: area,
            onSuccess: function(transport) {
                try {
                    if (transport.responseText.isJSON()) {
                        var response = transport.responseText.evalJSON()
                        if (response.error) {
                            alert(response.message);
                        }
                        if (response.ajaxExpired && response.ajaxRedirect) {
                            setLocation(response.ajaxRedirect);
                        }
                    } else {
                        $(area).update(transport.responseText);
                    }
                }
                catch (e) {
                    $(area).update(transport.responseText);
                }
            },
            onFailure: function(transport) {
                $(area).insert({
                    top: '<span class="error">' + transport.responseText + '</span>'
                });
            },
            onLoaded: function(transport) {
                $(area).select(".error").each(function(element, index) {
                    element.remove();
                });
            }
        });
    }
}
