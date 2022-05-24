<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * 上传组件
 *
 * @author qining
 * @category typecho
 * @package Widget
 */
if (!defined('MY_NEW_TABLE_NAME')){
    define('MY_NEW_TABLE_NAME', 'smms_image_list');
}
require(__DIR__ . DIRECTORY_SEPARATOR . "smapi.php");

class Upload extends Widget_Abstract_Contents implements Widget_Interface_Do
{
    //上传文件目录
    const UPLOAD_DIR = '/usr/uploads';

    /**
     * 创建上传路径
     *
     * @access private
     * @param string $path 路径
     * @return boolean
     */
    public static function makeUploadDir($path)
    {
        $path = preg_replace("/\\\+/", '/', $path);
        $current = rtrim($path, '/');
        $last = $current;

        while (!is_dir($current) && false !== strpos($path, '/')) {
            $last = $current;
            $current = dirname($current);
        }

        if ($last == $current) {
            return true;
        }

        if (!@mkdir($last,0777,true)) {
            return false;
        }

        $stat = @stat($last);
        $perms = $stat['mode'] & 0007777;
        @chmod($last, $perms);

        return self::makeUploadDir($path);
    }

    /**
     * 获取安全的文件名
     *
     * @param string $name
     * @static
     * @access private
     * @return string
     */
    public static function getSafeName(&$name)
    {
        $name = str_replace(array('"', '<', '>'), '', $name);
        $name = str_replace('\\', '/', $name);
        $name = false === strpos($name, '/') ? ('a' . $name) : str_replace('/', '/a', $name);
        $info = pathinfo($name);
        $name = substr($info['basename'], 1);

        return isset($info['extension']) ? strtolower($info['extension']) : '';
    }

    /**
     * 上传文件处理函数,如果需要实现自己的文件哈希或者特殊的文件系统,请在options表里把uploadHandle改成自己的函数
     *
     * @access public
     * @param array $file 上传的文件
     * @return mixed
     */
    public static function uploadHandle($file)
    {
        if (empty($file['name'])) {
            return false;
        } else {
//            print_r($file);
        }

        $result = Typecho_Plugin::factory('Widget_Upload')->trigger($hasUploaded)->uploadHandle($file);
        if ($hasUploaded) {
            return $result;
        }

        $ext = self::getSafeName($file['name']);

//        print_r($ext);

        if (!self::checkFileType($ext) || Typecho_Common::isAppEngine()) {
            return false;
        }

        $date = new Typecho_Date();
        $path = Typecho_Common::url(defined('__TYPECHO_UPLOAD_DIR__') ? __TYPECHO_UPLOAD_DIR__ : self::UPLOAD_DIR,
                defined('__TYPECHO_UPLOAD_ROOT_DIR__') ? __TYPECHO_UPLOAD_ROOT_DIR__ : __TYPECHO_ROOT_DIR__)
            . '/' . $date->year . '/' . $date->month;

        //创建上传目录
        if (!is_dir($path)) {
            if (!self::makeUploadDir($path)) {
                return false;
            }
        }

        //获取文件名
        $fileName = sprintf('%u', crc32(uniqid())) . '.' . $ext;
        $path = $path . '/' . $fileName;

        if (isset($file['tmp_name'])) {

            //移动上传文件
            if (!@move_uploaded_file($file['tmp_name'], $path)) {
                return false;
            }
        } else if (isset($file['bytes'])) {

            //直接写入文件
            if (!file_put_contents($path, $file['bytes'])) {
                return false;
            }
        } else {
            return false;
        }

        if (!isset($file['size'])) {
            $file['size'] = filesize($path);
        }

        //返回相对存储路径
        return array(
            'name' => $file['name'],
            'path' => (defined('__TYPECHO_UPLOAD_DIR__') ? __TYPECHO_UPLOAD_DIR__ : self::UPLOAD_DIR)
                . '/' . $date->year . '/' . $date->month . '/' . $fileName,
            'size' => $file['size'],
            'type' => $ext,
            'mime' => Typecho_Common::mimeContentType($path)
        );
    }

    /**
     * 修改文件处理函数,如果需要实现自己的文件哈希或者特殊的文件系统,请在options表里把modifyHandle改成自己的函数
     *
     * @access public
     * @param array $content 老文件
     * @param array $file 新上传的文件
     * @return mixed
     */
    public static function modifyHandle($content, $file)
    {
        if (empty($file['name'])) {
            return false;
        }

        $result = Typecho_Plugin::factory('Widget_Upload')->trigger($hasModified)->modifyHandle($content, $file);
        if ($hasModified) {
            return $result;
        }

        $ext = self::getSafeName($file['name']);

        if ($content['attachment']->type != $ext || Typecho_Common::isAppEngine()) {
            return false;
        }

        $path = Typecho_Common::url($content['attachment']->path,
            defined('__TYPECHO_UPLOAD_ROOT_DIR__') ? __TYPECHO_UPLOAD_ROOT_DIR__ : __TYPECHO_ROOT_DIR__);
        $dir = dirname($path);

        //创建上传目录
        if (!is_dir($dir)) {
            if (!self::makeUploadDir($dir)) {
                return false;
            }
        }

        if (isset($file['tmp_name'])) {

            @unlink($path);

            //移动上传文件
            if (!@move_uploaded_file($file['tmp_name'], $path)) {
                return false;
            }
        } else if (isset($file['bytes'])) {

            @unlink($path);

            //直接写入文件
            if (!file_put_contents($path, $file['bytes'])) {
                return false;
            }
        } else {
            return false;
        }

        if (!isset($file['size'])) {
            $file['size'] = filesize($path);
        }

        //返回相对存储路径
        return array(
            'name' => $content['attachment']->name,
            'path' => $content['attachment']->path,
            'size' => $file['size'],
            'type' => $content['attachment']->type,
            'mime' => $content['attachment']->mime
        );
    }

    /**
     * 删除文件
     *
     * @access public
     * @param array $content 文件相关信息
     * @return string
     */
    public static function deleteHandle(array $content)
    {
        $result = Typecho_Plugin::factory('Widget_Upload')->trigger($hasDeleted)->deleteHandle($content);
        if ($hasDeleted) {
            return $result;
        }

        return !Typecho_Common::isAppEngine()
            && @unlink(__TYPECHO_ROOT_DIR__ . '/' . $content['attachment']->path);
    }

    /**
     * 获取实际文件绝对访问路径
     *
     * @access public
     * @param array $content 文件相关信息
     * @return string
     */
    public static function attachmentHandle(array $content)
    {
        $result = Typecho_Plugin::factory('Widget_Upload')->trigger($hasPlugged)->attachmentHandle($content);
        if ($hasPlugged) {
            return $result;
        }

        $options = Typecho_Widget::widget('Widget_Options');
        return Typecho_Common::url($content['attachment']->path,
            defined('__TYPECHO_UPLOAD_URL__') ? __TYPECHO_UPLOAD_URL__ : $options->siteUrl);
    }

    /**
     * 获取实际文件数据
     *
     * @access public
     * @param array $content
     * @return string
     */
    public static function attachmentDataHandle(array $content)
    {
        $result = Typecho_Plugin::factory('Widget_Upload')->trigger($hasPlugged)->attachmentDataHandle($content);
        if ($hasPlugged) {
            return $result;
        }

        return file_get_contents(Typecho_Common::url($content['attachment']->path,
            defined('__TYPECHO_UPLOAD_ROOT_DIR__') ? __TYPECHO_UPLOAD_ROOT_DIR__ : __TYPECHO_ROOT_DIR__));
    }

    /**
     * 检查文件名
     *
     * @access private
     * @param string $ext 扩展名
     * @return boolean
     */
    public static function checkFileType($ext)
    {
        $options = Typecho_Widget::widget('Widget_Options');
        return in_array($ext, $options->allowedAttachmentTypes);
    }


    public static function getDataFromWebUrl($url)
    {
        $file_contents = "";
        if (function_exists('file_get_contents')) {
            $file_contents = @file_get_contents($url);
        }
        if ($file_contents == "") {
            $ch = curl_init();
            $timeout = 30;
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            $file_contents = curl_exec($ch);
            curl_close($ch);
        }
        return $file_contents;
    }


    /**
     * 执行升级程序
     *
     * @access public
     * @return void
     */
    public function upload()
    {

        $uploadType = "file";
        $original = "";
        if (!empty($_FILES)) {
            $file = array_pop($_FILES);
//            print_r($file);
            if (is_array($file["error"])) {//处理传过来的是一个file数组
                $file = array(
                    "name" => $file["name"][0],
                    "type" => $file["type"][0],
                    "error" => $file["error"][0],
                    "tmp_name" => $file["tmp_name"][0],
                    "size" => $file["size"][0],
                );
            }
        } else {
            $post = json_decode(file_get_contents("php://input"), true);
//            print_r($post);
            if (@!empty($post["url"])) {
                $imageUrl = $post["url"];
                $original = $imageUrl;
                if (substr($imageUrl, 0, 4) != "http") {//图片地址没有http前缀
                    if (substr($imageUrl,0,2) =="//"){//图片地址是相对路径
                        $imageUrl = "http:".$imageUrl;
                    }else{
                        $imageUrl = "";
                    }
                } else {//正确的url
//                    $imageUrl = "";
                }
//                print_r("imageUrl".$imageUrl);
                if ($imageUrl!=""){
                    $ret = parse_url($imageUrl);
//                    $url = @$ret["scheme"] . "://" . @$ret["host"] . @$ret["path"];
                    $fileName = mb_split("/", @$ret["path"]);
                    $fileName = $fileName[count($fileName) - 1];
                    $file = array(
                        "name" => $fileName,
                        "error" => 0,
                        "bytes" => self::getDataFromWebUrl($imageUrl),
                    );
                    $uploadType = "web";
//                    print_r($file);
                }

            } else {
                //不需要处理
                print_r("图片外链格式不正确");
            }
        }


        if (!empty($file)) {
            if (0 == $file['error'] && ((isset($file['tmp_name']) && is_uploaded_file($file['tmp_name'])) || isset
                    ($file["bytes"]))) {
                // xhr的send无法支持utf8
                if ($this->request->isAjax()) {
                    $file['name'] = urldecode($file['name']);
                }
                $result = self::uploadHandle($file);

                if (false !== $result) {
                    $this->pluginHandle()->beforeUpload($result);

                    $struct = array(
                        'title' => $result['name'],
                        'slug' => $result['name'],
                        'type' => 'attachment',
                        'status' => 'publish',
                        'text' => serialize($result),
                        'allowComment' => 1,
                        'allowPing' => 0,
                        'allowFeed' => 1
                    );

                    if (isset($this->request->cid)) {
                        $cid = $this->request->filter('int')->cid;

                        if ($this->isWriteable($this->db->sql()->where('cid = ?', $cid))) {
                            $struct['parent'] = $cid;
                        }
                    }

                    $insertId = $this->insert($struct);

                    $this->db->fetchRow($this->select()->where('table.contents.cid = ?', $insertId)
                        ->where('table.contents.type = ?', 'attachment'), array($this, 'push'));

                    /** 增加插件接口 */
                    $this->pluginHandle()->upload($this);


                    if ($uploadType == "file") {
                        $this->response->throwJson(array($this->attachment->url, array(
                            'cid' => $insertId,
                            'title' => $this->attachment->name,
                            'type' => $this->attachment->type,
                            'size' => $this->attachment->size,
                            'bytes' => number_format(ceil($this->attachment->size / 1024)) . ' Kb',
                            'isImage' => $this->attachment->isImage,
                            'url' => $this->attachment->url,
                            'permalink' => $this->permalink
                        )));
                    } else {
                        $this->response->throwJson(array(
                                "msg" => "",
                                "code" => 0,
                                "data" => array(
                                    'cid' => $insertId,
                                    "title" => $this->attachment->name,
                                    'type' => $this->attachment->type,
                                    'size' => $this->attachment->size,
                                    'bytes' => number_format(ceil($this->attachment->size / 1024)) . ' Kb',
                                    'isImage' => $this->attachment->isImage,
                                    "url" => $this->attachment->url,
                                    'permalink' => $this->permalink,
                                    "originalURL"=>$original,

                                )
                            )
                        );
                    }


                }
            }

        }

        $this->response->throwJson(false);
    }

    /**
     * 执行升级程序
     *
     * @access public
     * @return void
     */
    public function modify()
    {
        if (!empty($_FILES)) {
            $file = array_pop($_FILES);
            if (0 == $file['error'] && is_uploaded_file($file['tmp_name'])) {
                $this->db->fetchRow($this->select()->where('table.contents.cid = ?', $this->request->filter('int')->cid)
                    ->where('table.contents.type = ?', 'attachment'), array($this, 'push'));

                if (!$this->have()) {
                    $this->response->setStatus(404);
                    exit;
                }

                if (!$this->allow('edit')) {
                    $this->response->setStatus(403);
                    exit;
                }

                // xhr的send无法支持utf8
                if ($this->request->isAjax()) {
                    $file['name'] = urldecode($file['name']);
                }

                $result = self::modifyHandle($this->row, $file);

                if (false !== $result) {
                    $this->pluginHandle()->beforeModify($result);

                    $this->update(array(
                        'text' => serialize($result)
                    ), $this->db->sql()->where('cid = ?', $this->cid));

                    $this->db->fetchRow($this->select()->where('table.contents.cid = ?', $this->cid)
                        ->where('table.contents.type = ?', 'attachment'), array($this, 'push'));

                    /** 增加插件接口 */
                    $this->pluginHandle()->modify($this);

                    $this->response->throwJson(array($this->attachment->url, array(
                        'cid' => $this->cid,
                        'title' => $this->attachment->name,
                        'type' => $this->attachment->type,
                        'size' => $this->attachment->size,
                        'bytes' => number_format(ceil($this->attachment->size / 1024)) . ' Kb',
                        'isImage' => $this->attachment->isImage,
                        'url' => $this->attachment->url,
                        'permalink' => $this->permalink
                    )));
                }
            }
        }

        $this->response->throwJson(false);
    }

    /**
     * 初始化函数
     *
     * @access public
     * @return void
     */
//    public function action()
//    {
//        echo "hello upload";
//        if ($this->user->pass('contributor', true) && $this->request->isPost()) {
//            $this->security->protect();
//            if ($this->request->is('do=modify&cid')) {
//                $this->modify();
//            } else {
//                $this->upload();
//            }
//        } else {
//            $this->response->setStatus(403);
//        }
//    }
    public function action()
    {
        // TODO: Implement action() method.
    }
}

class SmmsForTypecho_Action extends Typecho_Widget implements Widget_Interface_Do
{
    //上传文件目录
    const UPLOAD_DIR = '/usr/uploads';

    public function smms_forward_callback()
    {
        //$paged = $request->get_param('paged');
        $tdb = Typecho_Db::get();;

        $lastname = $_FILES['smfile']['tmp_name'];

        $date = new Typecho_Date();
        $tp_uploads = Typecho_Common::url(defined('__TYPECHO_UPLOAD_DIR__') ? __TYPECHO_UPLOAD_DIR__ : self::UPLOAD_DIR,
                defined('__TYPECHO_UPLOAD_ROOT_DIR__') ? __TYPECHO_UPLOAD_ROOT_DIR__ : __TYPECHO_ROOT_DIR__)
            . '/' . $date->year . '/' . $date->month;
        //创建上传目录
        $tpath = $tp_uploads.'/'; // .'/smms_imglist/'
        if (!is_dir($tpath)) {
            if (!Upload::makeUploadDir($tpath)) {
                return false;
            }
        }

//        $path = $tpath.$_FILES['smfile']['name'];
        $ext = Upload::getSafeName($_FILES['smfile']['name']);

        if (!Upload::checkFileType($ext) ) {
            print_r(json_encode([
                'status_code'=>400,
                'msg'=>'check failed'
            ]));
            return [];
        }

        //获取文件名
        $fileName = sprintf('%u', crc32(uniqid())) . '.' . $ext;
        $path = $tpath . $fileName;

        //return $path;
        copy($lastname, $path);
        unlink($lastname);

        $options = Helper::options();

        $plugin_config = $options->plugin('SmmsForTypecho'); // 获取 后台设置
        // 是否只上传到本地
        // 返回相对存储路径
        $localOnly = $plugin_config->localOnly;
        if($localOnly){
            $relative_path = (defined('__TYPECHO_UPLOAD_DIR__') ? __TYPECHO_UPLOAD_DIR__ : self::UPLOAD_DIR)
                . '/' . $date->year . '/' . $date->month . '/' . $fileName;
            $localUrl = $options->rootUrl.$relative_path;
            // save to db
            list($width, $height, $type, $attr) = getimagesize($path);
            $fsize = filesize($path);
            $data['width']  = $width;
            $data['height'] = $height;
            $data['size']   = $fsize;
            $data['hash']   = md5_file($path);
            $data['url']    = $localUrl;

            $insert = $tdb->insert('table.'.MY_NEW_TABLE_NAME)
                ->rows($data);
            $insertId = $tdb->query($insert);
            // renturn json

            $result = array();
            $result["success"] = 1;
            $result['data']['url'] = $localUrl;
            $result['data']['size'] = $fsize;
            $this->response->throwJson($result);
            return $result;
        }

        $SourceImg_ = $plugin_config->SourceImg_;
        $hello_name = $plugin_config->hello_name;
        $hello_pswd = $plugin_config->hello_pswd;

        $auth = $plugin_config->Authorization_;

        $smapi = new SMApi($auth,$hello_name,$hello_pswd,$SourceImg_);

        $result = $smapi->Upload($path);
        if ($SourceImg_ == 1){
            if ($result["success"]) {
                $data['width']  = $result['data']['width'];
                $data['height'] = $result['data']['height'];
                $data['size']   = $result['data']['size'];
                $data['hash']   = $result['data']['hash'];
                $data['url']    = $result['data']['url'];

                $insert = $tdb->insert('table.'.MY_NEW_TABLE_NAME)
                    ->rows($data);
                $insertId = $tdb->query($insert);

                if ($plugin_config->Nolocal_) {
                    unlink($path);
                }
            } elseif ($result["code"] == "image_repeated") {
                $result['data']['url'] = $result["images"];
            }
        }else{
            if(array_key_exists('status_code',$result)){
                if ($result["status_code"] == 400){
                    // err
                }
            }
            if (array_key_exists('success',$result) && $result["success"]["code"] == 200) {
                $data['width']  = $result['image']['width'];
                $data['height'] = $result['image']['height'];
                $data['size']   = $result['image']['size'];
                $data['hash']   = $result['image']['md5'];
                $data['url']    = $result['image']['url'];

                $insert = $tdb->insert('table.'.MY_NEW_TABLE_NAME)
                    ->rows($data);
                $insertId = $tdb->query($insert);

                if ($plugin_config->Nolocal_) {
                    unlink($path);
                }
            } elseif ($result["error"]["code"] == 101) { // 重复上传
//                $result['data']['url'] = $result["images"];
            }
        }


        print_r(json_encode($result));
        return $result;
    }

    public function smms_getlist_callback()
    {
        $tdb = Typecho_Db::get();;
        $request = Typecho_Request::getInstance();

        $pages = $request->get('pages');
        $pages = $pages? : 1;
        $limit = 10;
        $offset = ($pages - 1) * 10;
        $query = $tdb->select('url')->from('table.'.MY_NEW_TABLE_NAME)->order('id',Typecho_Db::SORT_DESC)->offset($offset)->limit($limit);
        $result = $tdb->fetchAll($query);

        print_r(json_encode($result));
        return $result;

    }


    public function action()
    {
        if ($this->request->isPost()){
            $this->on($this->request->is('do=upload'))->smms_forward_callback();

        }elseif ($this->request->isGet()){
            $this->on($this->request->is('do=list'))->smms_getlist_callback();

        }

    }
}
