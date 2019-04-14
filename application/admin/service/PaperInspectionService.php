<?php
/**
 * User: yuzhao
 * CreateTime: 2019/3/19 下午10:19
 * Description:
 */
namespace app\admin\service;

use app\common\base\ServiceInter;
use app\common\config\SelfConfig;
use app\common\model\EveryStudentTopicModel;
use app\common\model\ExamTopicModel;
use app\common\model\StudentExamTopicModel;
use app\common\model\TestPaperContentModel;
use app\common\tool\TimeTool;

class PaperInspectionService implements ServiceInter {

    public static function instance()
    {
        // TODO: Implement instance() method.
        return new PaperInspectionService();
    }

    /**
     * User: yuzhao
     * CreateTime: 2019/3/19 下午10:34
     * @param array $params
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * Description:
     */
    public function getList($params = [])
    {
        // TODO: Implement getList() method.
        // 获取考试过后的信息
        $examTopicInfo = ExamTopicModel::instance()->getList([
            'status' => 2
        ]);
        return $examTopicInfo;
    }

    public function add($params = [], &$result)
    {
        // TODO: Implement add() method.
    }

    public function up($params = [], &$result)
    {
        // TODO: Implement up() method.
    }

    public function del($params = [], &$result)
    {
        // TODO: Implement del() method.
    }

    public function up_status($params = [], &$result)
    {
        // TODO: Implement up_status() method.
    }

    /**
     * User: yuzhao
     * CreateTime: 2019/3/19 下午11:10
     * @param array $params
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * Description:
     */
    public function paperInspection($params=[]) {
        // 获取所有考生
        $studentInfo = StudentExamTopicModel::instance()->getList([
            'exam_topic_id' => $params['exam_topic_id']
        ]);
        $newStudentInfo['students'] = [];
        $newStudentInfo['count'] = $studentInfo;
        foreach ($studentInfo as $key => $value) {
            $valueArr = $value->toArray();
            $newStudentInfo['students'][$valueArr['id']] = $value;
        }
        // 获取考试专题信息
        $examTopicInfo = ExamTopicModel::instance()->getList([
            'id' => $params['exam_topic_id']
        ])->toArray()['data'][0];
        $testConfig = json_decode($examTopicInfo['question_bank_config'], true);
        // 获取考生试卷
        $studentExamTopicIds = array_column($studentInfo->toArray()['data'],'id');
        // 获取中间表信息
        $everyTopicInfo = EveryStudentTopicModel::instance()->getList([
            'student_exam_topic_id' => $studentExamTopicIds,
            'is_deal' => 0
        ])->toArray();
        if (empty($everyTopicInfo)) {
            $newStudentInfo['students'] = [];
        }
        // 获取试卷原始信息
        $testPaperContentIds = array_column($everyTopicInfo, 'test_paper_content_id');
        $testPaperContents = TestPaperContentModel::instance()->getList(['all'=>true, 'id'=>$testPaperContentIds])->toArray();
        $newEveryTopicInfo = [];
        foreach ($everyTopicInfo as $key => $value) {
            foreach ($testPaperContents as $key1 => $value1) {
                if ($value1['id'] == $value['test_paper_content_id']) {
                    $value1['option'] = json_decode($value1['option'], true);
                    $value1['score'] = $testConfig[$value1['type']]['score'];
                    $value['content'] = $value1;
                }
            }
            $newEveryTopicInfo[$value['student_exam_topic_id']][] = $value;
        }
        foreach ($newEveryTopicInfo as $key => $value) {
            $newStudentInfo['students'][$key]['student_paper_info'] = $value;
        }
        return $newStudentInfo;
    }

    /**
     * User: yuzhao
     * CreateTime: 2019/4/14 下午10:04
     * @param $data
     * @param $msg
     * Description: 阅卷
     * @return bool
     */
    public function lookTestPaper($data, &$msg) {
        $upData = [];
        foreach ($data['score'] as $key => $value) {
            $upData[] = [
                'id' => $key,
                'score' => $value,
                'is_deal' => true,
                'utime' => TimeTool::getTime()
            ];
        }
        $res = EveryStudentTopicModel::instance()->upAll($upData);
        if ($res) {
            $msg = '提交成功';
            return true;
        }
        $msg = '提交失败';
        return false;
    }
}