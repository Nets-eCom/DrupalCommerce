<?php

function commerce_dibspw_success() {
    if(isset($_POST['orderid'])) {
        watchdog('dibspw', 'Callback performed for order: !order_id.', 
                array('!order_id' => check_plain($_POST['orderid'])));

        $order = commerce_order_load($_POST['orderid']);
        $payment_method = commerce_payment_method_instance_load($order->data['payment_method']);
        if (!$payment_method || $payment_method['method_id'] != 'dibspw') return FALSE;
        dibs_pw_helpers_cms::cms_dibs_setSettings($payment_method['settings']);
        $mResult = getDibspwObj()->api_dibs_action_success($order);
        if(empty($mResult)){
            $transaction_status = COMMERCE_PAYMENT_STATUS_SUCCESS;
            drupal_set_message("Successful transaction: " . (int)$_POST['orderid'], 'status');
            commerce_dibspw_process_transaction($order, $payment_method, $transaction_status);
        }
        else dibs_pw_helpers_cms::cms_dibs_gotoCart($mResult);
    }
    else dibs_pw_helpers_cms::cms_dibs_gotoCart(getDibspwObj()->helper_dibs_tools_lang(1, 'err'));
}

function commerce_dibspw_callback() {
    if(isset($_POST['orderid']) && $_POST['status'] == "ACCEPTED") {
        watchdog('dibspw', 'Callback performed for order: !order_id.', 
             array('!order_id' => check_plain($_POST['orderid'])));
        $order = commerce_order_load($_POST['orderid']);
        commerce_checkout_complete($order);
        $payment_method = commerce_payment_method_instance_load($order->data['payment_method']);
        dibs_pw_helpers_cms::cms_dibs_setSettings($payment_method['settings']);
        if (!$payment_method || $payment_method['method_id'] != 'dibspw') return FALSE;
        getDibspwObj()->api_dibs_action_callback($order);
    }
    else exit((string)11);
}

function commerce_dibspw_cancel() {
    if(isset($_POST['orderid'])) {
        watchdog('dibspw', 'Order canceled: !order_id.', array('!order_id' => check_plain($_POST['orderid'])));
        $order = commerce_order_load($_POST['orderid']);
        commerce_order_status_update($order, "canceled");
        $payment_method = commerce_payment_method_instance_load($order->data['payment_method']);
        dibs_pw_helpers_cms::cms_dibs_setSettings($payment_method['settings']);
        getDibspwObj()->api_dibs_action_cancel();
        dibs_pw_helpers_cms::cms_dibs_gotoCart('Order canceled: ' . (int)$_POST['orderid'], 'status');
    }
    else dibs_pw_helpers_cms::cms_dibs_gotoCart(getDibspwObj()->helper_dibs_tools_lang(1, 'err'));
}

function commerce_dibspw_redirect() {
    unset($_POST['op'], $_POST['form_build_id'], $_POST['form_token'], $_POST['form_id']);
        
    $sOutput = '';
    foreach($_POST as $sKey => $sVal) {
        $sOutput .= '<input type="hidden" name="'.$sKey.'" value="'.$sVal.'" />'."\r\n";
    }
    
    $sPage = '<form id="dibspw-payment" action="' . getDibspwObj()->api_dibs_get_formAction(TRUE) . '" method="POST">
                '.$sOutput.'
              </form>
              <script type="text/javascript">
                setTimeout("document.getElementById(\'dibspw-payment\').submit();",1000);
              </script>';

    return $sPage;
}