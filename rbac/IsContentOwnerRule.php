<?php

namespace asb\yii2\modules\content_2_170309\rbac;

use asb\yii2\modules\content_2_170309\Module;

use Yii;
use yii\rbac\Rule;

/**
 * @author ASB <ab2014box@gmail.com>
 */
class IsContentOwnerRule extends Rule
{
    public $name = 'ruleIsContentOwner';

    protected $role  = 'roleContentAuthor';

    protected $group = 'authors';

    /**
     * @param string|integer $userId the user ID.
     * @param Item $item the role or permission that this rule is associated width
     * @param array $params parameters passed to ManagerInterface::checkAccess().
     * @return boolean a value indicating whether the rule permits the role or permission it is associated with.
     */
    public function execute($userId, $item, $params)
    {//echo __METHOD__."($userId)";var_dump($item);var_dump($params);
        $hasRole = Yii::$app->authManager->getAssignment($this->role, $userId);//var_dump($hasRole);exit;
        if (empty($hasRole)) return false;

        $isOwner = ($params['content']->owner_id == $userId);
        if (isset($params['canEditVisible']) && $params['canEditVisible']) { // author can edit visible article
            return isset($params['content']) ? $isOwner : false;
        } else { // author can't edit visible article:
            if (empty($params['content'])) return false;
            return $isOwner && ($params['content']->is_visible == false);
        }
    }

}
