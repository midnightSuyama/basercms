<?php
/**
 * baserCMS :  Based Website Development Project <http://basercms.net>
 * Copyright (c) baserCMS Users Community <http://basercms.net/community/>
 *
 * @copyright		Copyright (c) baserCMS Users Community
 * @link			http://basercms.net baserCMS Project
 * @package			Uploader.View
 * @since			baserCMS v 3.0.10
 * @license			http://basercms.net/license/index.html
 */

/**
 * @var \BcAppView $this
 */
$this->BcListTable->setColumnNumber(4);
?>


<?php $this->BcBaser->element('pagination') ?>

<table cellpadding="0" cellspacing="0" class="list-table sort-table" id="ListTable">
	<thead>
		<tr>
			<th class="list-tool">
				<div>
					<?php $this->BcBaser->link($this->BcBaser->getImg('admin/btn_add.png', ['alt' => __d('baser', '新規追加')]) . __d('baser', '新規追加'), ['action' => 'add']) ?>　
				</div>
				<?php if($this->BcBaser->isAdminUser()): ?>
					<div>
						<?php echo $this->BcForm->checkbox('ListTool.checkall', array('title' => __d('baser', '一括選択'))) ?>
						<?php echo $this->BcForm->input('ListTool.batch', array('type' => 'select', 'options' => array('del' => __d('baser', '削除')), 'empty' => __d('baser', '一括処理'))) ?>
						<?php echo $this->BcForm->button(__d('baser', '適用'), array('id' => 'BtnApplyBatch', 'disabled' => 'disabled', 'class' => 'bca-btn')) ?>
					</div>
				<?php endif ?>
			</th>
			<th style="white-space: nowrap">
				<?php echo $this->Paginator->sort('id', array(
					'asc' => $this->BcBaser->getImg('admin/blt_list_down.png', array('alt' => __d('baser', '昇順'), 'title' => __d('baser', '昇順'))) .' NO',
					'desc' => $this->BcBaser->getImg('admin/blt_list_up.png', array('alt' => __d('baser', '降順'), 'title' => __d('baser', '降順'))) .' NO'), array('escape' => false, 'class' => 'btn-direction')) ?>
			</th>
			<th style="white-space: nowrap">
				<?php echo $this->Paginator->sort('name', array(
					'asc' => $this->BcBaser->getImg('admin/blt_list_down.png', array('alt' => __d('baser', '昇順'), 'title' => __d('baser', '昇順'))) . ' ' . __d('baser', 'カテゴリ名'),
					'desc' => $this->BcBaser->getImg('admin/blt_list_up.png', array('alt' => __d('baser', '降順'), 'title' => __d('baser', '降順'))) . ' ' . __d('baser', 'カテゴリ名')), array('escape' => false, 'class' => 'btn-direction')) ?>
			</th>
			<?php echo $this->BcListTable->dispatchShowHead() ?>
			<th style="white-space: nowrap">
				<?php echo $this->Paginator->sort('created', array(
					'asc' => $this->BcBaser->getImg('admin/blt_list_down.png', array('alt' => __d('baser', '昇順'), 'title' => __d('baser', '昇順'))) . ' ' . __d('baser', '登録日'),
					'desc' => $this->BcBaser->getImg('admin/blt_list_up.png', array('alt' => __d('baser', '降順'), 'title' => __d('baser', '降順'))) . ' ' . __d('baser', '登録日')), array('escape' => false, 'class' => 'btn-direction')) ?>
				<br />
				<?php echo $this->Paginator->sort('modified', array(
					'asc' => $this->BcBaser->getImg('admin/blt_list_down.png', array('alt' => __d('baser', '昇順'), 'title' => __d('baser', '昇順'))) . ' ' . __d('baser', '更新日'),
					'desc' => $this->BcBaser->getImg('admin/blt_list_up.png', array('alt' => __d('baser', '降順'), 'title' => __d('baser', '降順'))) . ' ' . __d('baser', '更新日')), array('escape' => false, 'class' => 'btn-direction')) ?>
			</th>
		</tr>
	</thead>
	<tbody>
		<?php if(!empty($datas)): ?>
			<?php foreach($datas as $data): ?>
				<?php $this->BcBaser->element('uploader_categories/index_row', array('data' => $data)) ?>
			<?php endforeach; ?>
		<?php else: ?>
		<tr>
			<td colspan="<?php echo $this->BcListTable->getColumnNumber() ?>"><p class="no-data"><?php echo __d('baser', 'データが見つかりませんでした。')?></p></td>
		</tr>
		<?php endif; ?>
	</tbody>
</table>

<!-- list-num -->
<?php $this->BcBaser->element('list_num') ?>
