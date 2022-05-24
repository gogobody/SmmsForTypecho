<?php
include_once 'common.php';
include_once 'header.php';
include_once 'menu.php';

include_once 'language.php';
$tdb = Typecho_Db::get();
global $language;
$request = Typecho_Request::getInstance();
if (!defined('MY_NEW_TABLE_NAME')) {
    define('MY_NEW_TABLE_NAME', 'smms_image_list');
}
if (!defined('SMMS_URL')) {
    define('SMMS_URL', Helper::options()->pluginUrl . '/SmmsForTypecho/');  //返回当前插件的目录URI,
}


if (@$_POST['action'] == 'delete' || @$_POST['action2'] == 'delete') {
    $options = Helper::options();
    $plugin_config = $options->plugin('SmmsForTypecho'); // 获取 后台设置
    $auth = $plugin_config->Authorization_;
    $SourceImg_ = $plugin_config->SourceImg_;
    $hello_name = $plugin_config->hello_name;
    $hello_pswd = $plugin_config->hello_pswd;

    $smapi = new SMApi($auth,$hello_name,$hello_pswd,$SourceImg_);
    if (array_key_exists('imglist',$_POST)){
        foreach ($_POST['imglist'] as $v) {
            $row = $tdb->fetchRow($tdb->select()->from('table.'.MY_NEW_TABLE_NAME)->where('hash = ?', $v));
            $delete = $tdb->delete('table.' . MY_NEW_TABLE_NAME)->where('hash = ?', $v);
            $deletedRows = $tdb->query($delete);
            if ($SourceImg_ ==1){
                $smapi->Delete($v);
            }
            // delete local file here
            $options = Helper::options();
            $rooturl = $options->rootUrl;
            $plugin_config = $options->plugin('SmmsForTypecho'); // 获取 后台设置
            // 是否只上传到本地
            // 返回相对存储路径
            $localOnly = $plugin_config->localOnly;
            if ($localOnly && substr($row['url'], 0, strlen($rooturl)) === $rooturl){
                $res_path = substr($row['url'], strlen($rooturl));
                unlink(__TYPECHO_ROOT_DIR__ . $res_path);
            }
        }
    }

}
$pages = (array_key_exists('paged', $_GET) and $_GET['paged'])? : 1;
$limit = 10;
$offset = ($pages - 1) * 10;
$query = $tdb->select()->from('table.' . MY_NEW_TABLE_NAME)->order('id', Typecho_Db::SORT_DESC)->offset($offset)->limit($limit);
$rs = $tdb->fetchAll($query);
$count_S = $tdb->select('COUNT(*)')->from('table.' . MY_NEW_TABLE_NAME);
$count = $tdb->fetchAll($count_S);
$t = 'COUNT(*)';
$count = $count[0][$t];

$all_pages = (int)($count / 10) + 1;

?>
<div class="main">
    <div class="body container">
        <?php include_once 'page-title.php'; ?>
        <div class="row typecho-page-main manage-metas">
            <div class="col-mb-12">
                <ul class="typecho-option-tabs clearfix">
                    <li><a href="https://github.com/gogobody/SmmsForTypecho" title="查看友情链接使用帮助"
                           target="_blank"><?php _e('帮助'); ?></a></li>
                </ul>
            </div>

            <div class="col-mb-12" role="main">
                <h1 class="typecho-heading-inline">
                    图片库
                </h1>
                <?php echo outHtml();?>
                <?php if (@$_POST['action'] == 'delete' || @$_POST['action2'] == 'delete') { ?>
                    <div id="message" class=" message updated notice is-dismissible">
                        <p>
                            已删除
                        </p>
<!--                        <button type="button" class="notice-dismiss"><span class="screen-reader-text">忽略此通知</span></button>-->
                    </div>
                <?php } ?>
                <!-- typecho-list-operate -->
                <div class="typecho-list-operate clearfix">
                    <form method="get">
                        <div class="operate">
                            <label><i class="sr-only"><?php _e('全选'); ?></i><input type="checkbox" class="typecho-table-select-all" /></label>
                            <div class="btn-group btn-drop">
                                <button class="btn dropdown-toggle btn-s" type="button"><i class="sr-only"><?php _e('操作'); ?></i><?php _e('选中项'); ?> <i class="i-caret-down"></i></button>
                                <ul class="dropdown-menu">
                                    <li><a lang="<?php _e('你确认要删除这些吗?'); ?>" href="#" onclick="submitF(event)"><?php _e('删除'); ?></a></li>
                                </ul>
                            </div>
                        </div>
<!--//todo:-->
<!--                        <div class="search" role="search">-->
<!--                            --><?php //if ('' != $request->keywords): ?>
<!--                                <a href="--><?php //$options->adminUrl('manage-pages.php'); ?><!--">--><?php //_e('&laquo; 取消筛选'); ?><!--</a>-->
<!--                            --><?php //endif; ?>
<!--                            <input type="text" class="text-s" placeholder="--><?php //_e('请输入关键字'); ?><!--" value="--><?php //echo htmlspecialchars($request->keywords); ?><!--" name="keywords" />-->
<!--                            <button type="submit" class="btn btn-s">--><?php //_e('筛选'); ?><!--</button>-->
<!--                        </div>-->
                    </form>
                </div><!-- end .typecho-list-operate -->

                <form id="manage-gallery" method="post">
                    <div class="tablenav top">


                        <input type="hidden" id="action" name="action" class="button action"
                               value="-1"/>

                        <div class='tablenav-pages one-page'><span class="displaying-num"><?php if ($count) echo $count; else echo '0'; ?>
                                个项目</span></div>
                        <br class="clear"/>
                    </div>
                    <h2 class='screen-reader-text'>
                        列表
                    </h2>
                    <div class="typecho-table-wrap">

                        <table class="typecho-list-table widefat striped users">
                        <thead>
                        <tr>
                            <th scope="col" id='cb' class='manage-column column-typecho check-column'>
                            </th>
                            <th scope="col" class='manage-column column-primary sortable desc'>
                                <span>图片</span>
                            </th>
                            <th scope="col" class='manage-column column-parent'>
                                尺寸
                            </th>
                            <th scope="col" class='manage-column sortable desc'><span>大小</span></th>
                            <th scope="col" class='manage-column'>URL</th>
                            <th scope="col" class='manage-column'>HASH</th>
                        </tr>
                        </thead>

                        <tbody id="the-list" data-typecho-lists='list:user'>
                        <?php if (count($rs) > 0 ):?>
                        <?php foreach ($rs as $res) { ?>
                            <tr>
                                <th scope='row' class='check-column'><input type="checkbox" name="imglist[]" id="user_1"
                                                                            class="administrator"
                                                                            value="<?php echo $res['hash']; ?>"/></th>
                                <td class='username column-username has-row-actions column-primary' data-colname="图片">
                                    <img alt=''
                                         src='<?php echo $res['url']; ?>'
                                         class='avatar avatar-32 photo' height='100' width='100'/></td>
                                <td><?php echo $res['width']; ?> * <?php echo $res['height']; ?>
                                </td>
                                <td><?php $size = $res['size'];
                                    echo $size > 1048576 ? $size / 1048576 . 'mb' : $size / 1024 . 'kb'; ?>
                                </td>
                                <td><?php echo $res['url']; ?>
                                </td>
                                <td><?php echo $res['hash']; ?>
                                </td>
                            </tr>
                        <?php } ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6"><h6 class="typecho-list-table-title"><?php _e('没有任何内容'); ?></h6></td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                    </div>

                </form>

                <div class="typecho-list-operate clearfix">
                    <form method="get">
                        <div class="operate">
                            <label><i class="sr-only"><?php _e('全选'); ?></i><input type="checkbox" class="typecho-table-select-all" /></label>
                            <div class="btn-group btn-drop">
                                <button class="btn dropdown-toggle btn-s" type="button"><i class="sr-only"><?php _e('操作'); ?></i><?php _e('选中项'); ?> <i class="i-caret-down"></i></button>
                                <ul class="dropdown-menu">
                                    <li><a lang="<?php _e('你确认要删除这些吗?'); ?>" href="#" onclick="submitF(event)"><?php _e('删除'); ?></a></li>
                                </ul>
                            </div>
                        </div>
                        <div class="" style="float: right">
                            <div class="tablenav-pages"><span class="displaying-num"><?php echo $count; ?>个项目</span>
                                <a target="_self" <?php if ($pages != 1) { ?>href="?panel=SmmsForTypecho%2Fmanage.php&paged=<?php echo($pages - 1); ?>"<?php } ?> target="_self">
                            <span class="tablenav-pages-navspan button <?php if ($pages == 1) { ?>disabled<?php } ?>" aria-hidden="true">上一页</span></a>
                                        <span class="screen-reader-text">当前页</span>
                                <span id="table-paging" class="paging-input">
                                    <span class="tablenav-paging-text">第<?php echo $pages; ?>页，共<span class="total-pages"><?php echo $all_pages; ?></span>页</span></span>
                                        <a target="_self" class="next-page button <?php if ($pages == $all_pages) { ?>disabled<?php } ?>"
                                           <?php if ($pages != $all_pages) { ?>href="?panel=SmmsForTypecho%2Fmanage.php&paged=<?php echo($pages + 1); ?>"<?php } ?>>
                                            <span aria-hidden="true">下一页</span>
                                        </a>
                                </span>
                                    </div>

                            <br class="clear"/>
                        </div>

                    </form>
                </div><!-- end .typecho-list-operate -->
                <br class="clear"/>
            </div>
        </div>
    </div>
</div>
<div id="background" class="background" style="display: none; "></div>
<div id="progressBar" class="progressBar" style="display: none; ">请稍等...</div>
<?php
include_once 'copyright.php';
include_once 'common-js.php';
include_once 'table-js.php';
?>
<script>
    function submitF(e){
        let form_ = $("#manage-gallery");
        let action = $("#action")
        action.val('delete')
        form_.submit();
        var ajaxbg = $("#background,#progressBar");
        ajaxbg.show()
        return false
    }
</script>
<?php
echo '<script>smms_url="'.Helper::options()->index.'";</script>';

echo '<script src="'. SMMS_URL . 'js/content.js'. '"></script>';
echo '<script src="'. SMMS_URL . 'js/modal.min.js'. '"></script>';
?>

<?php include_once 'footer.php'; ?>
