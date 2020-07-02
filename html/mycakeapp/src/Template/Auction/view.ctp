<h2>「<?= $biditem->name ?>」の情報</h2>
<table class="vertical-table">
<tr>
	<th class="small" scope="row">出品者</th>
	<td><?= $biditem->has('user') ? $biditem->user->username : '' ?></td>
</tr>
<tr>
	<th scope="row">商品名</th>
	<td><?= h($biditem->name) ?></td>
</tr>
<tr>
	<th scope="row">商品ID</th>
	<td><?= $this->Number->format($biditem->id) ?></td>
</tr>
<tr>
	<th scope="row">終了時間</th>
	<td><?= h($biditem->endtime) ?></td>
</tr>
<tr>
	<th scope="row">投稿時間</th>
	<td><?= h($biditem->created) ?></td>
</tr>
<tr>
	<th scope="row"><?= __('終了した？') ?></th>
	<td><?= $biditem->finished ? __('Yes') : __('No'); ?></td>
</tr>
<tr>
	<th scope="row">商品詳細</th>
	<td><?= h($biditem->description) ?></td>
</tr>
<tr>
	<th scope="row">終了までカウントダウン</th>
	<td><span id="over">終了</span></td>
</tr>

</table>
<div class="related">
	<h4><?= __('落札情報') ?></h4>
	<?php if (!empty($biditem->bidinfo)): ?>
	<table cellpadding="0" cellspacing="0">
	<tr>
		<th scope="col">落札者</th>
		<th scope="col">落札金額</th>
		<th scope="col">落札日時</th>
	</tr>
	<tr>
		<td><?= h($biditem->bidinfo->user->username) ?></td>
		<td><?= h($biditem->bidinfo->price) ?>円</td>
		<td><?= h($biditem->endtime) ?></td>
	</tr>
	</table>
	<?php else: ?>
	<p><?='※落札情報は、ありません。' ?></p>
	<?php endif; ?>
</div>
<div class="related">
	<h4><?= __('入札情報') ?></h4>
	<?php if (!$biditem->finished): ?>
	<h6><a href="<?=$this->Url->build(['action'=>'bid', $biditem->id]) ?>">《入札する！》</a></h6>
	<?php if (!empty($bidrequests)): ?>
	<table cellpadding="0" cellspacing="0">
	<thead>
	<tr>
		<th scope="col">入札者</th>
		<th scope="col">金額</th>
		<th scope="col">入札日時</th>
	</tr>
	</thead>
	<tbody>
	<?php foreach ($bidrequests as $bidrequest): ?>
	<tr>
		<td><?= h($bidrequest->user->username) ?></td>
		<td><?= h($bidrequest->price) ?>円</td>
		<td><?=$bidrequest->created ?></td>
	</tr>
	<?php endforeach; ?>
	</tbody>
	</table>
	<?php else: ?>
	<p><?='※入札は、まだありません。' ?></p>
	<?php endif; ?>
	<?php else: ?>
	<p><?='※入札は、終了しました。' ?></p>
	<?php endif; ?>
</div>

<!-- 画像表示 -->
<?php
	$imagePath = h($biditem->image_path);
	echo $this->Html->image("auction/$imagePath");
	$jsImagePath = json_encode($imagePath);
?>

<!-- カウントダウン -->
<script type="text/javascript">

var toend = '<?php echo $countdown; ?>';
var is_finished = '<?php echo json_encode($biditem->finished); ?>';

var countdown = function(due) {
	var now = new Date();

	var rest = due.getTime() - now.getTime();
	var sec = Math.floor(rest / 1000) % 60;
	var min = Math.floor(rest / 1000 / 60) % 60;
	var hours = Math.floor(rest / 1000 / 60 / 60) % 24;
	var days = Math.floor(rest / 1000 / 60 / 60 / 24);
	var count = [days, hours, min, sec];

	return count;
}

var recalc = function() {
	var goal = new Date(toend);
	var rightnow = new Date();

	if(rightnow.getTime() >= goal.getTime()) {
		clearTimeout(counting);
		return;
	}

	var counter = countdown(goal);

	document.getElementById('over').textContent = counter[0]+'日'+counter[1]+'時'+counter[2]+'分'+counter[3]+'秒';

	var counting = setTimeout(recalc, 1000);
}
recalc();
</script>