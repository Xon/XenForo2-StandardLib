<?php

namespace SV\StandardLib\ControllerPlugin;

use LogicException;
use SV\StandardLib\Helper;
use XF\ControllerPlugin\AbstractPlugin;
use XF\ControllerPlugin\InlineMod as InlineModPlugin;
use XF\Entity\Phrase;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Reply\AbstractReply;
use function assert;
use function count;
use function intval;
use function is_callable;
use function method_exists;
use function reset;

/**
 * Class Delete
 *
 * @package SV\StandardLib\ControllerPlugin
 */
class Delete extends AbstractPlugin
{
    /**
     * @param Entity $entity
     * @param string $stateKey
     * @param string $deleterService
     * @param string $contentType
     * @param string $deleteLink
     * @param string $editLink
     * @param string $redirectLink
     * @param Phrase|string $title
     * @param bool $canHardDelete
     * @param bool $includeAuthorAlert
     * @param string|null $templateName
     * @param array $params
     *
     * @return AbstractReply
     */
    public function actionDeleteWithState(
        Entity $entity,
        string $stateKey,
        string $deleterService,
        string $contentType,
        string $deleteLink,
        string $editLink,
        string $redirectLink,
               $title,
        bool $canHardDelete = false,
        bool $includeAuthorAlert = true,
        ?string $templateName = null,
        array $params = []
    ): AbstractReply
    {
        if (!is_callable([$entity, 'canDelete']) || !is_callable([$entity, 'canUndelete']))
        {
            throw new LogicException('Either canDelete or canUndelete is not callable on ' . \get_class($entity));
        }

        if (!$entity->canDelete('soft', $error))
        {
            return $this->noPermission($error);
        }

        if ($this->isPost())
        {
            $id = $entity->getIdentifierValues();
            if (!$id || count($id) !== 1)
            {
                throw new \InvalidArgumentException('Entity does not have an ID or does not have a simple key');
            }
            $entityId = intval(reset($id));

            if ($entity->{$stateKey} === 'deleted')
            {
                $linkHash = $this->buildLinkHash($entityId);

                $type = $this->filter('hard_delete', 'uint');
                switch ($type)
                {
                    case 0:
                        return $this->redirect($redirectLink . $linkHash);

                    case 1:
                        if (!$entity->canDelete('hard', $error))
                        {
                            return $this->noPermission($error);
                        }

                        $reason = $this->filter('reason', 'str');

                        $deleter = Helper::service($deleterService, $entity);
                        if ($includeAuthorAlert && $this->filter('author_alert', 'bool'))
                        {
                            assert(method_exists($deleter, 'setSendAlert'));
                            $deleter->setSendAlert(true, $this->filter('author_alert_reason', 'str'));
                        }
                        assert(method_exists($deleter, 'delete'));
                        $deleter->delete('hard', $reason);

                        $inlineModPlugin = Helper::plugin($this, InlineModPlugin::class);
                        $inlineModPlugin->clearIdFromCookie($contentType, $entityId);

                        return $this->redirect($redirectLink);

                    case 2:
                        if (!$entity->canUndelete())
                        {
                            return $this->noPermission();
                        }

                        $deleter = Helper::service($deleterService, $entity);
                        if ($includeAuthorAlert && $this->filter('author_alert', 'bool'))
                        {
                            assert(method_exists($deleter, 'setSendAlert'));
                            $deleter->setSendAlert(true, $this->filter('author_alert_reason', 'str'));
                        }

                        assert(method_exists($deleter, 'unDelete'));
                        $deleter->unDelete();

                        return $this->redirect($redirectLink . $linkHash);
                }
            }
            else
            {
                $type = $this->filter('hard_delete', 'bool') ? 'hard' : 'soft';
                $reason = $this->filter('reason', 'str');

                if (!$entity->canDelete($type, $error))
                {
                    return $this->noPermission($error);
                }

                $deleter = Helper::service($deleterService, $entity);
                if ($includeAuthorAlert && $this->filter('author_alert', 'bool'))
                {
                    assert(method_exists($deleter, 'setSendAlert'));
                    $deleter->setSendAlert(true, $this->filter('author_alert_reason', 'str'));
                }
                assert(method_exists($deleter, 'delete'));
                $deleter->delete($type, $reason);

                $inlineModPlugin = Helper::plugin($this, InlineModPlugin::class);
                $inlineModPlugin->clearIdFromCookie($contentType, $entityId);

                return $this->redirect($redirectLink);
            }
        }

        $templateName = $templateName ?: 'public:svStandardLib_delete_state';

        $viewParams = [
                          'entity'             => $entity,
                          'stateKey'           => $stateKey,
                          'title'              => $title,
                          'editLink'           => $editLink,
                          'deleteLink'         => $deleteLink,
                          'canHardDelete'      => $canHardDelete,
                          'includeAuthorAlert' => $includeAuthorAlert
                      ] + $params;
        return $this->view('SV\StandardLib:Delete\State', $templateName, $viewParams);
    }
}
