<?php

namespace shirase\yii2\helpers;

use yii\base\Object;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

class Models extends Object
{
    /**
     * @param ActiveRecord $baseModel
     * @param array $data
     * @param null $formName
     * @return array|null
     * @throws \yii\db\Exception
     */
    public static function load($baseModel, $data, $formName = null) {
        $scope = $formName === null ? $baseModel->formName() : $formName;
        if (isset($data[$scope])) {
            $rows = ArrayHelper::getValue($data, $scope, []);

            \shirase\helpers\ArrayHelper::normalize($rows);
            $tx = \Yii::$app->db->beginTransaction();
            $ok = true;
            foreach ($rows as $i=>$row) {
                if ($row['id']) {
                    $model = $baseModel::findOne($row['id']);
                } else {
                    $model = clone $baseModel;
                }

                $model->attributes = $row;
                $rows[$i] = $model;
                if (!$model->save()) {
                    $ok = false;
                }
            }
            if ($ok) {
                $tx->commit();
                return $rows;
            } else {
                $tx->rollBack();
            }
        }

        return null;
    }
}