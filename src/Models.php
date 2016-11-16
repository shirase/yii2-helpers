<?php

namespace shirase\yii2\helpers;

use yii\db\ActiveRecord;

class Models implements \IteratorAggregate
{
    public function getIterator() {
        return new \ArrayIterator($this->models);
    }

    /**
     * @var ActiveRecord[]
     */
    private $models;

    /**
     * @param $models ActiveRecord[]
     */
    public function __construct($models) {
        $this->models = $models;
    }

    /**
     * @param ActiveRecord $baseModel
     * @param array $data
     * @param null $formName
     * @return Models|false
     * @throws \yii\db\Exception
     */
    public static function load($baseModel, $data, $formName = null) {
        $scope = $formName === null ? $baseModel->formName() : $formName;
        if (isset($data[$scope])) {
            $rows = ArrayHelper::getValue($data, $scope, []);
            ArrayHelper::normalize($rows);

            foreach ($rows as $i=>$row) {
                if ($row['id']) {
                    $model = $baseModel::findOne($row['id']);
                } else {
                    $model = clone $baseModel;
                }

                $model->attributes = $row;
                $rows[$i] = $model;
            }

            return new Models($rows);
        }

        return false;
    }

    /**
     * @param null $attributeNames
     * @param bool|true $clearErrors
     * @return bool|null
     */
    public function validate($attributeNames = null, $clearErrors = true) {
        if ($this->models === null) {
            return null;
        }

        $valid = true;
        foreach ($this->models as $model) {
            if (!$model->validate($attributeNames, $clearErrors)) {
                $valid = false;
            }
        }
        return $valid;
    }

    /**
     * @param bool|true $runValidation
     * @param null $attributeNames
     * @return bool|null
     * @throws \yii\db\Exception
     */
    public function save($runValidation = true, $attributeNames = null) {
        if ($this->models === null) {
            return null;
        }

        $tx = \Yii::$app->db->beginTransaction();
        $valid = true;
        foreach ($this->models as $model) {
            if (!$model->save($runValidation, $attributeNames)) {
                $valid = false;
            }
        }
        if ($valid) {
            $tx->commit();
            return true;
        } else {
            $tx->rollBack();
            return false;
        }
    }
}