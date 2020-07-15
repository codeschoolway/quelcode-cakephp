<h2><?=h($user[0]->username)?>の評価</h2>

<h3>平均点数：<?= h(number_format($star_score, 1)) ?></h3>

<table cellpadding="0" cellspacing="0">
	<thead>
		<tr>
			<th scope="col" class="actions"><?= __('商品名') ?></th>
			<th scope="col" class="actions"><?= __('コメント') ?></th>
			<th scope="col" class="actions"><?= __('点数') ?></th>
			<th scope="col" class="actions"><?= __('評価日時') ?></th>
        </tr>
	</thead>
	<tbody>
		<?php foreach ($ratings as $rating) : ?>
			<tr>

				<td class="actions">
					<?php if (!empty($rating->biditem)) : ?>
						<?= $this->Html->link(__($rating->biditem->name), ['action' => 'view', $rating->biditem->id]) ?>
					<?php endif; ?>
				</td>
				<td><?= h($rating->comment) ?></td>
				<td><?= h($rating->stars) ?></td>
				<td><?= h($rating->updated) ?></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>

<div class="paginator">
	<ul class="pagination">
		<?= $this->Paginator->first('<< ' . __('first')) ?>
		<?= $this->Paginator->prev('< ' . __('previous')) ?>
		<?= $this->Paginator->numbers() ?>
		<?= $this->Paginator->next(__('next') . ' >') ?>
		<?= $this->Paginator->last(__('last') . ' >>') ?>
	</ul>
</div>
