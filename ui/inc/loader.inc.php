<?php
set_include_path(LIB_ROOT . PATH_SEPARATOR . get_include_path());
function _ui_loader($className)
{
    if (class_exists($className, false)) {
        return;
    }
    $className = str_replace('_', '/', $className);

    if ('localhost' !== App::getModule()) {
        $moduleName = App::getModule();
        $moduleRoot = ROOT . EnvConf::$moduleRoot . "/$moduleName";
        if (substr($className, -10) === 'Controller') {
			$realClassFile = "$moduleRoot/controllers/";
			$subDir = App::getSubDir();
			if(!empty($subDir)){
				$realClassFile .= $subDir . '/';
			}
            $realClassFile .= strtolower(substr($className, 0, -10)) . '.php';
            if (file_exists($realClassFile)) {
                include $realClassFile;
            }
            return;
        } else
            if (substr($className, -6) === 'Module') {
                $realClassFile = "$moduleRoot/module.php";
                if (file_exists($realClassFile)) {
                    include $realClassFile;
                }
                return;
            } else {
                $firstSegIndex = strpos($className, '/');
                if ($firstSegIndex !== false && strtolower(substr($className, 0, $firstSegIndex)) === $moduleName) {
                    $suffixClassName = strtolower(substr($className, $firstSegIndex + 1));
                    $realClassFile   = "$moduleRoot/library/$suffixClassName.php";
                    if (file_exists($realClassFile)){
                        include $realClassFile;
                    }
                    return;
                }
            }
    }
    $realClassFile = ROOT . "/lib/$className.php";
    if (file_exists($realClassFile)){
        include $realClassFile;
    }
}

spl_autoload_register('_ui_loader');
?>
