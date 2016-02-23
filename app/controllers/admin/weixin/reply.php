<?php
class ReplyController extends AdminController
{
	function index($ctx){
		$page = $_GET['page']? intval($_GET['page']) : 0;
		$size = $_GET['size']? intval($_GET['size']) : 10;
		$s = $_GET['s'];
		$keyword_type = $_GET['keyword_type'];
		$ctx->s = $s;
		$ctx->page = $page;
		$ctx->size = $size;
		$ctx->keyword_type = $keyword_type;

		$where = "1";
		if($s || $keyword_type){
			$s2 = Db::escape_like_string($s);
			$where .= " and id in(
				select item_id from wx_reply_keywords where 1";
			if($s){
				$where .= " and keyword like '%$s2%'";
			}
			if($keyword_type){
				$keyword_type = Db::escape($keyword_type);
				$where .= " and type='$keyword_type'";
			}
			$where .= ")";
		}
		$ds = WxReplyItem::paginate($page, $size, $where, 'id desc');
		$ctx->ds = $ds;
	}
	
	function add($ctx){
		if($_POST){
			return $this->save($ctx);
		}
		
		$ctx->channel_list = array();
		
		$m = new WxReplyItem();
		$type = $_GET['type'];
		if(!$type){
			$type = 'text';
		}
		$keyword_type = $_GET['keyword_type'];
		if(!$keyword_type){
			$keyword_type = 'equal';
		}
		if($type == 'text'){
			$m->content = WxReply::text('{$FromUserName}', '{$ToUserName}', $content);
		}else if($type == 'news'){
			$news_arr = array();
			$news_arr[] = array(
				'title' => $t,
				'text' => $d,
				'image_url' => $p,
				'link' => $u,
			);
			$m->content = WxReply::news('{$FromUserName}', '{$ToUserName}', $news_arr);
		}
		$ctx->keyword_type = $keyword_type;
		$ctx->type = $type;
		$ctx->m = $m;
		_render('form');
	}
	
	function edit($ctx){
		if($_POST){
			return $this->save($ctx);
		}
		
		$req = $_GET + $_POST;
		$id = intval($req['id']);
		$m = WxReplyItem::get($id);
		if(!$m){
			_redirect($this->_list_url());
			return;
		}

		$ctx->channel_list = Profile::list_channel_info();
		
		$keyword_type = 'equal';
		foreach($m->keywords() as $k){
			$keyword_type = $k->type;
			break;
		}
		$ctx->keyword_type = $keyword_type;
		
		#$xml = @simplexml_load_string($m->content, 'SimpleXMLElement', LIBXML_NOCDATA);
		#echo $xml->asXML();

		$ks = $m->keywords();
		$old_kws = array();
		foreach($ks as $k){
			$old_kws[] = $k->keyword;
		}
		$m->keywords = join(', ', $old_kws);
		
		$ctx->m = $m;
		_render('form');
	}
	
	function save($ctx){
		_render('form');

		$id = intval($_POST['id']);
		$m = WxReplyItem::get($id);
		if($id && !$m){
			_redirect($this->_list_url());
			return;
		}
		
		$type = $_POST['type'];
		$title = $_POST['title'];
		$desc = $_POST['desc'];
		$img_url = $_POST['img_url'];
		$link = $_POST['link'];
		$content = $_POST['content'];

		if(!$type){
			$ctx->errmsg = "空内容1.";
			return;
		}
		
		$reply_list = array();
		foreach($type as $index=>$mt){
			if($mt == 'text'){
				$c = $content[$index];
				if(!strlen($c)){
					continue;
				}
				$reply_list[] = array(
					'type' => $mt,
					'content' => $c,
				);
			}else if($mt == 'news'){
				$t = $title[$index];
				$d = $desc[$index];
				$i = $img_url[$index];
				$l = $link[$index];
				if(!strlen($t) || !strlen($i) || !strlen($l)){
					continue;
				}
				
				$channel_info = WxReplyKeyword::check_channel_subscribe_keyword($_POST['keywords']);
				if ($channel_info) {
					// 所有链接带上pcode
					$arr_query = parse_url($l);
					parse_str($arr_query['query'], $output);
					$output['pcode'] = $channel_info->promotion_code;
					$str_query = http_build_query($output);
					$l = $arr_query['scheme'] . '://' . $arr_query['host'] . $arr_query['path'] . '?' . $str_query;
				}
				
				$reply_list[] = array(
					'type' => $mt,
					'title' => $t,
					'desc' => $d,
					'img_url' => $i,
					'link' => $l,
				);
			}else{
				$ctx->errmsg = "异常错误!";
				return;
			}
		}
		if(!$reply_list){
			$ctx->errmsg = "空内容2.";
			return;
		}
		
		$content = Text::json_encode($reply_list);
		if(!$m){
			$m = WxReplyItem::save(array(
				'status' => 0,
				'type' => 'mixed',
				'content' => $content,
			));
		}else{
			$m->update(array(
				'content' => $content,
			));
			$m = WxReplyItem::get($id);
		}
				
		$keyword_type = $_POST['keyword_type'];
		$m->reset_keywords($_POST['keywords'], $keyword_type);
			
		_redirect($this->_list_url());
		return;
	}
	
	function delete($ctx){
		$id = intval($_GET['id']);
		try{
			Db::begin();
			WxReplyItem::delete($id);
			WxReplyKeyword::deleteByWhere("item_id='$id'");
			Db::commit();
		}catch(Exception $e){
			Db::rollback();
		}
		_redirect($this->_list_url());
		return;
	}
}
