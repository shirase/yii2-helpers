<?php
namespace shirase\yii2\helpers;

class FormatConverter extends \yii\helpers\FormatConverter
{
    public static function convertDatePhpToMomentJs($pattern)
    {
        $pattern = self::convertDatePhpToIcu($pattern);

        return strtr($pattern, [
            'y'=>'Y',
            'yy'=>'YY',
            'yyyy'=>'YYYY',
            'd'=>'D',
            'dd'=>'DD',
            'D'=>'DDD',
            'e'=>'d',
            'eee'=>'ddd',
            'eeee'=>'dddd',
        ]);
    }
} 