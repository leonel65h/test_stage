<?php
                
define('PROJECT_NAME', 'test');

define ('dbname', 'test');
//define ('dbname', 'devupstest_bd');
//define ('dbname', 'devupstuto_bd');
define ('dbuser', 'root');
define ('dbpassword',  '');//BD20Devupstuto18
define ('dbhost',  'localhost');
        
// base url
/**
 * config environment
 */
// in production, replace by "/"
define('__v', '4.3');

define('__server', 'http://127.0.0.1');
define('__env', __server.'/test/');
define('__prod', false);
define('__default_lang', "fr");
define('__project_id', 'test');
define('__lang', 'en');


define('ROOT', __DIR__  . '/../');
define('UPLOAD_DIR', __DIR__. '/../uploads/');
define('RESSOURCE', __DIR__ . '/../admin/Ressource/');
define('admin_dir', __DIR__ . '/../admin/');
define('web_dir', __DIR__ . '/../web/');

define('SRC_FILE', __env. 'uploads/');
define('RESSOURCE2', __env. 'admin/Ressource/');
define('CLASSJS', __env. 'dclass/devupsjs/');
define('node_modules', __env . 'node_modules/');

define('ENTITY', 0);
define('VIEW', 1);

define('ADMIN', __project_id.'_devups');
define('dv_role_navigation', __project_id.'_navigation');
define('dv_role_permission', __project_id.'_permission');

/**
 * NOTIFIACTION DEFINE
 */
define('LANG', "lang");
define('PREVIOUSPAGE', "previous_page");
define('JSON_ENCODE_DEPTH', 512);



