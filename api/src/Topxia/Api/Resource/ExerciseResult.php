<?php

namespace Topxia\Api\Resource;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Topxia\Common\ArrayToolkit;

class ExerciseResult extends BaseResource
{
    public function post(Application $app, Request $request, $exerciseId)
    {
        $answers = $request->request->all();

        $questionItems = $this->getExerciseService()->getItemSetByExerciseId($exerciseId)['items'];
        $questionIds = ArrayToolkit::column($questionItems, "questionId");

        $answers = !empty($answers['data']) ? $answers['data'] : array();
        $result = $this->getExerciseService()->startExercise($exerciseId,$questionIds);
        $this->getExerciseService()->submitExercise($result['id'], $answers);
        $res = array(
            'id' => $result['id'],
        );
        return $res;
    }

    public function get(Application $app, Request $request, $lessonId)
    {
        $user = $this->getCurrentUser();
        $exercise = $this->getExerciseService()->getExerciseByLessonId($lessonId);
        if (empty($exercise)) {
            return "";
        }
        $exerciseResults = $this->getExerciseService()->getItemSetResultByExerciseIdAndUserId($exercise['id'],$user->id);
        if (empty($exerciseResults)) {
            throw $this->createNotFoundException ('无法查看练习结果！');
        }
        var_dump($exerciseResults);

        // $itemSetResults = $this->getExerciseService()->getItemSetResultByExerciseIdAndUserId($exercise['id'],$user->id)['items'];
        // $exerciseResults['items'] = $this->filterItem($itemSetResults);
        // return $this->filter($exerciseResults);
        return $exerciseResults;
    }

    private function filterItem($items)
    {
        $questionIds = ArrayToolkit::column($items, "questionId");
        $questions = $this->getQuestionService()->findQuestionsByIds($questionIds);

        $materialMap = array();
        $itemIndexMap = array();
        $newItems = array();
        foreach ($items as &$item) {
            unset($item['answer']);
            unset($item['userId']);

            $question = $questions[$item['questionId']];
            $item['questionType'] = $question['type'];
            $item['questionParentId'] = $question['parentId'];

            if ('material' == $item['questionType']) {
                $itemIndexMap[$item['questionId']] = $item['id'];
                $materialMap[$item['questionId']] = array();
            }

            if ($item['questionParentId'] != 0 && isset($materialMap[$item['questionParentId']])) {
                $materialMap[$item['questionParentId']][] = $item;
                continue;
            }

            $newItems[$item['id']] = $item;
        }

        foreach ($materialMap as $id => $material) {
            $newItems[$itemIndexMap[$id]]['items'] = $material;
        }

        return array_values($newItems);
    }

    public function filter(&$res)
    {
        $res['usedTime'] = date('c', $res['usedTime']);
        $res['updatedTime'] = date('c', $res['updatedTime']);
        $res['createdTime'] = date('c', $res['createdTime']);
        return $res;
    }

    protected function getExerciseService()
    {
        return $this->getServiceKernel()->createService('Homework:Homework.ExerciseService');
    }

    protected function getQuestionService()
    {
        return $this->getServiceKernel()->createService('Question.QuestionService');
    }
}
