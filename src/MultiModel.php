<?php

namespace shirase\yii2\helpers;

use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;

/**
 * @var array $models
 * Example:
 * $model = new MultiModel([
 *     'account' => $accountModel,
 *     'profile' => $profileModel
 * ]);
 * $model->load($_POST);
 * $model->save();
 *
 * In view:
 * $form->field($model->getModel('account'), 'username')->textInput();
 */
class MultiModel extends Model
{
    /**
     * @var string
     */
    public $db = 'db';
    /**
     * @var array
     */
    protected $models = [];

    private $loaded = null;

    /**
     * @param Model[] $models
     * @param $config
     */
    public function __construct(array $models, $config = []) {
        parent::__construct($config);
        $this->models = $models;
    }

    /**
     * @param $key
     * @param Model $model
     * @return Model
     */
    public function setModel($key, Model $model)
    {
        return $this->models[$key] = $model;
    }

    /**
     * @param array $models
     */
    public function setModels(array $models)
    {
        foreach ($models as $key => $model) {
            $this->setModel($key, $model);
        }
    }

    /**
     * @param $key
     * @return Model|null
     */
    public function getModel($key)
    {
        return ArrayHelper::getValue($this->models, $key, false);
    }

    /**
     * @return array
     */
    public function getModels()
    {
        return $this->models;
    }

    /**
     * @param array $data
     * @param boolean $skipNull
     * @return bool
     */
    public function load($data, $skipNull = false)
    {
        if ($skipNull) {
            $this->loaded = [];
        }

        foreach ($this->models as $k => &$model) {
            if ($model->load($data)) {
                $this->loaded[] = $model;
            }
        }

        if (!$skipNull) {
            if (sizeof($this->loaded) != sizeof($this->models))
                return false;
        } else {
            if (!$this->loaded)
                return false;
        }

        return true;
    }

    /**
     * @param null $attributeNames
     * @param bool $clearErrors
     * @return bool
     */
    public function validate($attributeNames = null, $clearErrors = true)
    {
        $this->trigger(Model::EVENT_BEFORE_VALIDATE);
        $success = true;
        foreach ($this->loaded!==null ? $this->loaded : $this->models as $key => $model) {
            /* @var $model Model */
            if (!$model->validate()) {
                $success = false;
                $this->addErrors([$key => $model->getErrors()]);
            }
        }
        $this->trigger(Model::EVENT_AFTER_VALIDATE);
        return $success;
    }

    public function getFirstErrors()
    {
        foreach ($this->loaded!==null ? $this->loaded : $this->models as $key => $model) {
            if ($errors = $model->getFirstErrors())
                return $errors;
        }
        return [];
    }

    /**
     * @param bool $runValidation
     * @return bool
     * @throws \yii\db\Exception
     */
    public function save($runValidation = true)
    {
        if ($runValidation && !$this->validate()) {
            return false;
        }
        $success = true;
        $transaction = $this->getDb()->beginTransaction();
        foreach ($this->loaded!==null ? $this->loaded : $this->models as $model) {
            $success = $model->save(false);
            if (!$success) {
                $transaction->rollBack();
                return false;
            }
        }
        $transaction->commit();
        return $success;
    }

    /**
     * @return \yii\db\Connection
     */
    public function getDb()
    {
        return Yii::$app->get($this->db);
    }
}
