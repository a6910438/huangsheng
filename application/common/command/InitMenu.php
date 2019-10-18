<?php
namespace app\common\command;

use app\common\entity\ManageMenu;
use app\common\entity\ManagePower;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Env;

class InitMenu extends Command
{
    const WHITE_ARR = ['Index', 'Login', 'Admin', 'Upload'];

    protected function configure()
    {
        $this->setName('init-menu')
            ->setDescription('init admin menu');
    }

    protected function execute(Input $input, Output $output)
    {
              
        $output->writeln("crate menu start...");

        $controllerPath = Env::get('app_path') . 'admin/controller';
        $allClass = $this->getAllClass($controllerPath);
           
        foreach ($allClass as $classDir) {
            $namespace = trim(substr($classDir, strlen($controllerPath) + 1));
            $this->createMenuOrPower($namespace);
        }

        $output->writeln("crate menu end...");
    }

    private function createMenuOrPower($className)
    {
                   
        $namespace = '\app\admin\controller\\' . $className;
        //$namespace = '\App\Http\Controllers\Admin\Member\HandleController' ;
        $obj = new $namespace();
        $class = new \ReflectionClass($obj);

        $methods = $class->getMethods();
        $route = str_replace('\\', '/', $className);

        foreach ($methods as $method) {
            if ($method->isPublic() && $method->class == 'app\admin\controller\\' . $className) {
                $doc = $method->getDocComment();
                if ($doc) {
                    $result = $this->parseDoc($doc);
                    $path = '/admin/' . strtolower($route) . '/' . $method->name;
                    $httpMethod = $this->getRequestMethod($method->name);
                    $result['method'] = !empty($result['method']) ? $result['method'] : $httpMethod;

                    if (!$meauid = ManageMenu::checkPath($result['power'])) {
                        //判断父级是否存在
                        if ($result['level'] == 2) {
                            $parent = substr($result['power'], 0, strpos($result['power'], '|'));
                        } else if ($result['level'] == 3) {
                            $parent = substr($result['power'], 0, strpos($result['power'], '@'));
                        }
                        if (!$parentId = ManageMenu::checkPath($parent)) {
                            $parentId = \app\admin\service\rbac\Power\Service::addMenu([
                                'power' => $parent,
                                'level' => 1,
                                'sort' => $result['sort']
                            ]);
                        }
                        $result['parent_id'] = $parentId;
                        $meauid = \app\admin\service\rbac\Power\Service::addMenu($result);
                    }

                    if ($meauid && !ManagePower::checkPath($path)) {
                        \app\admin\service\rbac\Power\Service::addPower($path, $result['method'], $meauid);
                    }
                }
            }
        }

    }

    private function getRequestMethod($method)
    {
        switch ($method) {
            case 'index':
                return 'GET';
                break;
            case 'create':
                return 'GET';
                break;
            case 'edit':
                return 'GET';
                break;
            case 'save':
                return 'POST';
                break;
            case 'update':
                return 'POST';
                break;
            case 'delete':
                return 'POST';
                break;
            case 'read':
                return 'GET';
                break;
            default:
                return '';
                break;
        }
    }

    private function parseDoc($doc)
    {
        $result = preg_replace('/\n|\s*/', '', $doc);
        $result = preg_replace('/\/\*\*\*|\*\//', '', $result);

        $arr = explode('*', $result);

        $power = preg_replace('/^@power/', '', $arr[0]);
        $level = strpos($power, '@') ? 3 : 2;

        if (count($arr) > 1) {
            if (preg_match('/^@rank/', $arr[1])) {
                $sort = preg_replace('/^@rank/', '', $arr[1]);
                $sort = (int)$sort;
            } elseif (preg_match('/^@method/', $arr[1])) {
                $method = preg_replace('/^@method/', '', $arr[1]);
            }
        }
        return [
            'power' => $power,
            'level' => $level,
            'sort' => isset($sort) ? $sort : 0,
            'method' => isset($method) ? $method : ''
        ];
    }

    private function getAllClass($path)
    {
        static $data = [];
        $dir = opendir($path);
        while (($file = readdir($dir)) !== false) {
            if ($file != '.' && $file != '..') {
                if (is_dir($path . DIRECTORY_SEPARATOR . $file)) {
                    $this->getAllClass($path . DIRECTORY_SEPARATOR . $file);
                } else {
                    $fileName = substr($file, 0, strrpos($file, '.'));
                    if (!in_array($fileName, self::WHITE_ARR)) {
                        $data[] = $path . DIRECTORY_SEPARATOR . $fileName . "\n";

                    }
                }

            }
        }
        closedir($dir);
        return $data;
    }

}