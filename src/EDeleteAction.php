<?php
/**
 * Created by PhpStorm.
 * User: Yarmaliuk Mikhail
 * Date: 20.12.2017
 * Time: 23:50
 */

namespace MP\ExtendedApi;

use Yii;
use yii\db\ActiveRecord;
use yii\rest\DeleteAction;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

/**
 * Class    EDeleteAction
 * @package MP\ExtendedApi
 * @author  Yarmaliuk Mikhail
 * @version 1.0
 */
class EDeleteAction extends DeleteAction
{
    /**
     * @var ActiveRecord|null
     */
    private ?ActiveRecord $_model;

    /**
     * Get deleted model
     *
     * @return ActiveRecord|null
     */
    public function getModel(): ?ActiveRecord
    {
        return $this->_model;
    }

    /**
     * Deletes a model.
     * @param mixed $id id of the model to be deleted.
     *
     * @throws NotFoundHttpException on failure.
     * @throws ServerErrorHttpException
     */
    public function run($id)
    {
        $model = $this->findModel($id);

        if ($this->checkAccess) {
            call_user_func($this->checkAccess, $this->id, $model);
        }

        $this->_model = $model;

        if ($model->delete() === false) {
            throw new ServerErrorHttpException('Failed to delete the object for unknown reason.');
        }

        Yii::$app->getResponse()->setStatusCode(204);
    }
}
