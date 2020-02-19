<?php
/**
 * Created by PhpStorm.
 * Date: 2017-12-07
 * Time: 03:18
 */

namespace MP\ExtendedApi;

use MP\Services\ImplementServices;
use Yii;
use yii\data\DataProviderInterface;
use yii\db\ActiveRecordInterface;
use yii\helpers\ArrayHelper;
use yii\rest\ActiveController;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

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
     * @var ActiveRecordInterface|string
     */
    public string $searchClass;

    /**
     * Return error if empty filtered result
     *
     * @var bool
     */
    public bool $errorFilter = false;

    /**
     * List external actions
     *
     * 'delete-all' => true,
     *
     * @var array
     */
    public array $externalActions = [];

    /**
     * Check action access
     *
     * 'index'  => 'rule',
     * 'update' => 'permission',
     *  and etc...
     *
     * @var array
     */
    public array $checkAccessRules = [];

    /**
     * @var array
     */
    public array $actionsParams = [];

    /**
     * @inheritdoc
     */
    public function actions(): array
    {
        $actions = ArrayHelper::merge(parent::actions(), [
            'index'  => [
                'class' => EIndexAction::class,
            ],
            'create' => [
                'class' => ECreateAction::class,
            ],
            'view'   => [
                'class' => EViewAction::class,
            ],
            'update' => [
                'class' => EUpdateAction::class,
            ],
            'delete' => [
                'class' => EDeleteAction::class,
            ],
        ]);

        $actions = ArrayHelper::merge($actions, $this->actionsParams);

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

                    case 'update-all':
                        $actions[$externalAction]          = $actions['index'];
                        $actions[$externalAction]['class'] = EUpdateAllAction::class;
                    break;
                }
            }
        }

        return $actions;
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
