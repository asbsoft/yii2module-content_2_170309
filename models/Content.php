<?php

namespace asb\yii2\modules\content_2_170309\models;

use asb\yii2\modules\content_2_170309\Module;
use asb\yii2\common_2_170212\web\RoutesBuilder;

use Yii;
use yii\helpers\Url;

/**
 * Caching and additional method for processing content tree.
 * @author Alexandr Belogolovsky <ab2014box@gmail.com>
 */
class Content extends ContentBase
{
    /**
     * Clean cache for node with $id and it parent(s) children list.
     */
    public static function cleanCache($id, $parentOld)
    {
        unset(static::$_children[$parentOld]);
        unset(static::$_nodes[$id]);
        unset(static::$_i18n[$id]);

        $node = static::node($id);
        if (!empty($node)) {
            unset(static::$_children[$node->parent_id]); // after update node can get new parent
        }
    }
    /**
     * @inheritdoc
     */
    public function delete()
    {
        $id = $this->id;
        $parent = $this->parent_id;
        $result = parent::delete();
        static::cleanCache($id, $parent);
        return $result;
    }
    /**
     * @inheritdoc
     */
    public function save($runValidation = true, $attributeNames = null)
    {
        $result = parent::save($runValidation, $attributeNames);
        if ($result && !empty($this->id)) {
            static::cleanCache($this->id, $this->parent_id); // clear cache only on success change data in db
        }
        return $result;
    }

    /** Nodes info: id => obj, Use in node(), nodesTreeList() */
    protected static $_nodes = [];
    /** Quick get content node without i18n */
    public static function node($id)
    {
        $id = intval($id);
        if (empty($id)) return null;
        
        if (!static::$caching || empty(static::$_nodes[$id])) {
            $result = static::findOne($id);
            if (static::$caching) {
                static::$_nodes[$id] = $result;
            } else {
                return $result;
            }
        }
        return static::$_nodes[$id];
    }
    /** Save node path */
    public static function saveNodePath($id, $nodePath)
    {
        $node = static::node($id);
        $node->nodePath = $nodePath;
    }
    /** Get node's path collected from slugs */
    public static function nodePath($id)
    {
        $node = static::node($id);
        if (empty($node)) return '';

        if (empty($node->nodePath)) {
            $node->nodePath = static::nodePath($node->parent_id) . '/' . $node->slug;
        }
        return $node->nodePath;
    }

    /** Nodes in format id => array of children array-infos */
    protected static $_children = [];
    /** Get node children */
    public static function nodeChildren($parentId)
    {
        $parentId = intval($parentId);
        if (!static::$caching || empty(static::$_children[$parentId])) {
            $query = static::find()
                ->where(['parent_id' => $parentId])
                ->orderBy(static::$defaultOrderBy);
            $childList = $query
              //->asArray()
                ->all();
            if (static::$caching) {
                static::$_children[$parentId] = $childList;
            } else {
                return $childList;
            }
        }
        return static::$_children[$parentId];
    }

    protected static $_nodesTree = [];
    protected static $_nodesTreeList = [];
    /**
     * Get nodes tree list in format for dropdown box: id => name with shift according to level.
     * @param $parent = 0,  = [], $shift
     * @param integer $level nested level
     * @param string $shiftPrefix name shift prefix
     * @return array
     */
    public static function nodesTreeList($parentId = 0, $level = 0, $shiftPrefix = '. ')
    {
        if (!isset(static::$_nodesTreeList[$parentId][$level][$shiftPrefix])) {
            $tree = [];
            $list = [];

            $parentNode = static::node($parentId);
            if (!empty($parentNode)) {
                $grandParentId = $parentNode['parent_id'];
                $grandParentNode = static::node($grandParentId);
                $nodePath = (empty($grandParentNode->nodePath)
                             ? '' : $grandParentNode->nodePath . '/'
                         ) . $parentNode['slug'];
                $list[$parentId] = str_repeat($shiftPrefix, $level) . $nodePath;
                $tree[$parentId] = str_repeat($shiftPrefix, $level) . $parentNode['slug'];
                static::saveNodePath($parentId, $nodePath);
            }

            $children = static::nodeChildren($parentId);
            foreach ($children as $child) {
                static::$_nodes[$child['id']] = $child;
                $list += static::nodesTreeList($child->id, $level+1, $shiftPrefix);
                $tree += static::nodesTree($child->id, $level+1, $shiftPrefix);
            }
            static::$_nodesTree[$parentId][$level][$shiftPrefix] = $tree;
            static::$_nodesTreeList[$parentId][$level][$shiftPrefix] = $list;
        }    
        return static::$_nodesTreeList[$parentId][$level][$shiftPrefix];
    }
    /**
     * @see nodesTreeList()
     * Same as nodesTreeList() but return slugs as nodes name instead of full path.
     */
    public static function nodesTree($parentId = 0, $level = 0, $shiftPrefix = '. ')
    {
        if (!isset(static::$_nodesTree[$parentId][$level][$shiftPrefix])) {
            static::nodesTreeList($parentId, $level, $shiftPrefix);
        }
        return static::$_nodesTree[$parentId][$level][$shiftPrefix];
    }

    /**
     * @return boolean true if this node has at least one child
     */
    public function hasChildren()
    {
        $children = static::nodeChildren($this->id);
        return count($children) > 0 ? true : false;
    }

    /**
     * @return boolean true if this node has at least one unvisible parent
     */
    public function hasInvisibleParent()
    {
        $hasInvisible = false;
        $id = $this->parent_id;
        while ($id != 0) {
            $node = static::node($id);
            if (empty($node)) break;

            if (!$node->is_visible) {
                $hasInvisible = true;
                break;
            }
            $id = $node->parent_id;
        }
        return $hasInvisible;
    }

    /**
     * Return true if $nodeId exists in parents chain from $parentId.
     * @param integer $nodeId
     * @param integer $parentId
     * @return boolean
     */
    public static function existsInParentsChain($nodeId, $parentId)
    {
        $nextParentId = $parentId;
        $exists = false;
        while (true) {
            if ($nodeId == $nextParentId) {
                $exists = true;
                break;
            }

            $nextParentNode = static::node($nextParentId);
            if (empty($nextParentNode)) break;

            $nextParentId = $nextParentNode['parent_id'];
         }

         return $exists;
    }

    /**
     * @param string $path
     * @return integer|false
     */
    public static function getIdBySlugPath($path)
    {
        $path = trim($path);
        $path = trim($path, '/');
        $parts = explode('/', $path);
        $slug = $parts[count($parts)-1];
        
        $list = static::findAll(['slug' => $slug]);
        $contentId = false;
        foreach ($list as $node) {
            $nodePath = static::nodePath($node->id);
            if ($path == trim($nodePath, '/')) {
                $contentId = $node->id;
                break;
            }
        }
        return $contentId;
    }

    /**
     * Get path to node in content tree.
     * @param static $node
     * @return string
     */
    public static function getNodePath($node)
    {
        $result = '';
        if ($node instanceof static) {
            $result = $node->slug;
            if (!empty($node->parent_id)) {
                $parent = static::node($node->parent_id);
                if (!empty($parent)) {
                    $result = static::getNodePath($parent) . '/' . $result;
                }
            }
        }
        return $result;
    }
    
    protected static $_language;
    /**
     * Get normalized system language.
     */
    public static function language()
    {
        if (empty(static::$_language)) {
            $module = Module::getModuleByClassname(Module::className());
            $langHelper = $module->langHelper;
            static::$_language = $langHelper::normalizeLangCode(Yii::$app->language);
        }
        return static::$_language;
    }

    /**
     * Check if node's link belongs to route of exists module but not to this module ('main/view' action)
     * @param static $node
     * @return array|false found module's info
     */
    public static function checkModuleLink($node)
    {
        $module = Module::getModuleByClassname(Module::className());
        $langHelper = $module->langHelper;

        $contentAction = "{$module->uniqueId}/main/view"; // route to reject

        $nodeLink = static::getNodePath($node);

        $moduleInfo = false;
        
        // find such route 
        $resultRule = false;
        foreach (Yii::$app->urlManager->rules as $nextRule) {
            if (RoutesBuilder::properRule($nextRule, $nodeLink)) {
                if (isset($nextRule->route) && $nextRule->route == $contentAction) {
                    continue;
                }
                $resultRule = $nextRule;
                break;
            }
        }
        $moduleInfo = false;
        if ($resultRule) {
            if (isset($resultRule->route)) {
                $route = $resultRule->route;
            } else if (isset($resultRule->rules[0])) { // yii\web\GroupUrlRule
                $route = $resultRule->rules[count($resultRule->rules) - 1]->route; // get latest route from group
            } else {
                $route = false; // unsupported route type
            }
            $hrefs = false; // unknown link to module's backend 
            if ($route) {
                $hrefs = [];
                foreach ($langHelper::activeLanguages($all = true) as $langCode => $lang) {
                    $hrefs[$langCode] = Url::toRoute(["/{$route}", 'lang' => $langCode]);
                }
            }
            $moduleInfo = [
                'text' => Yii::t($module->tcModule, 'module'),
                'hrefs' => $hrefs,
            ];
        }
        return $moduleInfo;
    }

}
