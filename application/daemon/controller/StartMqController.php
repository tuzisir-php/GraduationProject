<?php
/**
 * User: yuzhao
 * CreateTime: 2019/3/19 下午3:59
 * Description: 启动MQ,php xxx/public/index.php /daemon/start_Mq/main 队列别名 进程数 -d(守护进程)
 */

namespace app\daemon\controller;
use app\common\config\SelfConfig;
use app\common\tool\ProcessTool;
use app\common\tool\RabbitMQTool;
use app\common\tool\ShellTool;

class StartMqController {

    private $dealPath = null;

    private $childsPid = array();

    /**
     * StartRabbitMQ constructor.
     */
    public function __construct()
    {
        // 脚本路径
        $this->dealPath = str_replace('/','\\',"/app/daemon/deal/");
    }

    /**
     * User: yuzhao
     * CreateTime: 2019/3/19 下午4:00
     * Description: 返回当前实例
     */
    public static function instance() {
        return new StartMqController();
    }

    /**
     * User: yuzhao
     * CreateTime: 2019/3/19 下午4:00
     * Description: 主要处理流程
     * @throws \ErrorException
     */
    public function main() {
        global $argv;
        // 扩展参数
        if (isset($argv[3])) {
            switch ($argv[3]) {
                case '-d': // 守护进程启动
                    ProcessTool::daemonStart();
                break;
                case '-s': // 杀死进程
                    ShellTool::kill($argv[1]);die();
                break;
            }
        }
        // 判断参数
        if (count($argv) < 2) {
            die('缺少参数');
        }
        // 获取配置信息
        $rabbitMqConf = SelfConfig::getConfig('Source.rabbit_mq');
        if (!isset( $rabbitMqConf['rabbit_mq_queue'][$argv[2]])) {
            die('没有配置:'.$argv[2]);
        }
        // 获取mq配置
        $mqConf = $rabbitMqConf['rabbit_mq_queue'][$argv[2]];
        // 实例化处理脚本
        $dealClass = $this->dealPath.$mqConf['consumer'];
        $dealObj = new $dealClass;
        $processNum = 1;
        if (isset($mqConf['process_num']) || !is_numeric($mqConf['process_num']) || $mqConf['process_num'] < 1 || $mqConf['process_num'] >10 ) {
            $processNum = $mqConf['process_num'];
        }
        if (!isset($mqConf['deal_num']) || !is_numeric($mqConf['deal_num'])) {
            die('处理条数设置有误');
        }
        // fork进程
        for ($i=0; $i<$processNum; $i++) {
            $pid = pcntl_fork();
            if( $pid < 0 ){
                exit();
            } else if( 0 == $pid ) {
                $this->downMqData($dealObj, $argv, $mqConf);
                exit();
            } else if( $pid > 0 ) {
                $this->childsPid[] = $pid;
            }
        }
        while( true ){
            sleep(1);
        }
    }

    /**
     * User: yuzhao
     * CreateTime: 2019/3/19 下午6:02
     * @param $dealObj
     * @param $argv
     * @param $mqConf
     * @throws \ErrorException
     * Description:
     */
    private function downMqData($dealObj, $argv, $mqConf) {
        while (true) {
            // 下载数据
            $mqData = RabbitMQTool::instance($argv[2])->rMq($mqConf['deal_num']);
            $dealObj->deal($mqData);
            sleep(1);
        }
    }

}
