<?php
function commerce_dibs_flexwin_success() {
    if(isset($_POST['realorderid'])) {
        $order = commerce_order_load($_POST['realorderid']);
        $payment_method = commerce_payment_method_instance_load($order->data['payment_method']);
        if (!$payment_method || $payment_method['method_id'] != 'dibs_flexwin') {
            return false;
        }
        if(!commerce_dibs_flexwin_transaction_already_exists($_POST['transact'])){
            watchdog('dibs_flexwin', 'Customer successfully returned back from DIBS : !order_id.', 
                array('!order_id' => check_plain($_POST['realorderid'])));
            $transaction_status = COMMERCE_PAYMENT_STATUS_SUCCESS;
            drupal_set_message("Successful transaction: " . (int)$_POST['realorderid'], 'status');
            commerce_dibs_flexwin_process_transaction($order, $payment_method, $transaction_status, 'success');
           
        }
        else {
            drupal_goto('checkout/' . $order->order_id . '/payment/return/' . $order->data['payment_redirect_key']);
        }
    }
    else {
        drupal_goto(url('cart', array('absolute' => TRUE)));
    }
}

function commerce_dibs_flexwin_callback() {
    if(isset($_POST['realorderid'])) {
        
        $order = commerce_order_load($_POST['realorderid']);
        $payment_method = commerce_payment_method_instance_load($order->data['payment_method']);
        if (!$payment_method || $payment_method['method_id'] != 'dibs_flexwin') {
            return false;
        }
        if(!commerce_dibs_flexwin_transaction_already_exists($_POST['transact'])){
            watchdog('dibs_flexwin', 'Callback performed by DIBS for order: !order_id.', 
                array('!order_id' => check_plain($_POST['realorderid'])));
            
            $transaction_status = COMMERCE_PAYMENT_STATUS_SUCCESS;
            drupal_set_message("Callback from DIBS : " . (int)$_POST['realorderid'], 'status');
            commerce_dibs_flexwin_process_transaction($order, $payment_method, $transaction_status, 'callback');
            
        }
        else exit(0);
    }else exit(0);
}