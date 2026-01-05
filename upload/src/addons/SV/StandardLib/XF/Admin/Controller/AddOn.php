<?php

namespace SV\StandardLib\XF\Admin\Controller;

use SV\StandardLib\XF\AddOn\Manager as ExtendedAddOnManager;
use XF\Http\Upload;
use XF\Service\AddOnArchive\InstallBatchCreator as InstallBatchCreatorService;
use SV\StandardLib\XF\Service\AddOnArchive\InstallBatchCreator as ExtendedInstallBatchCreatorService;

/**
 * @extends \XF\Admin\Controller\AddOn
 */
class AddOn extends XFCP_AddOn
{
    /**
     * @param Upload[] $uploads
     * @return InstallBatchCreatorService
     * @noinspection PhpMissingParentCallCommonInspection
     */
    protected function getBatchCreatorService(array $uploads)
    {
        /** @var ExtendedInstallBatchCreatorService $creator */
        $creator = $this->service(InstallBatchCreatorService::class, $this->getAddOnManager());

        /** @var ExtendedAddOnManager $addOnManager */
        $addOnManager = $this->app->addOnManager();
        $addOnManager->skipAddOnRequirements = true;
        try
        {
            foreach ($uploads AS $upload)
            {
                $creator->addUpload($upload);
            }
        }
        finally
        {
            $addOnManager->skipAddOnRequirements = false;
        }

        $creator->validateAllAddons();

        return $creator;
    }
}