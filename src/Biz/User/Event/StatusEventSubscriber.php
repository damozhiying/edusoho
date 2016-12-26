<?php
namespace Biz\User\Event;

use Codeages\Biz\Framework\Event\Event;
use Codeages\PluginBundle\Event\EventSubscriber;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class StatusEventSubscriber extends EventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            'course.thread.post.create' => 'onThreadPostCreate'
        );
    }

    public function onThreadPostCreate(Event $event)
    {
        $post   = $event->getSubject();
        $thread = $this->getThreadService()->getThread($post['courseId'], $post['threadId']);

        $course = $this->getCourseService()->getCourse($post['courseId']);

        if ($course['parentId']) {
            $classroom = $this->getClassroomService()->findClassroomIdsByCourseId($course['id']);
            $isTeacher = $this->getClassroomService()->isClassroomTeacher($classroom[0], $post['userId']);
        } else {
            $isTeacher = $this->getCourseService()->isCourseTeacher($post['courseId'], $post['userId']);
        }

        if ($isTeacher && $thread['type'] == 'question') {
            $this->getStatusService()->publishStatus(array(
                'userId'     => $thread['userId'],
                'courseId'   => $post['courseId'],
                'type'       => 'teacher_thread_post',
                'objectType' => 'thread_post',
                'objectId'   => $post['id'],
                'private'    => $course['status'] == 'published' ? 0 : 1,
                'properties' => array(
                    'thread' => $thread,
                    'post'   => $post
                )
            ));
        }
    }

    protected function getStatusService()
    {
        return $this->getBiz()->service('User:StatusService');
    }

    protected function getCourseService()
    {
        return $this->getBiz()->service('Course:CourseService');
    }

    protected function getThreadService()
    {
        return $this->getBiz()->service('Course:ThreadService');
    }

    protected function getClassroomService()
    {
        return $this->getBiz()->service('Classroom:ClassroomService');
    }
}
