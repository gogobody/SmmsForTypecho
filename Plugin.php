<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * SmmsForTypecho_Plugin 是 typecho 的图床插件，支持 sm.ms 图床和 helloimg.com 图床
 *
 * 
 * @package SmmsForTypecho
 * @author gogobody
 * @version 4.5
 * @link https://github.com/gogobody/SmmsForTypecho
 */
//设置语言
include_once 'language.php';
include_once 'smms.function.php';
include_once 'smapi.php';

define('SMMS_URL', Helper::options()->pluginUrl . '/SmmsForTypecho/');  //返回当前插件的目录URI,
define('SMMS_VERSION', "4.5");
function outHtml(){
    $html_ = '<div class="admin-img-manager"><div class="admin-manage-img ui_button"><div class="button" id="toggleModal" >Typecho图床插件</div></div><div class="admin-upload-img"><label class="ui_button ui_button_primary" for="admin-img-file">上传文件</label><form><input id="admin-img-file" type="file" accept="image/*" multiple="multiple"></form></div><div class="modal">' .
        '<div class="modal-header">'.
        '<p class="close">×</p></div><div class="modal-content"><ul id="img_list"></ul><span id="pages-list"></span></div>'.
        '<div class="modal-footer"><input id="upload-btn" type="button" class="load btn" value="加载更多"><input type="button" class="close btn" value="关闭">'.
        '</div></div><div class="mask"></div></div>';
    return $html_;
}
define('UPLOAD_DIR' , '/usr/uploads');

class SmmsForTypecho_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        // 创建 文件夹
        $tp_uploads = Typecho_Common::url(defined('__TYPECHO_UPLOAD_DIR__') ? __TYPECHO_UPLOAD_DIR__ : UPLOAD_DIR,
            defined('__TYPECHO_UPLOAD_ROOT_DIR__') ? __TYPECHO_UPLOAD_ROOT_DIR__ : __TYPECHO_ROOT_DIR__);
        if(!is_dir($tp_uploads)){
            $res = @mkdir($tp_uploads,0777,true);
            if(!$res){
                $error = error_get_last();

                throw new Typecho_Plugin_Exception('创建文件夹失败请手动创建,并给权限0777：'.$tp_uploads);
            }
        }

        Typecho_Plugin::factory('admin/header.php')->header = array('SmmsForTypecho_Plugin', 'admin_scripts_css');
        Typecho_Plugin::factory('admin/write-post.php')->bottom = array('SmmsForTypecho_Plugin', 'admin_writepost_scripts');

//        Typecho_Plugin::factory('Widget_Archive')->beforeRender_1001 = array('SmmsForTypecho_Plugin','Widget_Archive_beforeRender');
//
//        Typecho_Plugin::factory('Widget_Archive')->afterRender_1001 = array('SmmsForTypecho_Plugin','Widget_Archive_afterRender');

        plugin_activation_cretable();
        Helper::addAction('multi-upload', 'SmmsForTypecho_Action');

        //add panel
        Helper::addPanel(3, 'SmmsForTypecho/manage.php', 'SMMS图床', '管理SMMS图床', 'administrator'); //editor //contributor

        //
        Typecho_Plugin::factory('SmmsForTypecho')->header = array('SmmsForTypecho_Plugin','Widget_Archive_beforeRender');
        Typecho_Plugin::factory('SmmsForTypecho')->footer = array('SmmsForTypecho_Plugin','Widget_Archive_afterRender');

    }
    
    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate(){
        plugin_deactivation_deltable();
        Helper::removePanel(3, 'SmmsForTypecho/manage.php');
        Helper::removeAction('multi-upload');
    }
    
    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        /** 分类名称 */
        global $language;

        $Authorization = new Typecho_Widget_Helper_Form_Element_Text('Authorization_', NULL, null,'Authorization'.$language[2], _t('<a target="_blank" href="https://sm.ms/home/apitoken">Authorization '.$language[6].'</a><div class="">
                <ul class="typecho-option-tabs clearfix">
                    <li><a href="https://github.com/gogobody/SmmsForTypecho" title="查看友情链接使用帮助"
                           target="_blank">插件帮助文档</a></li>
                </ul>
            </div><br>'));
        $form->addInput($Authorization);

        $Comment_Selector = new Typecho_Widget_Helper_Form_Element_Text('Comment_Selector', NULL, '#textarea','评论选择器', _t('因为不同的主题的评论框是不同主题开发者自定义的，所以需要手动定位<a target="_blank" href="">点击这里查看</a>'));
        $form->addInput($Comment_Selector);

        $SourceImg = new Typecho_Widget_Helper_Form_Element_Radio('SourceImg_', array(
            1 => _t('SM.MS'),
            0 => _t('helloimg.com'),
        ), 1, _t('选择图床源'), _t('选择图床源'));
        $form->addInput($SourceImg);

        $hello_name = new Typecho_Widget_Helper_Form_Element_Text('hello_name', NULL, '','hello图床用户名', _t('hello图床用户名（不用hello图床不用填）'));
        $form->addInput($hello_name);

        $hello_pswd = new Typecho_Widget_Helper_Form_Element_Text('hello_pswd', NULL, '','hello图床密码', _t('hello图床密码（不用hello图床不用填）'));
        $form->addInput($hello_pswd);

        $Content_ = new Typecho_Widget_Helper_Form_Element_Radio('Content_', array(
            1 => _t('启用'),
            0 => _t('关闭'),
        ), 1, _t('后台文章编辑启用图片上传'), _t('勾选后在后台文章编辑处自动添加图片上传按钮'));
        $form->addInput($Content_);

        $Comment_ = new Typecho_Widget_Helper_Form_Element_Radio('Comment_', array(
            1 => _t('启用'),
            0 => _t('关闭'),
        ), 1, _t('是否启用评论上传按钮'), _t('勾选后在评论框后自动添加图片上传按钮'));
        $form->addInput($Comment_);

        // 是否保留本地文件，这个选项表示还是会上传到sm ，但是不会保留本地副本
        $Nolocal_ = new Typecho_Widget_Helper_Form_Element_Radio('Nolocal_', array(
            1 => _t('启用'),
            0 => _t('关闭'),
        ), 1, _t('删除本地文件'), _t('勾选后不保留本地上传文件'));
        $form->addInput($Nolocal_);

        // 只上传到本地，不上传到SM
        $localOnly = new Typecho_Widget_Helper_Form_Element_Radio('localOnly', array(
            1 => _t('启用'),
            0 => _t('关闭'),
        ), 1, "只上传到本地", "启用后文件只会上传到本地不会上传到sm（上面选项将失效）");
        $form->addInput($localOnly);
    }
    
    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}
    
    public static function add_scripts_css(){
        echo '<link rel="stylesheet" href="'.SMMS_URL . 'css/smms.diy.min.css'.'" type="text/css"/>';
        echo '<script>smms_url="'.Helper::options()->index.'";</script>';
        echo '<script src="'. SMMS_URL . 'js/jquery.min.js'. '"></script>';
        echo '<script src="'. SMMS_URL . 'js/comment.min.js'. '"></script>';

    }
    public static function admin_scripts_css($header){

        if (Typecho_Widget::widget('Widget_User')->hasLogin()) {
//            echo $header;
            echo '<link rel="stylesheet" href="'. SMMS_URL . 'css/input.min.css'.'" type="text/css"/><link rel="stylesheet" href="'. SMMS_URL . 'css/modal.css'.'" type="text/css"/>'.
                '<link rel="stylesheet" href="'.SMMS_URL . 'css/smms.diy.min.css'.'" type="text/css"/>';;
//            echo '<link rel="stylesheet" href="'. SMMS_URL . 'css/input.min.css'.'" type="text/css"/>';
//            echo '<link rel="stylesheet" href="'. SMMS_URL . 'css/modal.css'.'" type="text/css"/>';
        }
        return $header;
    }

    public static function admin_writepost_scripts($post){
        $option = Helper::options()->plugin('SmmsForTypecho');
        if (!$option->Content_){
            return;
        }
        if (Typecho_Widget::widget('Widget_User')->hasLogin()) {
            echo '<script>smms_url="'.Helper::options()->index.'";</script>';
            echo '<script src="'. SMMS_URL . 'js/content.min.js'. '"></script>';
            echo '<script src="'. SMMS_URL . 'js/modal.min.js'. '"></script>';

            ?>
            <script>
                let tmpHtml = '<?php echo outHtml();?>'
                $("#text").parent().append(tmpHtml);
            </script>
            <?php
        }

    }
    public static function checkOnecircleTheme($archive){
        $option = Helper::options()->plugin('SmmsForTypecho');
        if(Helper::options()->theme != 'onecircle'){ // this only for onecircle theme, to display in all pages
            if (!$option->Comment_){
                return false;
            }
            if (!$archive->is('single')) {
                return false;
            }
            if (!$archive->allow('comment')) {
                return false;
            }
        }else{
            if ($archive->is('neighbor') or $archive->is('metas')) return false;
        }
        return true;
    }

    public static function Widget_Archive_beforeRender($archive)
    {
        if (!self::checkOnecircleTheme($archive)) return;

        echo '<link rel="stylesheet" href="'.SMMS_URL . 'css/smms.diy.min.css'.'" type="text/css"/>';

    }

    public static function Widget_Archive_afterRender($archive)
    {
        if (!self::checkOnecircleTheme($archive)) return;
        $option = Helper::options()->plugin('SmmsForTypecho');
        echo '<script>smms_url="'.Helper::options()->index.'";comment_selector_="'.$option->Comment_Selector.'";</script>';
        ?>
        <script>smms_node={init:function(){var a='<div id="zz-img-show"></div><div class="zz-add-img "><input id="zz-img-file" type="file" accept="image/*" multiple="multiple"><button id="zz-img-add" type="button"><span class="chevereto-pup-button-icon"><svg class="chevereto-pup-button-icon" xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><path d="M76.7 87.5c12.8 0 23.3-13.3 23.3-29.4 0-13.6-5.2-25.7-15.4-27.5 0 0-3.5-0.7-5.6 1.7 0 0 0.6 9.4-2.9 12.6 0 0 8.7-32.4-23.7-32.4 -29.3 0-22.5 34.5-22.5 34.5 -5-6.4-0.6-19.6-0.6-19.6 -2.5-2.6-6.1-2.5-6.1-2.5C10.9 25 0 39.1 0 54.6c0 15.5 9.3 32.7 29.3 32.7 2 0 6.4 0 11.7 0V68.5h-13l22-22 22 22H59v18.8C68.6 87.4 76.7 87.5 76.7 87.5z" style="fill: currentcolor;"></path></svg></span><span class="chevereto-pup-button-text">上传</span></button></div>';if(typeof comment_selector_!="undefined"){$(comment_selector_).after(a)}}};smms_node.init();</script>
        <?php
        echo '<script src="'. SMMS_URL . 'js/comment.min.js'. '"></script>';


    }
}
