<?php
namespace shirase\yii2\helpers;

class ArrayHelper extends \yii\helpers\ArrayHelper
{
    public static function normalize(&$data) {
        if(is_array($data) && sizeof($data)) {
            list(, $val) = each($data);
            if(is_array($val) && array_key_exists(0, $val)) {
                $m = array();
                foreach(array_keys($data) as $field) {
                    foreach($data[$field] as $i=>$val) {
                        $m[$i][$field] = $val;
                    }
                }
                $data = $m;
            } else {
                foreach(array_keys($data) as $key) {
                    self::normalize($data[$key]);
                }
                if (is_array($data) && !array_key_exists(0, $data)) {
                    self::normalize($data);
                }
            }
        }
    }
}