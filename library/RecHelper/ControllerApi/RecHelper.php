<?php

class RecHelper_ControllerApi_RecHelper extends bdApi_ControllerApi_Abstract
{
    public function actionGetIndex()
    {
        $addOns = XenForo_Application::get('addOns');

        $d7 = XenForo_Application::$time - 7 * 86400;
        $d30 = XenForo_Application::$time - 30 * 86400;

        $data = [
            'data_links' => [
                bdApi_Data_Helper_Core::safeBuildApiLink('rec-helper/user-followings'),
                bdApi_Data_Helper_Core::safeBuildApiLink('rec-helper/user-forums'),
                bdApi_Data_Helper_Core::safeBuildApiLink('rec-helper/thread-likes', null, ['like_date' => $d30]),
                bdApi_Data_Helper_Core::safeBuildApiLink('rec-helper/thread-posts', null, ['post_date' => $d30]),
                bdApi_Data_Helper_Core::safeBuildApiLink('rec-helper/thread-reads', null, ['thread_read_date' => $d7]),
            ],
            'version_id' => $addOns['RecHelper'],
        ];

        return $this->responseData('', $data);
    }

    public function actionGetUserFollowings()
    {
        return $this->_actionData('SELECT user_id, follow_user_id FROM xf_user_follow');
    }

    public function actionGetUserForums()
    {
        return $this->_actionData('SELECT user_id, node_id FROM xf_forum_watch');
    }

    public function actionGetThreadLikes()
    {
        /** @var int $likeDate */
        $likeDate = $this->_input->filterSingle('like_date', XenForo_Input::UINT);
        if (empty($likeDate)) {
            return $this->responseNoPermission();
        }

        return $this->_actionData("
            SELECT lc.like_user_id, p.thread_id
            FROM xf_liked_content AS lc
            INNER JOIN xf_post AS p
                ON p.post_id = lc.content_id
            WHERE lc.content_type = 'post'
                AND lc.like_date > {$likeDate}
        ");
    }

    public function actionGetThreadPosts()
    {
        /** @var int $postDate */
        $postDate = $this->_input->filterSingle('post_date', XenForo_Input::UINT);
        if (empty($postDate)) {
            return $this->responseNoPermission();
        }

        return $this->_actionData("SELECT user_id, thread_id FROM xf_post WHERE post_date > {$postDate}");
    }

    public function actionGetThreadReads()
    {
        /** @var int $threadReadDate */
        $threadReadDate = $this->_input->filterSingle('thread_read_date', XenForo_Input::UINT);
        if (empty($threadReadDate)) {
            return $this->responseNoPermission();
        }

        // TODO: verify php memory limit allow this
        return $this->_actionData("
            SELECT user_id, thread_id
            FROM xf_thread_read
            WHERE thread_read_date > {$threadReadDate}
        ");
    }

    protected function _actionData($sql)
    {
        $db = XenForo_Application::getDb();

        header('Content-Type: application/json');

        echo '[';

        $stmt = $db->query($sql);
        $first = true;
        while ($row = $stmt->fetch(Zend_Db::FETCH_ASSOC)) {
            if ($first) {
                $first = false;
                echo json_encode([
                    'action' => $this->_routeMatch->getAction(),
                    'headers' => array_keys($row),
                    'sql' => trim(preg_replace('/\s+/', ' ', $sql)),
                    'total' => $stmt->rowCount(),
                ]);
            }

            echo ',', json_encode(array_values($row));
        }

        echo ']';

        die(0);
    }

    protected function _preDispatch($action)
    {
        parent::_preDispatch($action);

        $session = bdApi_Data_Helper_Core::safeGetSession();
        $clientId = $session->getOAuthClientId();
        if (empty($clientId)) {
            throw $this->responseException($this->responseNoPermission());
        }
    }
}
