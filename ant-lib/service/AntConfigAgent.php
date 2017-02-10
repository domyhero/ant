<?php
/**
 * Created by PhpStorm.
 * User: shenzhe
 * Date: 2016/11/30
 * Time: 19:54
 */

namespace service;


use common\Consts;
use common\Utils;
use sdk\LoadService;
use ZPHP\Core\Config as ZConfig;

class AntConfigAgent
{

    /**
     * @param $serviceName
     * @param $record
     * @return bool
     * @desc 同步一条记录
     */
    public function sync($serviceName, $record)
    {
        if (empty($serviceName)) {
            return false;
        }
        $serviceName = Utils::getServiceConfigNamespace($serviceName);
        $configData = ZConfig::get($serviceName, []);
        $configData[$record['item']] = $record['value'];
        return $this->_sync($serviceName, $configData);
    }

    /**
     * @param $serviceName
     * @return bool
     * @desc 全量同步
     */
    public function syncAll($serviceName)
    {
        if (empty($serviceName)) {
            return false;
        }
        try {
            $configService = LoadService::getService(Consts::CONFIG_SERVER_NAME);
            $result = $configService->call('all', [
                'serviceName' => $serviceName
            ]);
            if (empty($result)) {
                //读取数据失败
                return false;
            }
            $data = $result->getData();
            if ($data) {
                $configData = [];
                foreach ($data as $_config) {
                    $configData[$_config['item']] = $_config['value'];
                }
                return $this->_sync(Utils::getServiceConfigNamespace($serviceName), $configData);
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param $serviceName
     * @param $data
     * @return bool
     * @desc 写入并更新配置文件
     */
    private function _sync($serviceName, $data)
    {
        $path = ZConfig::getField('lib_path', 'ant-lib');
        if (empty($path)) {
            return false;
        }
        $filename = $path . DS . 'config' . DS . $serviceName . '.php';
        file_put_contents($filename, "<?php\rreturn array(
                        '$serviceName'=>" . var_export($data, true) . "
                    );");
        return ZConfig::mergeFile($filename);
    }
}