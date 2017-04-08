<?php

use asb\yii2\modules\content_2_170309\rbac\IsContentOwnerRule;

use yii\db\Migration;

// if problems with autoload (classes not found):
//Yii::setAlias('@asb/yii2', dirname(dirname(dirname(__DIR__))) . '/yii2-common_2_170212');
//Yii::setAlias('@asb/yii2/modules/content_2_170309', dirname(__DIR__));//var_dump(Yii::$aliases);exit;

/**
 * @author Alexandr Belogolovsky <ab2014box@gmail.com>
 */
class m170309_193703_content_roles_perms extends Migration
{
    public $roleRoot  = 'roleRoot';  // system developer
    public $roleAdmin = 'roleAdmin'; // system admin

    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
        $auth = Yii::$app->authManager;

        // rules
        $ruleIsContentOwner = new IsContentOwnerRule;
        $auth->add($ruleIsContentOwner);
        
        // roles
        $roleContentModerator = $auth->createRole('roleContentModerator');
        $auth->add($roleContentModerator);

        $roleContentAuthor = $auth->createRole('roleContentAuthor');
        $auth->add($roleContentAuthor);

        $roleAdmin = $auth->getRole($this->roleAdmin);
        $auth->addChild($roleAdmin, $roleContentModerator); // system admin is moderator too

        $roleRoot = $auth->getRole($this->roleRoot);    // system developer - need all roles by default
        $auth->addChild($roleRoot, $roleContentAuthor); // root inherit roleContentModerator from admin

        // permissions
        $createContent = $auth->createPermission('createContent');
        $createContent->description = 'Create content';
        $auth->add($createContent);

        $deleteContent = $auth->createPermission('deleteContent');
        $deleteContent->description = 'Delete content';
        $auth->add($deleteContent);

        $updateContent = $auth->createPermission('updateContent');
        $updateContent->description = 'Update content';
        $auth->add($updateContent);

        $updateOwnContent = $auth->createPermission('updateOwnContent');
        $updateOwnContent->description = 'Update own content';
        $updateOwnContent->ruleName = $ruleIsContentOwner->name;
        $auth->add($updateOwnContent);

        $auth->addChild($updateOwnContent, $updateContent); //??

        // permissions in roles
        $auth->addChild($roleContentAuthor, $createContent);
        $auth->addChild($roleContentAuthor, $updateOwnContent);
        $auth->addChild($roleContentModerator, $updateContent);
        $auth->addChild($roleContentModerator, $deleteContent);
    }

    public function safeDown()
    {
        $auth = Yii::$app->authManager;

        $roleContentModerator = $auth->getRole('roleContentModerator');
        $roleContentAuthor = $auth->getRole('roleContentAuthor');

        $createContent = $auth->getPermission('createContent');
        $deleteContent = $auth->getPermission('deleteContent');
        $updateContent = $auth->getPermission('updateContent');
        $updateOwnContent = $auth->getPermission('updateOwnContent');

        $auth->removeChild($roleContentAuthor, $createContent);
        $auth->removeChild($roleContentAuthor, $updateOwnContent);
        $auth->removeChild($roleContentModerator, $updateContent);
        $auth->removeChild($roleContentModerator, $deleteContent);

        $auth->removeChild($updateOwnContent, $updateContent); //??

        $auth->remove($createContent);
        $auth->remove($deleteContent);
        $auth->remove($updateContent);
        $auth->remove($updateOwnContent);

        $roleRoot = $auth->getRole('roleRoot');
        $roleAdmin = $auth->getRole('roleAdmin');
        $auth->removeChild($roleAdmin, $roleContentModerator);
        $auth->removeChild($roleRoot, $roleContentAuthor);

        $auth->remove($roleContentAuthor);
        $auth->remove($roleContentModerator);
        
        $ruleIsContentOwner = $auth->getRule('ruleIsContentOwner');
        $auth->remove($ruleIsContentOwner);
    }

}
