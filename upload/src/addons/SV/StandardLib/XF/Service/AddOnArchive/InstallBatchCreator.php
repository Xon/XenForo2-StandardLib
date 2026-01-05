<?php

namespace SV\StandardLib\XF\Service\AddOnArchive;


use SV\StandardLib\BypassAccessStatus;
use SV\StandardLib\Repository\AddOnRepository;
use SV\StandardLib\Repository\Helper as HelperRepo;
use SV\StandardLib\XF\AddOn\Manager as ExtendedAddOnManager;

/**
 * @extends \XF\Service\AddOnArchive\InstallBatchCreator
 */
class InstallBatchCreator extends XFCP_InstallBatchCreator
{
    protected $filenameMap = [];

    /** @noinspection PhpMissingReturnTypeInspection */
    public function addArchive($tempFile, $fileName = null)
    {
        $this->filenameMap[$tempFile] = $fileName ?? $tempFile;

        return parent::addArchive($tempFile, $fileName);
    }

    public function validateAllAddons(): void
    {
        $addOns = $this->getAddOnList();
        $addOns = AddOnRepository::get()->sortByDependencies($addOns);
        $this->saveSortedList($addOns);
        $this->verifyAddons($addOns);
    }

    protected function getAddOnList(): array
    {
        $addOns = [];

        /** @var array<string,string> $pendingAddOnFiles */
        $pendingAddOnFiles = (new BypassAccessStatus())->getPrivate($this->installBatch, 'pendingAddOnFiles')();

        // extract json. again
        foreach ($pendingAddOnFiles as $addOnId => $tempFile)
        {
            $json = $this->getJsonForAddon($addOnId, $tempFile);
            if ($json === null)
            {
                continue;
            }

            $json['tmpFile'] = $tempFile;

            $addOns[$addOnId] = $json;
        }

        return $addOns;
    }

    protected function getJsonForAddon(string $addOnId, string $tempFile): ?array
    {
        $zip = new \ZipArchive();
        $openResult = $zip->open($tempFile);
        if ($openResult !== true)
        {
            if (\XF::$developmentMode)
            {
                throw new \LogicException("File could not be opened as a zip ($openResult)");
            }

            return null;
        }

        $jsonFile = "upload/src/addons/{$addOnId}/addon.json";
        $json = @json_decode($zip->getFromName($jsonFile), true);
        if (!is_array($json))
        {
            return null;
        }

        return $json;
    }

    protected function saveSortedList(array $addOns)
    {
        $addonIds = $this->installBatch->addon_ids;
        $sortedAddonIds = [];
        // push sorted list into the install-batch
        foreach ($addOns as $addOnId => $json)
        {
            $sortedAddonIds[$addOnId] = $addonIds[$addOnId];
        }
        // capture any skipped files
        foreach ($addonIds as $addOnId => $blurb)
        {
            if (!array_key_exists($addOnId, $sortedAddonIds))
            {
                $sortedAddonIds[$addOnId] = $addonIds[$addOnId];
            }
        }
        if ($this->installBatch->exists())
        {
            $this->installBatch->fastUpdate('addon_ids', $sortedAddonIds);
        }
        else
        {
            $this->installBatch->addon_ids = $sortedAddonIds;
        }
    }

    protected function verifyAddons(array $addOns): void
    {
        $existingAddOnVersionString = HelperRepo::get()->getAddonVersions();
        $existingAddOnVersionIds = \XF::app()->container('addon.cache');
        foreach ($addOns as $addOnId => $json)
        {
            $existingAddOnVersionString[$addOnId] = $json['version_string'];
            $existingAddOnVersionIds[$addOnId] = $json['version_id'];
        }

        $repo = AddOnRepository::get();

        foreach ($addOns as $addOnId => $json)
        {
            $fileName = $this->filenameMap[$json['tmpFile']] ?? $addOnId;

            $title = $json['title'] ?? $addOnId;
            $requires = $json['require'] ?? null;
            if (is_array($requires) && count($requires) !== 0)
            {
                $errors = $warnings = [];
                $repo->checkAddOnRequirements($requires, $title, $errors, $warnings, true, false, $existingAddOnVersionString, $existingAddOnVersionIds);
                if (count($errors) !== 0)
                {
                    $error = \XF::phrase('following_requirements_for_x_were_not_met_y', ['errors' => implode(' ', $errors), 'title' => $title]);
                    $this->errors[] = \XF::phrase('could_not_process_x_y', ['fileName' => $fileName, 'errors' => $error]);
                }
            }

            $requires = $json['require-soft'] ?? null;
            if (is_array($requires) && count($requires) !== 0)
            {
                $errors = $warnings = [];
                $repo->checkAddOnRequirements($requires, $title, $errors, $warnings, true, true, $existingAddOnVersionString, $existingAddOnVersionIds);
                if (count($errors) !== 0)
                {
                    $error = \XF::phrase('following_requirements_for_x_were_not_met_y', ['errors' => implode(' ', $errors), 'title' => $title]);
                    $this->errors[] = \XF::phrase('could_not_process_x_y', ['fileName' => $fileName, 'errors' => $error]);
                }
            }
        }
    }
}

