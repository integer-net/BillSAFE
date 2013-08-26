var new_quote_form = Class.create(varienForm, {
    submit : function(url) {
        if(this.validator && this.validator.validate()){   
            if((document.getElementById("p_method_billsafe") != null && document.getElementById("p_method_billsafe").checked == true) ||
                (document.getElementById("p_method_billsafe_installment") != null && document.getElementById("p_method_billsafe_installment").checked == true)) {
                if(!lpg) {
                    var formElement = document.getElementById('edit_form');
                    
                    if(formElement.action.indexOf("admin_billsafe/adminhtml_billsafe_payment/gettoken") == -1) {
                        BILLSAFE_FORM_ACTION = formElement.action;
                        
                        if(IS_SECURE_URL == "1") {
                            BILLSAFE_FORM_ACTION = BILLSAFE_FORM_ACTION.replace("http://", "https://");
                        }
                    }
                    
                    if(IS_SECURE_URL == "1") {
                        SHOP_URL = SHOP_URL.replace("http://", "https://");
                    }
                    
                    formElement.action = SHOP_URL + "admin_billsafe/adminhtml_billsafe_payment/gettoken/key/"+ SECRET_KEY +"/";
                    
                    lpg = new BillSAFE.LPG.client({
                        form: formElement,
                        url_return: SHOP_URL + "admin_billsafe/adminhtml_billsafe_payment/verify/key/"+ SECRET_KEY_SUCCESS +"/",
                        conditions: {
                            invoice:
                            [{
                                element: 'payment[method]', 
                                value: 'billsafe'
                            }],
                            installment:
                            [{
                                element: 'payment[method]', 
                                value: 'billsafe_installment'
                            }]
                        },
                        sandbox: true
                    });
                }
            
                console.log("Alles korrekt, Payment-Layer öffnen");
                lpg.open();
                document.getElementById('edit_form').submit()
            } else {
                document.getElementById('edit_form').submit()
            }
        }
        return false;
    },
    
    /*
     * Will be executed after sending successful billsafe payment
     **/
    submitBillsafe: function() {
        var form = document.getElementById('edit_form');
        top.lpg.close();       
        
        form.action = BILLSAFE_FORM_ACTION;
        form.target = "_top";
        form.submit();
    }
})