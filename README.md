# SmmsForTypecho
 sm.ms 图床的typecho 插件 ，欢迎 star，pr  
sm.ms 是一个好用免费的图床，因为不想把图片存服务器，所以写了这个插件

## 注意  
typecho 1.0 版本的时候在判断是虚拟机的时候禁止上传附件。
解决方案都一样：http://www.phpnote.net/index.php/Home/Article/index/id/54  
首先我是找到var/Typecho/Common.php这个文件并更改415行左右的一个关于你服务器的函数。
```
public static function isAppEngine()
{
    return !empty($_SERVER['HTTP_APPNAME'])                     // SAE
        || !!getenv('HTTP_BAE_ENV_APPID')                       // BAE
        || !!getenv('SERVER_SOFTWARE')                          // BAE 3.0
        || (ini_get('acl.app_id') && class_exists('Alibaba'))   // ACE
        || (isset($_SERVER['SERVER_SOFTWARE']) && strpos($_SERVER['SERVER_SOFTWARE'],'Google App Engine') !== false) // GAE
        ;
}
```  
把这个函数直接改成:
```
public static function isAppEngine()
{
return false;
}
```
然后去 typecho 目录下的 usr 目录下创建uploads 文件夹，给权限 0777 就好了。

#### 插件版本 v 1.2
#### 功能：
1. 后台图片管理页面，以及写文章时的单独图片管理页，及插及用
2. 支持批量上传图片到图床
3. 支持上传图片到自己的 smms 免费空间（自己管理的空间是容量有限的）
4. 支持评论框上传图片（需要设置）

#### 使用
为了维护 HTML 文档的正确性,使用方式改为手动启用
插件不自带jquery，需要自行引入 jquery 。  
启用插件后  
在主题header部分插入：  
```
<?php Typecho_Plugin::factory('SmmsPlugin')->header($this); ?>
```
footer部分插入：  
```
<?php Typecho_Plugin::factory('SmmsPlugin')->footer($this); ?>
```

如果有设置 pjax，则pjax插入以下代码:  
```
    if (typeof smms_node!="undefined" && typeof smms!="undefined"){
        smms_node.init()
        smms.init()
    }
    if (typeof smms!="undefined"){
        smms.init()
    }
```
#### 关于评论框设置
因为不同的作者的主题的评论框代码不一样，所以需要我们自己手动定位到评论框。    
在插件的设置中填入 评论框的选择器，比如评论框 '<textarea id="text">',那么填入 #text   
handsome主题的填 #comment
如果不会，最简单的打开chrome ，按如下操作：
 0、按f12 ，点图上第0个位置
 1、选中评论框
 2、在对应代码处右键，选择-》复制-》复制选择器
 
![无标题.png](https://i.loli.net/2020/10/14/rvR7PW5uVtnhpQf.png)



  
#### 截图  

设置
![image.png](https://i.loli.net/2020/10/14/Ece4hsWxCMRUKZb.png)  
写文章
![image.png](https://i.loli.net/2020/10/14/iQIlCTbkhSHVP8g.png)
后台管理
![image.png](https://i.loli.net/2020/10/14/y5vEmpt2LxuAK9q.png)
评论框上传
![image.png](https://i.loli.net/2020/10/14/InBSM2xGAj7hePd.png)

