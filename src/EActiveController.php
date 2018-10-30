<?php
/**
 * Created by PhpStorm.
 * Date: 2017-12-07
 * Time: 03:18
 */

namespace MP\ExtendedApi;

use Yii;
use yii\data\DataProviderInterface;
use yii\db\ActiveRecordInterface;
use yii\rest\ActiveController;
use yii\web\NotFoundHttpException;
use MP\Services\ImplementServices;
use yii\web\ForbiddenHttpException;

/**
 * Class    EActiveController
 * @package MP\ExtendedApi
 * @author  Yarmaliuk Mikhail
 * @version 1.0
 */
class EActiveController extends ActiveController
{
    use ImplementServices;

    const FILTER_ERROR_CODE = 405;

    /**
     * Search model class
     *
     * @var ActiveRecordInterface
     */
    public $searchClass;

    /**
     * Return error if empty filtered result
     *
     * @var bool
     */
    public $errorFilter = false;

    /**
     * List external actions
     *
     * 'delete-all' => true,
     *
     * @var array
     */
    public $externalActions = [];

    /**
     * Check action access
     *
     * 'index'  => 'rule',
     * 'update' => 'permission',
     *
     * @var array
     */
    public $checkAccessRules = [];

    /**
     * @inheritdoc
     */
    public function actions(): array
    {
        $actions = parent::actions();

        $actions['index']['class']  = EIndexAction::class;
        $actions['delete']['class'] = EDeleteAction::class;
        $actions['view']['class']   = EViewAction::class;

        if (!empty($this->searchClass)) {
            $actions['index']['dataFilter'] = [
                'class'       => EActiveDataFilter::class,
                'searchModel' => $this->searchClass,
            ];
        }

        foreach ($this->externalActions as $externalAction => $value) {
            if ($value) {
                switch ($externalAction) {
                    case 'delete-all':
                        $actions[$externalAction]          = $actions['index'];
                        $actions[$externalAction]['class'] = EDeleteAllAction::class;
                    break;
                }
            }
        }

        return $actions;
    }

    /**
     * Throw error empty filtered result
     *
     * @throws NotFoundHttpException
     */
    public function filterError(): void
    {
        throw new NotFoundHttpException(Yii::t('app', 'Nothing found'), self::FILTER_ERROR_CODE);
    }

    /**
     * @inheritdoc
     *
     * @param EIndexAction $action
     * @param mixed        $result
     *
     * @throws NotFoundHttpException
     */
    public function afterAction($action, $result)
    {
        if ($action->id === 'index' && $result instanceof DataProviderInterface) {
            if ($this->errorFilter && !empty($action->dataFilter->filter) && empty($result->getModels())) {
                $this->filterError();
            }
        }

        return parent::afterAction($action, $result);
    }

    /**
     * @inheritdoc
     */
    public function checkAccess($action, $model = null, $params = [])
    {
        if ($this->checkAccessRules[$action] ?? null) {
            $allow = Yii::$app->user->can($this->checkAccessRules[$action], ['model' => $model, 'params' => $params]);

            if (!$allow) {
                $this->forbidden();
            }
        }
    }

    /**
     * Throw forbidden error
     *
     * @throws ForbiddenHttpException
     */
    protected function forbidden(): void
    {
        throw new ForbiddenHttpException(Yii::t('app', 'You are not allowed to perform this action.'));
    }
}
