# yii2-extended-api
Package extends the capabilities of standard classes Yii2 API

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist matthew-p/yii2-extended-api "*"
```

or add

```
"matthew-p/yii2-extended-api": "*"
```

to the require section of your `composer.json` file.

Usage
-----

Once the extension is installed, simply use it in your code by:

REST Controller for ExampleProduct model:
```php
use MP\ExtendedApi\EActiveController;

class ProductsController extends EActiveController
{
    /**
     * @var string
     */
    public $modelClass = ExampleProduct::class;

    /**
     * @var string
     */
    public $searchClass = ExampleProductSearch::class;

    /**
     * @var bool
     */
    public $errorFilter = true;
    
    /**
     * @var array
     */
    public $externalActions = [
        'delete-all' => true,
    ];

    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {
        return array_merge(parent::behaviors(), [
            'authenticator' => [
                'class' => HttpBearerAuth::class,
            ],
            'access'        => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => [User::ROLE_API],
                    ],
                ],
            ],
        ]);
    }

    /**
     * @inheritdoc
     * @throws NotFoundHttpException
     */
    public function filterError()
    {
        throw new NotFoundHttpException(Yii::t('app', 'Product not found'), self::FILTER_ERROR_CODE);
    }
}
```

REST Search model for ExampleProduct:
```php
use MP\ExtendedApi\ModelSearchInterface;

class ExampleProductSearch extends ExampleProduct implements ModelSearchInterface
{
    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        // model rules ...
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search(array $params = []): ActiveDataProvider
    {
        $dataProvider = $this->getDataProvider();

        $query = $dataProvider->query;

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions

        return $dataProvider;
    }

    /**
     * @inheritdoc
     */
    public function getDataProvider(): ActiveDataProvider
    {
        return new ActiveDataProvider([
            'query'      => self::find(),
            'pagination' => [
                'defaultPageSize' => 20,
                'pageSizeLimit'   => [
                    0, 20, 50, 100,
                ],
            ],
        ]);
    }
}
```

Features:
 - Delete all models action (support filtering, add headers)
 - Filtering via custom data provider
 - Custom error if filter result empty
 - View action trigger
 ```php
 // In controller
 $this->action->on(EViewAction::EVENT_RUN_VIEW_ACTION, function (Event $event) use ($action) {
     // you code...
 });
 ```
 - Edit filter params
 ```php
 //  public function beforeAction($action) in controller
 if ($action instanceof EIndexAction) {
     $filterParams = $action->getFilterParams();

     $filterParams['active'] = ExampleProduct::STATUS_ACTIVE;

     $action->setFilterParams($filterParams);
 }
 ``` 
 - After prepare data provider trigger
 - Get deleted model for `delete` action
 ```php
 // public function afterAction($action, $result)
 if ($action instanceof EDeleteAction) {
     $model = $action->getModel();
 }
 ```
 
That's all. Check it.
