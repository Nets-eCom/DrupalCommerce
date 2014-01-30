<?php
class dibs_pw_helpers extends dibs_pw_helpers_cms implements dibs_pw_helpers_interface {

    public static $bTaxAmount = true;
    
    /**
     * Process write SQL query (insert, update, delete) with build-in CMS ADO engine.
     * 
     * @param string $sQuery 
     */
    function helper_dibs_db_write($sQuery) {
        return db_query($sQuery);
    }
    
    /**
     * Read single value ($sName) from SQL select result.
     * If result with name $sName not found null returned.
     * 
     * @param string $sQuery
     * @param string $sName
     * @return mixed 
     */
    function helper_dibs_db_read_single($sQuery, $sName) {
        $mResult = db_query($sQuery);
        $aRecs = $mResult->fetchAll();
        return isset($aRecs[0]) ? $aRecs[0]->$sName : null;
    }
    
    /**
     * Return settings with CMS method.
     * 
     * @param string $sVar
     * @param string $sPrefix
     * @return string 
     */
    function helper_dibs_tools_conf($sVar, $sPrefix = 'dibspw_') {
        return dibs_pw_helpers_cms::cms_dibs_getSettings($sPrefix . $sVar);
    }
    
    /**
     * Return CMS DB table prefix.
     * 
     * @return string 
     */
    function helper_dibs_tools_prefix() {
        $sDbPrefix = isset($db_prefix['default']) ? $db_prefix['default'] : '';
        return "commerce_" . $sDbPrefix;
    }
    
    /**
     * Returns text by key using CMS engine.
     * 
     * @param type $sKey
     * @return type 
     */
    function helper_dibs_tools_lang($sKey, $sType = 'msg') {
        $aLang = $this->cms_dibs_get_textArray();
        $sFullKey = "dibspw_lang_" . $sType . "_" .$sKey;
        return isset($aLang[$sFullKey]) ? $aLang[$sFullKey] : '';
    }

    /**
     * Get full CMS url for page.
     * 
     * @param string $sLink
     * @return string 
     */
    function helper_dibs_tools_url($sLink) {
        return url($sLink, array('absolute' => TRUE));
    }
    
    /**
     * Build CMS order information to API object.
     * 
     * @param mixed $mOrderInfo
     * @param bool $bResponse
     * @return object 
     */
    function helper_dibs_obj_order($mOrderInfo, $bResponse = FALSE) {
        if($bResponse === TRUE) {
            return (object)array(
                'orderid'  => $mOrderInfo->order_number,
                'amount'   => $mOrderInfo->commerce_order_total[LANGUAGE_NONE][0]['amount'] / 100,
                'currency' => dibs_pw_api::api_dibs_get_currencyValue(
                    $mOrderInfo->commerce_order_total[LANGUAGE_NONE][0]['currency_code']
                )
            );            
        }
        else {
            return (object)array(
                'orderid'  => $mOrderInfo['order']->order_number,
                'amount'   => $mOrderInfo['wrapper']->commerce_order_total->amount->value() / 100,
                'currency' => dibs_pw_api::api_dibs_get_currencyValue(
                    $mOrderInfo['wrapper']->commerce_order_total->currency_code->value()
                )
            );
        }
    }
    
    /**
     * Build CMS each ordered item information to API object.
     * 
     * @param mixed $mOrderInfo
     * @return object 
     */
    function helper_dibs_obj_items($mOrderInfo) {
        $aItems = array();
        foreach($mOrderInfo['wrapper']->commerce_line_items as $mItem) {
            if($mItem->type->value() == 'product') {
                $mTmp = $mItem->commerce_total->value();
                if(count($mTmp) > 0) {
                    $mPrice = $mTmp['data']['components'][0]['price']['amount'];
                    if(function_exists('commerce_tax_components')) {
                        $mTax = commerce_tax_components($mTmp['data']['components']);
                        $mVat = isset($mTax[0]['price']['amount']) ? $mTax[0]['price']['amount'] : 0;
                    }
                    else $mVat = 0;
                }
                else { 
                    $mPrice = $mTmp['amount'];
                    $mVat = 0;
                }

                $aItems[] = (object)array(
                    'id'    => $mItem->commerce_product->value()->product_id,
                    'name'  => $mItem->commerce_product->value()->title,
                    'sku'   => $mItem->commerce_product->value()->sku,
                    'price' => dibs_pw_api::api_dibs_round($mPrice) / (10000 * $mItem->quantity->value()),
                    'qty'   => $mItem->quantity->value(),
                    'tax'   => dibs_pw_api::api_dibs_round($mVat) / (10000 * $mItem->quantity->value())
                );
            }
        }

        return $aItems;
    }
    
    /**
     * Build CMS shipping information to API object.
     * 
     * @param mixed $mOrderInfo
     * @return object 
     */
    function helper_dibs_obj_ship($mOrderInfo) {
        $mPrice = 0;
        $mVat = 0;
        foreach($mOrderInfo['wrapper']->commerce_line_items as $mItem) {
            if($mItem->type->value() == 'shipping') {
                $mTmp = $mItem->commerce_total->value();
                if(count($mTmp) > 0) {
                    $mPrice = $mTmp['data']['components'][0]['price']['amount'];
                    if(function_exists('commerce_tax_components')) {
                        $mTax = commerce_tax_components($mTmp['data']['components']);
                        $mVat = isset($mTax[0]['price']['amount']) ? $mTax[0]['price']['amount'] : 0;
                    }
                }
                else $mPrice = isset($mTmp['amount']) ? $mTmp['amount'] : 0;
                break;
            }
        }
        
        return (object)array(
                'id'         => "shipping",
                'name'       => "Shipping Rate",
                'sku'        => "",
                'price'   => dibs_pw_api::api_dibs_round($mPrice) / 10000,
                'qty'        => 1,
                'tax'    => dibs_pw_api::api_dibs_round($mVat) / 10000
        );
    }
    
    /**
     * Build CMS customer addresses to API object.
     * 
     * @param mixed $mOrderInfo
     * @return object 
     */
    function helper_dibs_obj_addr($mOrderInfo) {
        if(empty($mOrderInfo['billing']['first_name']) && empty($mOrderInfo['billing']['last_name'])
                || !empty($mOrderInfo['billing']['name_line'])) {
            dibs_pw_helpers_cms::cms_dibs_processFullName($mOrderInfo, "billing");
        }
        
        if(empty($mOrderInfo['shipping']['first_name']) && empty($mOrderInfo['shipping']['last_name'])
                || !empty($mOrderInfo['shipping']['name_line'])) {
            dibs_pw_helpers_cms::cms_dibs_processFullName($mOrderInfo, "shipping");

        }

        return (object)array(
            'shippingfirstname'  => $mOrderInfo['shipping']['first_name'],
            'shippinglastname'   => $mOrderInfo['shipping']['last_name'],
            'shippingpostalcode' => $mOrderInfo['shipping']['postal_code'],
            'shippingpostalplace'=> $mOrderInfo['shipping']['locality'],
            'shippingaddress2'   => $mOrderInfo['shipping']['thoroughfare'] . " " .
                                    $mOrderInfo['shipping']['premise'],
            'shippingaddress'    => $mOrderInfo['shipping']['country'] . " " . 
                                    $mOrderInfo['shipping']['administrative_area'],
            
            'billingfirstname'   => $mOrderInfo['billing']['first_name'],
            'billinglastname'    => $mOrderInfo['billing']['last_name'],
            'billingpostalcode'  => $mOrderInfo['billing']['postal_code'],
            'billingpostalplace' => $mOrderInfo['billing']['locality'],
            'billingaddress2'    => $mOrderInfo['billing']['thoroughfare'] . " " .
                                    $mOrderInfo['billing']['premise'],
            'billingaddress'     => $mOrderInfo['billing']['country'] . " " . 
                                    $mOrderInfo['billing']['administrative_area'],
            
            'billingmobile'      => "",
            'billingemail'       => $mOrderInfo['wrapper']->mail->value()
        );
    }
    
    /**
     * Returns object with URLs needed for API, 
     * e.g.: callbackurl, acceptreturnurl, etc.
     * 
     * @param mixed $mOrderInfo
     * @return object 
     */
    function helper_dibs_obj_urls($mOrderInfo = null) {
        return (object)array(
            'acceptreturnurl' => 'cart/dibspw/success/',
            'callbackurl'     => 'cart/dibspw/callback/',
            'cancelreturnurl' => 'cart/dibspw/cancel/',
            'carturl'         => 'cart'
        );
    }
    
    /**
     * Returns object with additional information to send with payment.
     * 
     * @param mixed $mOrderInfo
     * @return object 
     */
    function helper_dibs_obj_etc($mOrderInfo) {
        return (object)array(
            'sysmod'      => 'd7dc_4_1_1',
            'callbackfix' => $this->helper_dibs_tools_url('cart/dibspw/callback/'),
        );
    }
}
?>