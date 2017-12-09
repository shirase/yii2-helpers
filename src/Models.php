<?php

namespace shirase\yii2\helpers;

use yii\base\Model;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Exception;

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
     * @var Model|ActiveRecord
     */
    private $model;

    /**
     * @var Model[]|ActiveRecord[]
     */
    private $models = [];

    private $notLoadedModels = [];

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
        $this->notLoadedModels = [];
        foreach ($this->models as $model) {
            $this->notLoadedModels[$model->primaryKey] = $model;
        }

        /**
         * @var Model|ActiveRecord $baseModel
         */
        $baseModel = $this->model;
        $scope = $formName === null ? $this->formName() : $formName;
        if (isset($data[$scope])) {
            $rows = ArrayHelper::getValue($data, $scope, []);
            ArrayHelper::normalize($rows);

            if (is_array($rows)) {
                foreach ($rows as $row) {
                    if ($baseModel instanceof ActiveRecord && ($pk = $row[$baseModel->primaryKey()[0]])) {
                        $pkName = $baseModel->primaryKey()[0];
                        foreach ($this->models as $model) {
                            if ($model->{$pkName} && $model->{$pkName} == $pk) {
                                $model->attributes = $row;
                                unset($this->notLoadedModels[$model->primaryKey]);
                                break;
                            }
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
     * @param bool $deleteNotLoaded
     * @return bool|null
     * @throws Exception
     * @throws \Exception
     * @throws \Throwable
     */
    public function save($runValidation = true, $attributeNames = null, $deleteNotLoaded = true) {
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
            if ($deleteNotLoaded) {
                /** @var ActiveRecord $model */
                foreach ($this->notLoadedModels as $model) {
                    if (!$model->delete())
                        throw new Exception('Delete error', $model->errors);
                }
            }
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