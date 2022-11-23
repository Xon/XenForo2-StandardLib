<?php

namespace SV\StandardLib\ControllerPlugin;

use XF\ControllerPlugin\AbstractPlugin;

/**
 * Class Delete
 *
 * @package SV\StandardLib\ControllerPlugin
 */
class Delete extends AbstractPlugin
{
	/**
	 * @param \XF\Mvc\Entity\Entity $entity
	 * @param string $stateKey
	 * @param string $deleterService
	 * @param string $contentType
	 * @param string $deleteLink
	 * @param string $editLink
	 * @param string $redirectLink
	 * @param \XF\Entity\Phrase|string $title
	 * @param bool $canHardDelete
	 * @param bool $includeAuthorAlert
	 * @param string|null $templateName
	 * @param array $params
	 *
	 * @return \XF\Mvc\Reply\AbstractReply
     * @noinspection PhpMissingParamTypeInspection
     */
	public function actionDeleteWithState(
		\XF\Mvc\Entity\Entity $entity,
		string $stateKey,
		string $deleterService,
		string $contentType,
		string $deleteLink,
		string $editLink,
		string $redirectLink,
		$title,
		bool $canHardDelete = false,
		bool $includeAuthorAlert = true,
		string $templateName = null,
		array $params = []
	): \XF\Mvc\Reply\AbstractReply
    {
        if (!\is_callable([$entity, 'canDelete']) || !\is_callable([$entity, 'canUndelete']))
        {
            throw new \LogicException('Either canDelete or canUndelete is not callable on ' . \get_class($entity));
        }

        if (!$entity->canDelete('soft', $error))
        {
            return $this->noPermission($error);
        }

        if ($this->isPost())
		{
			$id = $entity->getIdentifierValues();
			if (!$id || count($id) != 1)
			{
				throw new \InvalidArgumentException("Entity does not have an ID or does not have a simple key");
			}
			$entityId = intval(reset($id));
			
			if ($entity->{$stateKey} == 'deleted')
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
						
						$deleter = $this->service($deleterService, $entity);
						if ($includeAuthorAlert && $this->filter('author_alert', 'bool'))
						{
							$deleter->setSendAlert(true, $this->filter('author_alert_reason', 'str'));
						}
						$deleter->delete('hard', $reason);
						
						/** @var \XF\ControllerPlugin\InlineMod $inlineModPlugin */
						$inlineModPlugin = $this->plugin('XF:InlineMod');
						$inlineModPlugin->clearIdFromCookie($contentType, $entityId);
						
						return $this->redirect($redirectLink);

					case 2:
                        if (!$entity->canUndelete())
                        {
                            return $this->noPermission();
                        }

						$deleter = $this->service($deleterService, $entity);
						if ($includeAuthorAlert && $this->filter('author_alert', 'bool'))
						{
							$deleter->setSendAlert(true, $this->filter('author_alert_reason', 'str'));
						}

                        /** @noinspection PhpUndefinedMethodInspection */
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
				
				$deleter = $this->service($deleterService, $entity);
				if ($includeAuthorAlert && $this->filter('author_alert', 'bool'))
				{
					$deleter->setSendAlert(true, $this->filter('author_alert_reason', 'str'));
				}
				$deleter->delete($type, $reason);
				
				/** @var \XF\ControllerPlugin\InlineMod $inlineModPlugin */
				$inlineModPlugin = $this->plugin('XF:InlineMod');
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
