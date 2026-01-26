<?php

namespace SV\StandardLib\XF\Admin\Controller;

use SV\StandardLib\XF\AddOn\Manager as ExtendedAddOnManager;
use SV\StandardLib\XF\Service\AddOnArchive\InstallBatchCreator as ExtendedInstallBatchCreatorService;
use XF\Http\Upload;
use XF\Service\AddOnArchive\InstallBatchCreator as InstallBatchCreatorService;
use function count;
use function preg_match;
use function reset;

/**
 * @extends \XF\Admin\Controller\AddOn
 */
class AddOn extends XFCP_AddOn
{
    /**
     * @param Upload[] $uploads
     * @return InstallBatchCreatorService
     * @noinspection PhpMissingReturnTypeInspection
     */
    protected function getBatchCreatorService(array $uploads)
    {
        if (count($uploads) === 1)
        {
            $upload = reset($uploads);
            $fileName = $upload->getFileName();
            if (preg_match('/^SV-StandardLib.*.zip$/i', $fileName))
            {
                return parent::getBatchCreatorService($uploads);
            }
        }

        try
        {
            return $this->getSvBatchCreatorService($uploads);
        }
        catch (\Throwable $e)
        {
            \XF::logException($e);

            return parent::getBatchCreatorService($uploads);
        }
    }

    protected function getSvBatchCreatorService(array $uploads): InstallBatchCreatorService
    {
        /** @var ExtendedAddOnManager $addOnManager */
        $addOnManager = $this->app->addOnManager();
        $addOnManager->skipAddOnRequirements = true;
        try
        {
            /** @var ExtendedInstallBatchCreatorService $creator */
            $creator = $this->service(InstallBatchCreatorService::class, $this->getAddOnManager());

            foreach ($uploads as $upload)
            {
                $creator->addUpload($upload);
            }

            $creator->validateAllAddons();

            return $creator;
        }
        finally
        {
            $addOnManager->skipAddOnRequirements = false;
        }
    }
}