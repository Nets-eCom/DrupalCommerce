<?php
class dibs_pw_helpers_cms {   
    
    private static $aLocalSettings = array();
    
    function cms_dibs_get_textArray() {
        return array(
            'dibspw_lang_err_fatal'  => t('A fatal error has occured.'), 
            'dibspw_lang_msg_toshop' => t('Return to shop'), 
            'dibspw_lang_err_2'     => t('Unknown orderid was returned from DIBS payment gateway!'), 
            'dibspw_lang_err_1'     => t('No orderid was returned from DIBS payment gateway!'), 
            'dibspw_lang_err_4'     => t('The amount received from DIBS payment gateway 
                              differs from original order amount!'), 
            'dibspw_lang_err_3'     => t('No amount was returned from DIBS payment gateway!'), 
            'dibspw_lang_err_6'     => t('The currency type received from DIBS payment gateway 
                                   differs from original order currency type!'),
            'dibspw_lang_err_5'     => t('No currency type was returned from DIBS payment 
                               gateway!'), 
            'dibspw_lang_err_7'     => t('The fingerprint key does not match!'), 
       );
    }
    
    public static function cms_dibs_getSettings($sProperty) {
        return isset(self::$aLocalSettings[$sProperty]) ? self::$aLocalSettings[$sProperty] : "";
    }
    
    public static function cms_dibs_setSettings($aSettings) {
        self::$aLocalSettings = $aSettings;
    }
    
    public static function cms_dibs_processFullName(&$mOrderInfo, $sType) {
        $iMatches = preg_match("/^([^\s]*)(\s.*)?$/is", $mOrderInfo[$sType]['name_line'], $aName);

        if(!empty($iMatches) && count($aName) > 2) {
            $mOrderInfo[$sType]['first_name'] = $aName[1];
            $mOrderInfo[$sType]['last_name'] = $aName[2];
        }
        else {
            $mOrderInfo[$sType]['first_name'] = $mOrderInfo[$sType]['name_line'];
            $mOrderInfo[$sType]['last_name'] = "";
        }
    }
    
    public static function cms_dibs_gotoCart($sText, $sType = "error") {
        drupal_set_message($sText, $sType); 
        drupal_goto(url('cart', array('absolute' => TRUE)));
    }
}
?>