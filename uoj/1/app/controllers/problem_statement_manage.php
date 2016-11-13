<?php
	requirePHPLib('form');
	
	if (!validateUInt($_GET['id']) || !($problem = queryProblemBrief($_GET['id']))) {
		become404Page();
	}
	if (!hasProblemPermission($myUser, $problem)) {
		become403Page();
	}
	
	$problem_content = queryProblemContent($problem['id']);
	$problem_all_tags = queryProblemTags($problem['id']);
	$problem_tabs = array_filter($problem_all_tags, function($item) { return strpos($item, '::') !== FALSE; });
	$problem_tags = array_filter($problem_all_tags, function($item) { return strpos($item, '::') === FALSE; });
	
	$problem_editor = new UOJBlogEditor();
	$problem_editor->name = 'problem';
	$problem_editor->blog_url = "/problem/{$problem['id']}";
	$problem_editor->cur_data = array(
		'title' => $problem['title'],
		'content_md' => $problem_content['statement_md'],
		'content' => $problem_content['statement'],
		'tags' => $problem_tags,
		'is_hidden' => $problem['is_hidden']
	);
	$problem_editor->label_text = array_merge($problem_editor->label_text, array(
		'view blog' => '查看题目',
		'blog visibility' => '题目可见性'
	));
	
	$problem_editor->save = function($data) {
		global $problem, $problem_all_tags, $problem_tabs;
		DB::update("update problems set title = '".DB::escape($data['title'])."' where id = {$problem['id']}");
		DB::update("update problems_contents set statement = '".DB::escape($data['content'])."', statement_md = '".DB::escape($data['content_md'])."' where id = {$problem['id']}");

		$data_tags = array_merge($problem_tabs, $data['tags']);
		if ($data_tags !== $problem_all_tags) {
			DB::delete("delete from problems_tags where problem_id = {$problem['id']}");
			foreach ($data_tags as $tag) {
				DB::insert("insert into problems_tags (problem_id, tag) values ({$problem['id']}, '".DB::escape($tag)."')");
			}
		}
		if ($data['is_hidden'] != $problem['is_hidden'] ) {
			DB::update("update problems set is_hidden = {$data['is_hidden']} where id = {$problem['id']}");
			DB::update("update submissions set is_hidden = {$data['is_hidden']} where problem_id = {$problem['id']}");
			DB::update("update hacks set is_hidden = {$data['is_hidden']} where problem_id = {$problem['id']}");
		}
	};
	
	$problem_editor->runAtServer();
?>
<?php echoUOJPageHeader(HTML::stripTags($problem['title']) . ' - 编辑 - 题目管理') ?>
<h1 class="page-header" align="center">#<?=$problem['id']?> : <?=$problem['title']?> 管理</h1>
<ul class="nav nav-tabs" role="tablist">
	<li class="active"><a href="/problem/<?= $problem['id'] ?>/manage/statement" role="tab">编辑</a></li>
	<li><a href="/problem/<?= $problem['id'] ?>/manage/managers" role="tab">管理者</a></li>
	<li><a href="/problem/<?= $problem['id'] ?>/manage/data" role="tab">数据</a></li>
	<li><a href="/problem/<?=$problem['id']?>" role="tab">返回</a></li>
</ul>
<?php $problem_editor->printHTML() ?>
<?php echoUOJPageFooter() ?>
