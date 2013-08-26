function billsafeCheckAGB() {
    if ($("checkout-agreements") != null) {
        for(var i = 0; i < $("checkout-agreements").elements.length; i++) {
            if($("checkout-agreements").elements[i].checked == false) {
                alert("Bitte akzeptieren Sie die AGB")
                return false;
            }
        }
        
        return true;
    } else {
        return true;
    }
}

function billsafeGoLayer() {
    if (billsafeCheckAGB() == true) {
        lpg.open();
        document.getElementById('co-payment-form').submit()
    }
}

function billsafeGoGateway(base_url) {
    if (billsafeCheckAGB() == true) {
        $('checkout-agreements').action = base_url + 'billsafe/payment/prepareorder';
        $('checkout-agreements').submit()
    }
}