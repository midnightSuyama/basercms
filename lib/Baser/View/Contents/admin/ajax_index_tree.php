<?php
/**
 * baserCMS :  Based Website Development Project <http://basercms.net>
 * Copyright (c) baserCMS Users Community <http://basercms.net/community/>
 *
 * @copyright		Copyright (c) baserCMS Users Community
 * @link			http://basercms.net baserCMS Project
 * @package			Baser.View
 * @since			baserCMS v 4.0.0
 * @license			http://basercms.net/license/index.html
 */
header('Content-type: text/html; charset=utf-8');
?>


<?php if($datas): ?>
<div id="ContentsTreeList" style="display:none">
<?php $this->BcBaser->element('contents/index_list_tree'); ?>
</div>
<?php else: ?>
<div class="tree-empty"><?php echo __d('baser', 'データが登録されていません。') ?></div>
<?php endif ?>