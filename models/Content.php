<?php

namespace asb\yii2\modules\content_2_170309\models;

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
        $result = parent::save($runValidation, $attributeNames);//var_dump($result);var_dump($this->errors);var_dump($this->attributes);exit;
        $id = $this->id;
        $parent = $this->parent_id;
        static::cleanCache($id, $parent);
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
        }//var_dump(static::$_nodes[$id]->attributes);
        //var_dump(static::$_nodes[$id]->title);var_dump(static::$_nodes[$id]->text); //!! for current(non-predicted) lang
        //var_dump(static::$_nodes[$id]->i18n); //!! here is array for all langs
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
    {//echo __METHOD__."($parentId)<br>";
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
            foreach ($children as $child) {//var_dump($child);
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
        $children = static::nodeChildren($this->id);//var_dump($children);exit;
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
    {//echo __METHOD__."($nodeId, $parentId)<br>";
        $nextParentId = $parentId;
        $exists = false;
        while (true) {//echo "? movedNode:$nodeId == nextParent:$nextParentId<br>";
            if ($nodeId == $nextParentId) {
                $exists = true;
                break;
            }

            $nextParentNode = static::node($nextParentId);//var_dump($nextParentNode);
            if (empty($nextParentNode)) break;

            $nextParentId = $nextParentNode['parent_id'];
         }//var_dump($exists);exit;

         return $exists;
    }

    /**
     * @param string $path
     * @return integer|false
     */
    public static function getIdBySlugPath($path)
    {//echo __METHOD__."($path)<br>";
        $path = trim($path);
        $path = trim($path, '/');
        $parts = explode('/', $path);
        $slug = $parts[count($parts)-1];
        
        $list = static::findAll(['slug' => $slug]);
        $contentId = false;
        foreach ($list as $node) {
            $nodePath = static::nodePath($node->id);//var_dump($nodePath);
            if ($path == trim($nodePath, '/')) {
                $contentId = $node->id;
                break;
            }
        }//var_dump($contentId);exit;
        return $contentId;
    }

    /**
     * Get path to node in content tree.
     * @param static $node
     * @return string
     */
    public static function getNodePath($node)
    {//echo __METHOD__."({$node->id})<br>";
        $result = '';
        if ($node instanceof static) {//var_dump($node->attributes);
            $result = $node->slug;
            if (!empty($node->parent_id)) {
                $parent = static::node($node->parent_id);
                if (!empty($parent)) {
                    $result = static::getNodePath($parent) . '/' . $result;
                }
            }
        }//var_dump($result);
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

}
