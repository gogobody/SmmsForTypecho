<?php

global $tdb;
$tdb = Typecho_Db::get();
define('MY_NEW_TABLE_NAME', 'smms_image_list');

define('MY_NEW_TABLE', $tdb->getPrefix() . 'smms_image_list');
// 插件激活时，运行回调方法创建数据表, 在WP原有的options表中插入插件版本号

function plugin_activation_cretable()
{
	global $tdb;

	$charset_collate = '';

	if (!empty($tdb->charset)) {
		$charset_collate = "DEFAULT CHARACTER SET {$tdb->charset}";
	}

	if (!empty($tdb->collate)) {
		$charset_collate .= " COLLATE {$tdb->collate}";
	}
    $db = Typecho_Db::get();
    $prefix = $db->getPrefix();
    $type = explode('_', $db->getAdapterName());
    $type = array_pop($type);

    if($type == "SQLite"){
        $sql ="SELECT count(*) FROM sqlite_master WHERE type='table' AND name='".MY_NEW_TABLE."';";
        $checkTabel = $db->query($sql);
        $row = $checkTabel->fetchAll();
        if ($row[0]["count(*)"] == '0'){
            $sql = "CREATE TABLE " . MY_NEW_TABLE . " (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            width INTEGER NOT NULL,
            height INTEGER NOT NULL,
            size INTEGER NOT NULL,
            hash varchar(255) NOT NULL,
            url varchar(255) NOT NULL
	        ) $charset_collate;";
            $tdb->query($sql);
        }
    }else{
        $sql = 'SHOW TABLES LIKE "' . $prefix . 'smms_image_list' . '"';
        $checkTabel = $db->query($sql);
        $row = $checkTabel->fetchAll();
        if ('1' == count($row)) {
            // exist
        }else{
            $sql = "CREATE TABLE " . MY_NEW_TABLE . " (
            id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
            width int NOT NULL,
            height int NOT NULL,
            size int NOT NULL,
            hash varchar(255) NOT NULL,
            url varchar(255) NOT NULL
	        ) $charset_collate;";
            $tdb->query($sql);
        }
    }

}


// 插件停用时，运行回调方法删除数据表，删除options表中的插件版本号
function plugin_deactivation_deltable()
{
//	global $tdb;
//  暂时不删除数据表
//	$tdb->query("DROP TABLE IF EXISTS " . MY_NEW_TABLE);
}

