<?php
namespace App\Controller;

use App\Controller\AppController;

use Cake\Event\Event; // added.
use Exception; // added.

class AuctionController extends AuctionBaseController
{
	// デフォルトテーブルを使わない
	public $useTable = false;

	// 初期化処理
	public function initialize()
	{
		parent::initialize();
		$this->loadComponent('Paginator');
		// 必要なモデルをすべてロード
		$this->loadModel('Users');
		$this->loadModel('Biditems');
		$this->loadModel('Bidrequests');
		$this->loadModel('Bidinfo');
		$this->loadModel('Bidmessages');
		$this->loadModel('Deliveries');
		$this->loadModel('Ratings');
		// ログインしているユーザー情報をauthuserに設定
		$this->set('authuser', $this->Auth->user());
		// レイアウトをauctionに変更
		$this->viewBuilder()->setLayout('auction');
	}

	// 認証を使わないページの設定
	public function beforeFilter(Event $event)
	{
		parent::beforeFilter($event);
		$this->Auth->allow(['index', 'view', 'rating']);
	}

	// トップページ
	public function index()
	{
		// ページネーションでBiditemsを取得
		$auction = $this->paginate('Biditems', [
			'order' =>['endtime'=>'desc'], 
			'limit' => 10]);
		$this->set(compact('auction'));
	}

	// 商品情報の表示
	public function view($id = null)
	{
		// $idのBiditemを取得
		$biditem = $this->Biditems->get($id, [
			'contain' => ['Users', 'Bidinfo', 'Bidinfo.Users']
		]);
		// オークション終了時の処理
		if ($biditem->endtime < new \DateTime('now') and $biditem->finished == 0) {
			// finishedを1に変更して保存
			$biditem->finished = 1;
			$this->Biditems->save($biditem);
			// Bidinfoを作成する
			$bidinfo = $this->Bidinfo->newEntity();
			// Bidinfoのbiditem_idに$idを設定
			$bidinfo->biditem_id = $id;
			// 最高金額のBidrequestを検索
			$bidrequest = $this->Bidrequests->find('all', [
				'conditions'=>['biditem_id'=>$id], 
				'contain' => ['Users'],
				'order'=>['price'=>'desc']])->first();
			// Bidrequestが得られた時の処理
			if (!empty($bidrequest)){
				// Bidinfoの各種プロパティを設定して保存する
				$bidinfo->user_id = $bidrequest->user->id;
				$bidinfo->user = $bidrequest->user;
				$bidinfo->price = $bidrequest->price;
				$this->Bidinfo->save($bidinfo);
			}
			// Biditemのbidinfoに$bidinfoを設定
			$biditem->bidinfo = $bidinfo;		
		} elseif ($biditem->finished == 0){
			$endtime = (array)$biditem->endtime;

			$date = new \DateTime($endtime['date']);
			$current  = new \DateTime('now');
			
			$count = $current->diff($date);
			$countdown = $endtime['date'];

			$this->set('countdown', $countdown);
		}
		// Bidrequestsからbiditem_idが$idのものを取得
		$bidrequests = $this->Bidrequests->find('all', [
			'conditions'=>['biditem_id'=>$id], 
			'contain' => ['Users'],
			'order'=>['price'=>'desc']])->toArray();
		// オブジェクト類をテンプレート用に設定
		$this->set(compact('biditem', 'bidrequests'));
	}

	// 出品する処理
	public function add()
	{
		// Biditemインスタンスを用意
		$biditem = $this->Biditems->newEntity();
		// POST送信時の処理
		if ($this->request->is('post')) {
			// $biditemにフォームの送信内容を反映
			$biditem = $this->Biditems->patchEntity($biditem, $this->request->getData());
			$image_file = $this->request->getData('image');
			
			// 画像であるか確認
			$path_info = pathinfo($image_file['name']);
			switch($path_info['extension']) {
				case "gif":
				case "jpeg":
				case "jpg":
				case "png":
				case "GIF":
				case "JPEG":
				case "JPG":
				case "PNG":
					break;
				default:
					$this->Flash->error(__('画像を選択してください。画像は拡張子がgif, jpeg, jpg または png のいずれかになります。'));
					return $this->redirect(['action' => 'add']);
					break;
			}

			// 画像をアップロード
			$image_name = date("YmdHis") . $image_file['name'];

			$image_path = WWW_ROOT . 'img/auction/' . $image_name;

			move_uploaded_file($image_file['tmp_name'], $image_path);

			$biditem['image_path'] = $image_name;

			// $biditemを保存する
			if ($this->Biditems->save($biditem)) {
				// 成功時のメッセージ
				$this->Flash->success(__('保存しました。'));
				// トップページ（index）に移動
				return $this->redirect(['action' => 'index']);
			}
			// 失敗時のメッセージ
			$this->Flash->error(__('保存に失敗しました。もう一度入力下さい。'));
		}
		// 値を保管
		$this->set(compact('biditem'));
	}

	// 商品の発送先を出品者に通知するする処理
	public function contact($biditem_id = null)
	{
		$biditem = $this->Biditems->findById($biditem_id)->toArray();
		$bidinfo = $this->Bidinfo->findByBiditem_id($biditem_id)->toArray();
		$delivery_findby_bid = $this->Deliveries->findByBiditem_id($biditem_id)->toArray();

		// 出品者と落札者だけがアクセスできる
		$seller_id = $biditem[0]['user_id'];
		$buyer_id = $bidinfo[0]['user_id'];
		if (!($seller_id === $this->Auth->user('id')) && !($buyer_id === $this->Auth->user('id'))){
			exit('当事者以外はご利用できません。');
		}

		$ratings_findby_bid = $this->Ratings->find('all')->where(['biditem_id'=>$biditem_id])
			->andWhere(['from_user_id'=>$this->Auth->user('id')])->toArray();

		// 落札者は商品の配送先を送信したか
		$is_address_sent = false;
		// 出品者は商品は発送したか
		$is_shipped = false;
		// 落札者は発送された商品を受け取ったか
		$is_received = false;
		// 評価済みか
		$is_rated = false;

		// 配送先住所が出品者に送信済みの場合
		if(!empty($delivery_findby_bid)) {
			$is_address_sent = true;
			// 商品が発送されている場合
			if($delivery_findby_bid[0]['is_shipped'] !== 0) {
				$is_shipped = true;
			// 商品が発送されていない場合	
			} else {
				$this->set(compact('delivery_findby_bid'));
			}
			// 商品が受領されている場合
			if($delivery_findby_bid[0]['is_received'] !== 0) {
				$is_received = true;
			}
		}
		// 評価済みの場合
		if (!empty($ratings_findby_bid)) {
			if($ratings_findby_bid[0]['from_user_id'] === $this->Auth->user('id')) {
				$is_rated = true;
			}
		}

		$bidinfo_find_bid = $this->Bidinfo->find('all')->where(['biditem_id'=>$biditem_id])->toArray();
		$delivery = $this->Deliveries->newEntity();
		$rating = $this->Ratings->newEntity();

		// user_idが同じなら落札者、異なるなら出品者
		// 落札者の場合
		if ($bidinfo_find_bid[0]['user_id'] === $this->Auth->user('id')) {
			$is_buyer = true;
			
			// POST送信だったら
			if ($this->request->is('post')) {
				/* ここから ***** 落札者が出品者を評価するフォーム ***** */
				$form_num = $this->request->getData('form_num');
				if($form_num === '1') {
					$delivery = $this->Deliveries->patchEntity($delivery, $this->request->getData());

					if ($this->Deliveries->save($delivery)) {
						$this->Flash->success(__('配送先を送信しました'));
						return $this->redirect(['action' => 'contact/'.$biditem_id]);
					}
					// 失敗時
					$this->Flash->error(__('配送先の送信に失敗しました。もう一度入力してください。'));

				// 落札者
				} elseif($form_num === '2') {
					$rating = $this->Ratings->patchEntity($rating, $this->request->getData());
					//var_dump($rating);

					if ($this->Ratings->saveOrFail($rating)) {
						$this->Flash->success(__('評価を送信しました'));
						return $this->redirect(['action' => 'contact/'.$biditem_id]);
					}
					// 失敗時
					$this->Flash->error(__('出品者の評価の送信に失敗しました。もう一度入力してください。'));
				}
			}
		// 出品者の場合
		} else {
			$is_buyer = false;
			if ($this->request->is('post')) {

				$rating = $this->Ratings->patchEntity($rating, $this->request->getData());

				if ($this->Ratings->save($rating)) {
					$this->Flash->success(__('評価を送信しました'));
					return $this->redirect(['action' => 'contact/'.$biditem_id]);
				}
				// 失敗時
				$this->Flash->error(__('bbbbbbbb送信に失敗しました。もう一度入力してください。'));
			}
		}

		$this->set(compact('bidinfo', 'biditem', 'is_buyer', 'is_address_sent', 'is_shipped', 'is_received', 'is_rated'));
		$this->set(compact('delivery', 'rating'));
	}

	public function rating($user_id = null)
	{
		$user = $this->Users->findById($user_id)->toArray();

		$user_ratings = $this->Ratings->find('all')->Where(['to_user_id'=>$user_id])->toArray();

		// 評価されていない場合
		if (empty($user_ratings)) {
			exit('評価はありません');
		}

		$stars = array_column($user_ratings, 'stars');

		$star_sum = array_sum($stars);
		$star_count = count($stars);
		$star_score = $star_sum / $star_count;

		// 
		$ratings = $this->paginate('Ratings', [
			'conditions'=>['Ratings.to_user_id'=>$user_id],
			'contain' => ['Users', 'Biditems'],
			'order'=>['created'=>'desc'],
			'limit' => 10])->toArray();
		$this->set(compact('ratings'));

		$this->set(compact('user', 'star_score', 'ratings'));
	}

	public function updateIsShipped()
    {
		if ($this->request->is('post')) {
			$biditem_id = $this->request->query('biditem_id');
			$is_updated = $this->Deliveries->updateAll(['is_shipped' => '1'], ['biditem_id' => $biditem_id]);
			if ($is_updated) {
				$this->Flash->success(__('送信しました'));
				return $this->redirect(['action' => 'contact/'.$biditem_id]);
			}
			// 失敗時
			$this->Flash->error(__('送信に失敗しました。もう一度入力してください。'));
		}
	}
	
	public function updateIsReceived()
    {
		if ($this->request->is('post')) {
			$biditem_id = $this->request->query('biditem_id');
			$is_updated = $this->Deliveries->updateAll(['is_received' => '1'], ['biditem_id' => $biditem_id]);
			if ($is_updated) {
				$this->Flash->success(__('送信しました'));
				return $this->redirect(['action' => 'contact/'.$biditem_id]);
			}
			// 失敗時
			$this->Flash->error(__('送信に失敗しました。もう一度入力してください。'));
		}
    }

	// 入札の処理
	public function bid($biditem_id = null)
	{
		// 入札用のBidrequestインスタンスを用意
		$bidrequest = $this->Bidrequests->newEntity();
		// $bidrequestにbiditem_idとuser_idを設定
		$bidrequest->biditem_id = $biditem_id;
		$bidrequest->user_id = $this->Auth->user('id');
		// POST送信時の処理
		if ($this->request->is('post')) {
			// $bidrequestに送信フォームの内容を反映する
			$bidrequest = $this->Bidrequests->patchEntity($bidrequest, $this->request->getData());
			// Bidrequestを保存
			if ($this->Bidrequests->save($bidrequest)) {
				// 成功時のメッセージ
				$this->Flash->success(__('入札を送信しました。'));
				// トップページにリダイレクト
				return $this->redirect(['action'=>'view', $biditem_id]);
			}
			// 失敗時のメッセージ
			$this->Flash->error(__('入札に失敗しました。もう一度入力下さい。'));
		}
		// $biditem_idの$biditemを取得する
		$biditem = $this->Biditems->get($biditem_id);
		$this->set(compact('bidrequest', 'biditem'));
	}
	
	// 落札者とのメッセージ
	public function msg($bidinfo_id = null)
	{
		// Bidmessageを新たに用意
		$bidmsg = $this->Bidmessages->newEntity();
		// POST送信時の処理
		if ($this->request->is('post')) {
			// 送信されたフォームで$bidmsgを更新
			$bidmsg = $this->Bidmessages->patchEntity($bidmsg, $this->request->getData());
			// Bidmessageを保存
			if ($this->Bidmessages->save($bidmsg)) {
				$this->Flash->success(__('保存しました。'));
			} else {
				$this->Flash->error(__('保存に失敗しました。もう一度入力下さい。'));
			}
		}
		try { // $bidinfo_idからBidinfoを取得する
			$bidinfo = $this->Bidinfo->get($bidinfo_id, ['contain'=>['Biditems']]);
		} catch(Exception $e){
			$bidinfo = null;
		}
		// Bidmessageをbidinfo_idとuser_idで検索
		$bidmsgs = $this->Bidmessages->find('all',[
			'conditions'=>['bidinfo_id'=>$bidinfo_id],
			'contain' => ['Users'],
			'order'=>['created'=>'desc']]);
		$this->set(compact('bidmsgs', 'bidinfo', 'bidmsg'));
	}

	// 落札情報の表示
	public function home()
	{
		// 自分が落札したBidinfoをページネーションで取得
		$bidinfo = $this->paginate('Bidinfo', [
			'conditions'=>['Bidinfo.user_id'=>$this->Auth->user('id')], 
			'contain' => ['Users', 'Biditems'],
			'order'=>['created'=>'desc'],
			'limit' => 10])->toArray();
		$this->set(compact('bidinfo'));
	}

	// 出品情報の表示
	public function home2()
	{
		// 自分が出品したBiditemをページネーションで取得
		$biditems = $this->paginate('Biditems', [
			'conditions'=>['Biditems.user_id'=>$this->Auth->user('id')], 
			'contain' => ['Users', 'Bidinfo'],
			'order'=>['created'=>'desc'],
			'limit' => 10])->toArray();
		$this->set(compact('biditems'));
	}
}
