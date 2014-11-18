<?php

class Resources
{
    /**
     * @var array Предопределенные алиясы
     */
    protected static $alias = array(
        '@jQuery' => 'js/jquery.min.js',

        '@jQueryUI.js' => 'lib/jquery-ui-1.11.0/jquery-ui.min.js',
        '@jQueryUIstructure.css' => 'lib/jquery-ui-1.11.0/jquery-ui.structure.min.css',
        '@jQueryUIDatepicker.lang.js' => 'js/time_picker/i18n/jquery.ui.datepicker-ru.js',

        '@jQueryUITimepicker.js' => 'js/time_picker/jquery-ui-timepicker-addon.min.js',
        '@jQueryUITimepicker.css' => 'js/time_picker/css/jquery-ui-timepicker-addon.min.css',
        '@jQueryUITimepickersliderAccess.js' => 'js/time_picker/jquery-ui-sliderAccess.min.js',
        '@jQueryUITimepicker.lang.js' => 'js/time_picker/i18n/jquery-ui-timepicker-ru.js',

        '@bootstrap.js' => 'lib/bootstrap/js/bootstrap.min.js',
        '@bootstrap.css' => 'lib/bootstrap/css/bootstrap.min.css',
        '@bootstrap.theme.css' => '',

        '@select2.js' => 'lib/select2/select2.min.js',
        '@select2.i8n.js' => 'lib/select2/select2_locale_ru.js',
        '@select2.css' => 'lib/select2/select2.css',
        '@select2.bootstrap.css' => 'lib/select2/select2-bootstrap.css',

        '@ckeditor' => 'laPuzzle/js/ckeditor/ckeditor.js',
        '@ckeditorPreset.js' => 'laPuzzle/js/ckeditor/presets/ckeditor.default.set.js',

        '@gritter' => 'laPuzzle/js/gritter/js/jquery.gritter.js',
        '@gritter.css'=> 'laPuzzle/js/gritter/css/jquery.gritter.css',

    );

    /**
     * Predefined constants
     * You can use: Resources::addFile(Resources::jQuery);
     */
    const bootstrap = '@bootstrap.js';
    const ckeditor = '@ckeditor';
    const jQuery = '@jQuery';
    const jQueryUI = '@jQueryUI.js';
    const jQueryUITimepicker = '@jQueryUITimepicker.js';
    const select2  = '@select2.js';

    // =====================================

    /**
     * @var array Реестр рессурсов для вывода
     */
    protected static $registry = array();

    /**
     * @var array Рессурсы для вывода в footer
     */
    protected static $footerRc = array();

    /**
     * @var array Рессурсы для вывода в header
     */
    protected static $headerRc = array();

    protected static $addedFiles = array();

    protected static $skip_minification = false;

    protected static $cacheOn = false;
    protected static $cacheDir = '';

    protected static $consolidate = false;
    protected static $isAdmin = false;
    protected static $minify = false;

    public static function __init() {
        global $cfg, $cache, $cot_rc_skip_minification;

        static::$cacheOn = !empty($cache);
        static::$cacheDir = $cfg['cache_dir'] . '/static/';

        static::$consolidate = $cfg['headrc_consolidate'];

        static::$isAdmin = defined('COT_ADMIN');

        static::$minify = $cfg['headrc_minify']; // && !$cot_rc_skip_minification;

        if (static::$consolidate) {
            if (!file_exists($cfg['cache_dir'])) mkdir($cfg['cache_dir']);
            if (!file_exists(static::$cacheDir)) mkdir(static::$cacheDir);
        }
    }

    /**
     * Puts a JS/CSS file into the resource registry to be consolidated with other
     * such resources and stored in cache.
     *
     * It is recommened to use files instead of embedded code and use this method
     * instead of Resources::AddEmbed(). Use this way for any sort of static JavaScript or
     * CSS linking.
     *
     * Do not put any private data in any of resource files - it is not secure. If you really need it,
     * then use direct output instead.
     *
     * @param string $path  Path to a *.js script or *.css stylesheet
     * @param string $type
     * @param int    $order Order priority number
     * @param mixed  $scope Resource scope. Scope is a selector of domain where resource is used. Valid scopes are:
     *                      'global' - global for entire site, will be included everywhere, this is the most static and persistent scope;
     *                      'guest' - for unregistered visitors only;
     *                      'user' - for registered members only;
     *                      'group_123' - for members of a specific group, in this example of group with id=123.
     *                      It is recommended to use 'global' scope whenever possible because it delivers best caching opportunities.
     *
     * @return bool Returns TRUE normally, FALSE is file was not found
     */
    public static function addFile($path, $type = '', $order = 50, $scope = 'global')
    {

        $tmp = explode('?', $path);
        $fileName = $tmp[0];

        if (in_array($fileName, static::$addedFiles)) return false; // Уже добавлено
        if (mb_strpos($fileName, '@' !== 0) && !file_exists($fileName)) return false; // Файл не найден

        if (mb_strpos($fileName, '@') === 0) $fileName = static::$alias[$fileName];

        if (empty($type)) $type = preg_match('#\.(min\.)?(js|css)$#', mb_strtolower($fileName), $m) ? $m[2] : 'js';

        static::$addedFiles[] = $tmp[0];

        if (static::$cacheOn && static::$consolidate && !static::$isAdmin && static::$minify
            && !static::$skip_minification && mb_strpos($fileName, '.min.') === false
        ) {
            $bname = ($type == 'css') ? str_replace('/', '._.', $fileName) : basename($fileName) . '.min';
            $code = static::minify(file_get_contents($fileName), $type);
            $path = static::$cacheDir . $bname;
            file_put_contents($path, $code);
        }

        if (static::$consolidate && static::$cacheOn && !static::$isAdmin) {
            static::$registry[$type][$scope][$order][] = $path;
        } else {
            static::$registry[$type]['files'][$scope][$order][] = $path;
        }

        foreach (static::additionalFiles($tmp[0]) as $file) {
            static::addFile($file, '', $order, $scope);
        }

        return true;
    }

    protected static function additionalFiles($file)
    {
        $ret = array();

        switch ($file) {
            case '@jQueryUITimepicker.js':
                $ret[] = '@jQueryUITimepicker.css';
                $ret[] = '@jQueryUITimepickersliderAccess.js';
                $ret[] = '@jQueryUITimepicker.lang.js';
                $ret[] = '@jQueryUI.activator.js';
                break;

            case '@bootstrap.js':
                $ret[] = '@bootstrap.css';
                $ret[] = '@bootstrap.theme.css';
                break;

            case '@select2.js':
                $ret[] = '@select2.i8n.js';
                $ret[] = '@select2.css';
                $ret[] = '@select2.bootstrap.css';
                $ret[] = '@jQueryUI.activator.js';
                break;

            case '@jQueryUI.js':
                $ret[] = '@jQueryUIDatepicker.lang.js';
                $ret[] = '@jQueryUIstructure.css';
                $ret[] = '@jQueryUI.activator.js';
                break;

            case '@ckeditor':
                $ret[] = '@ckeditorPreset.js';
                //$ret[] =  'site/themes/personal/css/smoothness/jquery-ui-1.10.4.min.css';
                break;

            case '@gritter':
                $ret[] = '@gritter.css';
                break;
        }

        return $ret;
    }


    /**
     * Puts a portion of embedded code into the header CSS/JS resource registry.
     *
     * It is strongly recommended to use files for CSS/JS whenever possible
     * and call Resources::AddFile() function for them instead of embedding code
     * into the page and using this function. This function should be used for
     * dynamically generated code, which cannot be stored in static files.
     *
     * @param string $identifier Alphanumeric identifier for the piece, used to control updates, etc.
     * @param string $code       Embedded stylesheet or script code
     * @param string $scope      Resource scope. See description of this parameter in Resources::AddFile() docs.
     * @param string $type       Resource type: 'js' or 'css'
     * @param int    $order      Order priority number
     *
     * @return bool This function always returns TRUE
     * @see Resources::AddFile()
     */
    public static function addEmbed($code, $type = 'js', $order = 50, $scope = 'global', $identifier = '')
    {

        // Если используем консолидацию и минификацию, сохранить в файл
        if (static::$consolidate && static::$cacheOn && !static::$isAdmin) {
            if (!$identifier) $identifier = md5($code . $type);
            // Save as file
            $path = static::$cacheDir . $identifier . '.' . $type;
            if (!file_exists($path) || md5($code) != md5_file($path)) {
                if (static::$minify && !static::$skip_minification) {
                    $code = static::minify($code, $type);
                }
                file_put_contents($path, $code);
            }
            static::$registry[$type][$scope][$order][] = $path;
        } else {
            $separator = $type == 'js' ? "\n;" : "\n";
            static::$registry[$type]['embed'][$scope][$order] .= $code . $separator;
        }
        return true;
    }

    public static function render()
    {
        $rc_html = static::consolidate();
        $ret = '';
        if (is_array($rc_html)) {
            foreach ($rc_html as $scope => $html) {
                switch ($scope) {
                    case 'global':
                        $pass = true;
                        break;
                    case 'guest':
                        $pass = cot::$usr['id'] == 0;
                        break;
                    case 'user':
                        $pass = cot::$usr['id'] > 0;
                        break;
                    default:
                        $parts = explode('_', $scope);
                        $pass = count($parts) == 2 && $parts[0] == 'group' && $parts[1] == cot::$usr['maingrp'];
                }
                if ($pass) $ret = $html . $ret;

            }
        }


        // Теперь собирем рессурсы не участвубщие в минификациях
        if (!is_array(static::$headerRc)) return $ret;

        // CSS should go first
        ksort(static::$headerRc);
        foreach (static::$headerRc as $type => $data) {
            if (!empty(static::$headerRc[$type]) && is_array(static::$headerRc[$type])) {
                foreach (static::$headerRc[$type] as $order => $htmlArr) {
                    foreach ($htmlArr as $key => $htmlData) {
                        $ret .= $htmlData . "\n";
                    }
                }
            }
        }

        return $ret;

    }

    /**
     * Сборка рессурсов в один файл
     */
    protected static function consolidate(){
        global $theme;

        // Если нужно собирать и ужимать делаем это
        if (!is_array(static::$registry)) return false;

        // CSS should go first
        ksort(static::$registry);

        $rc_html = array();

        // Consolidate resources
        if (static::$cacheOn && static::$consolidate && !static::$isAdmin) {

            clearstatcache();
            foreach (static::$registry as $type => $scope_data) {
                if ($type == 'css') {
                    $separator = "\n";
                } elseif ($type == 'js') {
                    $separator = "\n;";
                }
                // Consolidation
                foreach ($scope_data as $scope => $ordered_files) {
                    ksort($ordered_files);
                    $target_path = static::$cacheDir . "$scope.$theme.$type";

                    $files = array();
                    foreach ($ordered_files as $order => $o_files) {
                        $files = array_merge($files, $o_files);
                    }
                    $files = array_unique($files);

                    foreach ($files as $key => $file) {
                        if (mb_strpos($file, '@') === 0) $files[$key] = static::$alias[$file];
                    }

                    $code = '';
                    $modified = false;

                    if (!file_exists($target_path)) {
                        // Just compile a new cache file
                        $file_list = $files;
                        $modified = true;
                    } else {
                        // Load the list of files already cached
                        $file_list = unserialize(file_get_contents("$target_path.idx"));

                        // Check presense or modification time for each file
                        foreach ($files as $path) {
                            if (!in_array($path, $file_list) || filemtime($path) >= filemtime($target_path)) {
                                $modified = true;
                                break;
                            }
                        }
                    }

                    if ($modified) {
                        // Reconsolidate cache
                        $current_path = str_replace('\\', '/', realpath('.'));
                        foreach ($files as $path) {
                            // Get file contents and remove BOM
                            $file_code = str_replace(pack('CCC', 0xef, 0xbb, 0xbf), '', file_get_contents($path));

                            if ($type == 'css') {
                                if (strpos($path, '._.') !== false) {
                                    // Restore original file path
                                    $path = str_replace('._.', '/', basename($path));
                                }
                                if ($path[0] === '/') {
                                    $path = mb_substr($path, 1);
                                }
                                $file_path = str_replace('\\', '/', dirname(realpath($path)));
                                $relative_path = str_replace($current_path, '', $file_path);
                                if ($relative_path[0] === '/') {
                                    $relative_path = mb_substr($relative_path, 1);
                                }

                                // Apply CSS imports
                                if (preg_match_all('#@import\s+url\((\'|")?([^\)"\']+)\1?\);#i', $file_code, $mt, PREG_SET_ORDER)) {
                                    foreach ($mt as $m) {
                                        if (preg_match('#^https?://#i', $m[2])) {
                                            $filename = $m[2];
                                        } else {
                                            $filename = empty($relative_path) ? $m[2] :$relative_path . '/' . $m[2];
//                                            $filename = empty($relative_path) ? $m[2] : realpath($relative_path . '/' . $m[2]);
                                        }
                                        $file_code = str_replace($m[0], file_get_contents($filename), $file_code);
                                    }
                                }

                                // Fix URLs
                                if (preg_match_all('#\burl\((\'|")?([^\)"\']+)\1?\)#i', $file_code, $mt, PREG_SET_ORDER)) {
                                    foreach ($mt as $m) {
                                        $filename = empty($relative_path) ? $m[2] : $relative_path . '/' . $m[2];
                                        $filename = str_replace($current_path, '', str_replace('\\', '/', realpath($filename)));
                                        if (!$filename) {
                                            continue;
                                        }
                                        if ($filename[0] === '/') {
                                            $filename = mb_substr($filename, 1);
                                        }
                                        $file_code = str_replace($m[0], 'url("' . $filename . '")', $file_code);
                                    }
                                }
                            }
                            $code .= $file_code . $separator;
                        }

                        file_put_contents($target_path, $code);
                        if (cot::$cfg['gzip']) {
                            file_put_contents("$target_path.gz", gzencode($code));
                        }
                        file_put_contents("$target_path.idx", serialize($files));
                    }

                    $rc_url = "rc.php?rc=$scope.$theme.$type";

                    if (empty($rc_html[$scope])) $rc_html[$theme][$scope] = '';
                    $rc_html[$scope] .= cot_rc("code_rc_{$type}_file", array('url' => $rc_url));

                }
            }
            // Save the output
            cot::$cache && cot::$cache->db->store('cot_rc_html', $rc_html);

        } else {

            $log = array(); // log paths to avoid duplicates
            foreach (static::$registry as $type => $resData) {
                if (!empty(static::$registry[$type]['files']) && is_array(static::$registry[$type]['files'])) {
                    foreach (static::$registry[$type]['files'] as $scope => $scope_data) {
                        ksort($scope_data);
                        foreach ($scope_data as $order => $files) {
                            foreach ($files as $file) {
                                if (!in_array($file, $log)) {
                                    $fileName = $file;
                                    if (mb_strpos($file, '@') === 0) $fileName = static::$alias[$file];

                                    if (empty($rc_html[$scope])) $rc_html[$scope] = '';

                                    $rc_html[$scope] .= cot_rc("code_rc_{$type}_file", array('url' => $fileName)) . "\n";
                                    $log[] = $file;
                                }
                            }
                        }
                    }
                }
                if (!empty(static::$registry[$type]['embed']) && is_array(static::$registry[$type]['embed'])) {
                    foreach (static::$registry[$type]['embed'] as $scope => $scope_data) {
                        ksort($scope_data);
                        foreach ($scope_data as $order => $code) {
                            if (empty($rc_html[$scope])) $rc_html[$scope] = '';
                            $rc_html[$scope] .= cot_rc("code_rc_{$type}_embed", array('code' => $code)) . "\n";
                        }
                    }
                }
            }
        }
        return $rc_html;
    }

    /**
     * Вывод рессурсов в подвал
     */
    public static function renderFooter()
    {

        if (!is_array(static::$footerRc)) return false;

        // CSS should go first
        ksort(static::$footerRc);
        $ret = '';

        foreach (static::$footerRc as $type => $data) {
            if (!empty(static::$footerRc[$type]) && is_array(static::$footerRc[$type])) {
                foreach (static::$footerRc[$type] as $order => $htmlArr) {
                    foreach ($htmlArr as $key => $htmlData) {
                        $ret .= $htmlData . "\n";
                    }
                }
            }
        }

        return $ret;
    }

    /**
     * A shortcut for plain output of a link to a CSS/JS file in the header of the page
     *
     *  Эти файлы не участвуют в возможных консолидациях или минификациях рессурсов и выводятся после таких рессурсов
     *
     * @param string $path Stylesheet *.css or script *.js path/url
     * @param string $type
     * @param int    $order
     *
     * @return bool
     */
    public static function linkFile($path, $type = '', $order = 50)
    {
        $tmp = explode('?', $path);
        $fileName = $tmp[0];

        if (in_array($fileName, static::$addedFiles)) return false;

        if (mb_strpos($fileName, '@') === 0) $fileName = $path = static::$alias[$fileName];

        if (!file_exists($fileName)) {
            throw new Exception ("Can't link recourse file " . $fileName);
        }

        if (empty($type)) $type = preg_match('#\.(js|css)$#i', $fileName, $m) ? strtolower($m[1]) : 'js';

        $path = cot_rc("code_rc_{$type}_file", array('url' => $path));

        static::$addedFiles[] = $tmp[0];
        static::$headerRc[$type][$order][] = $path;

        foreach (static::additionalFiles($tmp[0]) as $file) {
            static::linkFile($file, '', $order);
        }
    }

    /**
     * A shortcut to append a JavaScript or CSS file to the footer
     *
     * @param string $path JavaScript or CSS file path
     * @param string $type
     * @param int    $order
     *
     * @return bool
     */
    public static function linkFileFooter($path, $type = '', $order = 50)
    {

        $tmp = explode('?', $path);
        $fileName = $tmp[0];

        if (in_array($fileName, static::$addedFiles)) return false;

        if (mb_strpos($fileName, '@') === 0) $fileName = $path = static::$alias[$fileName];

        if (!file_exists($fileName)) return;

        if (empty($type)) $type = preg_match('#\.(js|css)$#i', $fileName, $m) ? strtolower($m[1]) : 'js';

        $path = cot_rc("code_rc_{$type}_file", array('url' => $path));

        static::$addedFiles[] = $tmp[0];
        static::$footerRc[$type][$order][] = $path;

        foreach (static::additionalFiles($tmp[0]) as $file) {
            static::linkFileFooter($file, '', $order);
        }
    }

    /**
     * A shortcut for plain output of an embedded stylesheet/javascript in the header of the page
     *
     * Example: Resources::embed(" alert('ssssss') ");
     *          Resources::embed(" .blablabla {color: #000000} ", 'css');
     *
     * @param string $code Stylesheet or javascript code
     * @param int    $order
     * @param string $type Resource type: 'js' or 'css'
     */
    public static function embed($code, $type = 'js', $order = 50)
    {
        $code = cot_rc("code_rc_{$type}_embed", array('code' => $code));
        static::$headerRc[$type][$order][] = $code;
    }

    /**
     * A shortcut for plain output of an embedded stylesheet/javascript in the footer of the page
     *
     * Example: Resources::embedFooter(" alert('ssssss') ");
     *
     * @param string $code Stylesheet or javascript code
     * @param string $type Resource type: 'js' or 'css'
     * @param int    $order
     */
    public static function embedFooter($code, $type = 'js', $order = 50)
    {
        $code = cot_rc("code_rc_{$type}_embed", array('code' => $code));
        static::$footerRc[$type][$order][] = $code;
    }

    /**
     * JS/CSS minification function
     *
     * @param string $code Code to minify
     * @param string $type Type: 'js' or 'css'
     *
     * @return string Minified code
     */
    public static function minify($code, $type = 'js')
    {
        if ($type == 'js') {
            require_once './lib/jsmin.php';
            $code = JSMin::minify($code);
        } elseif ($type == 'css') {
            require_once './lib/cssmin.php';
            $code = minify_css($code);
        }
        return $code;
    }

    public static function setAlias($newAlias, $value = ''){
        if($newAlias == '') return false;

        if(mb_strpos($newAlias, '@') === false) $newAlias = '@'.$newAlias;

        static::$alias[$newAlias] = $value;
    }

    public static function getAlias($aliasName){
        if($aliasName == '') return null;

        if(mb_strpos($aliasName, '@') === false) $aliasName = '@'.$aliasName;

        if(!isset(static::$alias[$aliasName])) return null;

        return static::$alias[$aliasName];
    }

}

Resources::__init();