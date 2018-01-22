<?php
//ru
$instruction = include __DIR__ . '/instruction.php';
return array_merge($instruction, [
    'Content manager'   => 'Менеджер контента',
    'Adminer'           => 'Админка',

// controllers
    "Node #{parent} '{toalias}' can't become parent of edited node #{id} '{slug}'"
    . " because this node #{id} already exists among relatives of #{parent} (will loop in tree)"
                        => "Узел #{parent} '{toalias}' не может стать предком редактируемого узла #{id} '{slug}',"
                         . " потому что этот узел #{id} уже есть в цепочке родственников #{parent}"
                         . " (это приведет к зацикливанию в дереве)",
    'Content not found' => 'Контент не найден',
    'Node #{id} not found'
                        => 'Узел #{id} не найден',
    "Can't swap #{id} with #{swapId}"
                        => 'Не удалось обменять #{id} с #{swapId}',
    "Can't find swap ({dir}) for #{id}"
                        => "Не удалось переместить #{id} в направлении {dir}",
    'up'                => 'вверх',
    'down'              => 'вниз',
    'You can update only your own article' => 'Ви можете редактировать только свою собственную статью',
    'You can update only your own article still unvisible'
                                        => 'Ви можете редактировать только свою собственную статью пока она не опубликована',

// admin-views
    'Contents'          => 'Контент',
    'Content #{id}'     => 'Контент #{id}',
    'Children for node' => 'Потомки узла',
    'Children for node not found'
                        => 'Потомки узла не найдены',
    'Create Content'    => 'Создать Контент',
    'Update Content'    => 'Редактировать Контент',
    'Create'            => 'Создать',
    'Update'            => 'Редактировать',
    'Save'              => 'Сохранить',
    'Save no view'      => 'Сохранить без просмотра',
    'Return to list'    => 'Вернуться к списку',
    'Delete'            => 'Удалить',
    'Are you sure you want to delete this item?'
                        => 'Вы уверены, что хотите удалить это',
    'Actions'           => 'Действия',
    'Shift down'        => 'Сдвмнуть вниз',
    'Shift up'          => 'Сдвмнуть вверх',
    'Hide'              => 'Скрыть',
    'Show'              => 'Показывать',
    'Are you sure to change visibility of this item?'
                        => 'Вы уверены что хотите изменить видимость этого узла?',
    'View'              => 'Посмотреть',
    'Edit'              => 'Редактировать',
    'Change author'     => 'изменить автора',
    'Change parent'     => 'изменить предка',
    'select'            => 'выберите',
    'all'               => 'все',
    'any'               => 'все',
    'root'              => 'корень',
    'All nodes'         => 'Все узлы',
    'Work with this node'=>'Работать с этим узлом',
    '[no title]'        => '[без заголовка]',
    'Tree is empty'     => 'Дерево пусто',
    "Moderator can't create articles"
                        => 'Модератор не может создавать статьи',
    "When create new record you can't upload images in text editor. You can do this in update mode"
 => "При создании новой записи вы не сможете загружать изображения в текстовом редакторе. Вы сможете сделать это при редактировании",
    'This language not show at frontend'
                        => 'Этот язык не отображается на frontend',
    'Content invisible at frontend'
                        => 'Страница не отображается на frontend',
    'No content to show'=> 'Нет контента для отображения',
    'For use as text block only because has invisible parent node'
                        => 'Для использования только в виде текстового блока, так как имеет невидимый родительский узел',
    '(no title)'        => '(без заголовка)',

// models
    'ID'                => 'Ид',
    'Parent'            => 'Предок',
    'Alias / URL part'  => 'Псевдоним / Часть линка',
    'Visible'           => 'Видимость',
    'Author'            => 'Автор',
    'Menu item / Title' => 'Пункт меню / Заголовок',
    'Text'              => 'Текст',
    'Only small latin letters, digits and hyphen'
                        => 'Только маленькие латинские буквы, цифры и дефис',
    'Such slug (alias) already exists for this parent'
                        => 'Эта часть линка/псевдоним уже существует для этого предка',
    "Can't delete node with children"
                        => 'Невозможно удалить узел с потомками',
    'Deletion unsuccessfull by the reason'
                        => 'Удаление не удалось по причине',
    'At least one title or text field must be fill'
                        => 'Хотя бы один заголовок или текст должен быть заполнен',
    'Saving unsuccessfull'
                        => 'Сохранение не удалось',
    'Saving unsuccessfull by the reason'
                        => 'Сохранение не удалось по причине',
    'module'            => 'модуль',

]);
