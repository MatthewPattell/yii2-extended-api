<?php
/**
 * Created by PhpStorm.
 * Date: 2017-12-07
 * Time: 03:12
 */

namespace MP\ExtendedApi;

use yii\base\Event;
use yii\rest\ViewAction;

/**
 * Class    EViewAction
 * @package MP\ExtendedApi
 * @author  Yarmaliuk Mikhail
 * @version 1.0
 */
class EViewAction extends ViewAction
{
    const EVENT_RUN_VIEW_ACTION = 'runAction';

    /**
     * @inheritdoc
     */
    public function run($id)
    {
        $event = new Event(['data' => $id]);

        $this->trigger(self::EVENT_RUN_VIEW_ACTION, $event);

        if (!empty($event->data['id'])) {
            $id = $event->data['id'];
        }

        return parent::run($id);
    }
}
