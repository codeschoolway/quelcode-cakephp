<h2>ユーザー：<?=$authuser['username'] ?></h2>

<!-- ここから ***** 落札者のステータスに対応した表示 ***** -->
<?php if ($is_buyer): ?>
<!-- ここから ***** 落札者の配送先入力フォーム ***** -->
    <?php if (!$is_address_sent): ?>
        <h3>※商品の配送先を入力</h3>
        <?= $this->Form->create($delivery) ?>
        <fieldset>
            <?php
                echo $this->Form->hidden('biditem_id', ['value' => $bidinfo[0]["biditem_id"]]);
                echo $this->Form->hidden('user_id', ['value' => $authuser['id']]);
                echo $this->Form->hidden('form_num', ['value' => 1]);
                echo $this->Form->control('name');
                echo $this->Form->control('address');
                echo $this->Form->control('phone');
                echo $this->Form->hidden('is_shipped', ['value' => 0]);
                echo $this->Form->hidden('is_received', ['value' => 0]);
            ?>
        </fieldset>
        <?= $this->Form->button(__('Submit')) ?>
        <?= $this->Form->end() ?>
    <!-- ここまで ***** 落札者の配送先入力フォーム ***** -->

    <?php else: ?>

        <?php if (!$is_shipped): ?>
            <h3>※商品の発送待ち</h3>
            <p>配送先住所は送信されました。出品者からの商品発送の通知を待っています。</p>
        <?php else: ?>

        <?php if (!$is_received): ?>
            <h3>※商品が発送されました</h3>
            <p>商品が発送されました。商品を受け取ったら「商品を受け取りました」ボタンを押してください。</p>
            <?= $this->Form->postButton('商品を受け取りました', ['controller' => 'Auction', 'action' => 'updateIsReceived', 'biditem_id' => $bidinfo[0]["biditem_id"]]) ?>
        <?php endif; ?>
    <?php endif; ?>

<!-- ここから ***** 落札者が出品者を評価するフォーム ***** -->
    <?php if ($is_received): ?>
        <?php if ($is_rated) : ?>
        <p>完了しました。</p>
            <? else: ?>

            <p>今回の出品者を評価してください。</p>
            <?= $this->Form->create($rating) ?>
            <fieldset>
                <?php
                    echo $this->Form->hidden('from_user_id', ['value' => $authuser['id']]);
                    echo $this->Form->hidden('to_user_id', ['value' => $biditem[0]['user_id']]);
                    echo $this->Form->hidden('biditem_id', ['value' => $bidinfo[0]['biditem_id']]);
                    echo '<p><strong>５段階評価</strong></p>';
                    $options = ['5' => '5', '4' => '4', '3' => '3', '2' => '2', '1' => '1'];
                    echo $this->Form->select('stars', $options, ['empty' => true]);
                    echo '<p><strong>コメント</strong></p>';
                    echo $this->Form->control('comment', array('label' => false));
                    echo $this->Form->hidden('form_num', ['value' => 2]); 
                ?>
                </fieldset>
                <?= $this->Form->button(__('Submit')) ?>
                <?= $this->Form->end() ?>
    <!-- ここまで ***** 落札者が落札者を評価するフォーム ***** -->
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>
<?php endif; ?>
<!-- ここまで ***** 落札者のステータスに対応した表示 ***** -->

<!-- ここから ***** 出品者のステータスに対応した表示 ***** -->
<?php if (!$is_buyer): ?>
<!-- 出品者に配送先を表示する -->
    <?php if ($is_received): ?>

        <?php if ($is_rated) : ?>
        <p>完了しました。</p>
        <? else: ?>

        <p>落札者は商品を受け取りました。</p>
        <p>落札者を評価してください。</p>
        <?= $this->Form->create($rating) ?>
            <fieldset>
                <?php
                    echo $this->Form->hidden('from_user_id', ['value' => $authuser['id']]);
                    echo $this->Form->hidden('to_user_id', ['value' => $bidinfo[0]['user_id']]);
                    echo $this->Form->hidden('biditem_id', ['value' => $bidinfo[0]['biditem_id']]);
                    echo '<p><strong>５段階評価</strong></p>';
                    $options = ['5' => '5', '4' => '4', '3' => '3', '2' => '2', '1' => '1'];
                    echo $this->Form->select('stars', $options, ['empty' => true]);
                    echo '<p><strong>コメント</strong></p>';
                    echo $this->Form->control('comment', array('label' => false));
                    echo $this->Form->hidden('form_num', ['value' => 2]); 
                ?>
            </fieldset>
        <?= $this->Form->button(__('Submit')) ?>
        <?= $this->Form->end() ?>
        <?php endif; ?>
    <?php endif; ?>

<?php if ($is_address_sent): ?>
    <?php if (!$is_shipped): ?>
        <h3>※商品を発送してください</h3>
        <p>この住所に商品をお送りください。</p>
        <table>
        <tr><td>名前：<?= h($delivery_findby_bid[0]->name) ?></td></tr>
        <tr><td>住所：<?= h($delivery_findby_bid[0]->address) ?></td></tr>
        <tr><td>電話：<?= h($delivery_findby_bid[0]->phone) ?></td></tr>
        </table>
        <p>商品発送後に「商品を発送しました」ボタンを押してください。</p>
        <?= $this->Form->postButton('商品を発送しました', ['controller' => 'Auction', 'action' => 'updateIsShipped', 'biditem_id' => $bidinfo[0]["biditem_id"]]) ?>

        <?php else: ?>
            <?php if (!$is_received): ?>
            <p>落札者から商品受け取りの通知が来るまでお待ちください。</p>
            <?php endif; ?>

        <?php endif; ?>

    <?php else: ?>
    <p>落札者から配送先の通知が来るまでお待ちください。</p>
    <?php endif; ?>

<?php endif; ?>
<!-- ここまで ***** 出品者のステータスに対応した表示 ***** -->
