<?php

namespace shirase\yii2\helpers;

use yii\base\Model;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * Class Models
 * @package shirase\yii2\helpers
 * @property Model[]|ActiveRecord[] $models
 */
class Models extends Model implements \IteratorAggregate
{
    public function getIterator() {
        return new \ArrayIterator($this->models);
    }

    /**
     * @var string
     */
    public $db = 'db';

    /**
     * @var ActiveQuery
     */
    public $query;

    /**
     * @var Model|ActiveRecord
     */
    private $model;

    /**
     * @var Model[]|ActiveRecord[]
     */
    private $models = [];

    /**
     * @param Model[]|ActiveRecord[] $models
     */
    public function setModels($models) {
        $this->models = $models;
    }

    /**
     * @return \yii\base\Model[]|\yii\db\ActiveRecord[]
     */
    public function getModels() {
        return $this->models;
    }

    /**
     * @param Model|ActiveRecord $model Base model
     * @param $config
     */
    public function __construct($model, $config = []) {
        parent::__construct($config);
        $this->model = $model;
    }

    /**
     * @param array $data
     * @param string $formName
     * @return bool
     */
    public function load($data, $formName = null)
    {
        /**
         * @var Model|ActiveRecord $baseModel
         */
        $baseModel = $this->model;
        $scope = $formName === null ? $this->formName() : $formName;
        if (isset($data[$scope])) {
            $rows = ArrayHelper::getValue($data, $scope, []);
            ArrayHelper::normalize($rows);

            if (is_array($rows)) {
                foreach ($rows as $i=>$row) {
                    if ($baseModel instanceof ActiveRecord && $pk = $row[$baseModel->primaryKey()[0]]) {
                        if ($this->query) {
                            $query = clone($this->query);
                            $model = $query->andWhere([$baseModel::tableName() .'.[['.$baseModel->primaryKey()[0].']]' => $pk])->one();
                        } else {
                            $model = $baseModel::findOne($pk);
                        }
                        if (!$model) continue;

                        $model->attributes = $row;

                        $pkName = $baseModel->primaryKey()[0];
                        $found = false;
                        foreach ($this->models as $i=>$m) {
                            if ($m->{$pkName} && $m->{$pkName}==$model->{$pkName}) {
                                $found = true;
                                $this->models[$i] = $model;
                                break;
                            }
                        }
                        if (!$found) {
                            $this->models[] = $model;
                        }
                    } else {
                        $model = clone $baseModel;
                        $model->attributes = $row;
                        $this->models[] = $model;
                    }
                }
            }

            return true;
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

    public function getErrors($attribute = null) {
        $errors = [];
        foreach ($this->models as $model) {
            if ($err = $model->getErrors($attribute)) {
                $errors[] = $err;
            }
        }
        return $errors ?: null;
    }

    public function getFirstError($attribute) {
        $errors = [];
        foreach ($this->models as $model) {
            if ($err = $model->getErrors($attribute)) {
                $errors[] = reset($err);
            }
        }
        return $errors ?: null;
    }

    public function hasErrors($attribute = null) {
        foreach ($this->models as $model) {
            if ($model->hasErrors($attribute)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return string
     */
    public function formName() {
        return $this->model->formName();
    }

    /**
     * @return \yii\db\Connection
     */
    public function getDb()
    {
        return \Yii::$app->get($this->db);
    }
}