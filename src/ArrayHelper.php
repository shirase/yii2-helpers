<?php
namespace shirase\yii2\helpers;

class ArrayHelper extends \yii\helpers\ArrayHelper
{
    public static function normalize(&$data) {
        if(is_array($data) && sizeof($data)) {
            if(array_key_exists(0, $data) && is_array($data[0])) {
                // model[]field
                $corrected = [];
                foreach ($data as $i=>$val) {
                    list($k, $v) = each($val);
                    if (!isset($corrected[$k])) {
                        $corrected[$k] = [];
                    }
                    $corrected[$k][] = $v;
                }
                $data = $corrected;
                reset($data);
            }

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
                // name[model][field][]
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