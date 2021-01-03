<?php

namespace SV\StandardLib\XF\Admin\Controller;

use XF\Diff;
use XF\Entity\TemplateModification;
use XF\Mvc\Entity\ArrayCollection;
use XF\Mvc\Entity\Finder;
use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\View as ViewReply;
use XF\Entity\Template as TemplateEntity;
use XF\Repository\TemplateModification as TemplateModificationRepo;
use XF\Mvc\Reply\Exception as ExceptionReply;
use XF\Template\Compiler\Exception as TemplateCompilerException;

class Template extends XFCP_Template
{
    public function actionEdit(ParameterBag $params)
    {
        $reply = parent::actionEdit($params);

        if ($reply instanceof ViewReply)
        {
            $template = $reply->getParam('template');
            if ($template instanceof TemplateEntity)
            {
                $reply->setParam(
                    'svModificationCount',
                    $this->getTemplateModificationFinderForSvStandardLib($template->type, $template->title)->total()
                );
            }
        }

        return $reply;
    }

    /**
     * @param ParameterBag $parameterBag
     *
     * @return ViewReply
     *
     * @throws ExceptionReply
     */
    public function actionViewModifications(ParameterBag $parameterBag) : ViewReply
    {
        /** @noinspection PhpUndefinedFieldInspection */
        $masterTemplate = $this->assertTemplateExists($parameterBag->template_id);
        $style = $this->assertStyleExists(
            $this->filter('style_id', '?uint'), // if this is not nullable then it will revert to master style which we do not want
            null,
            'svStandardLib_requested_style_not_found'
        );

        $templateRepo = $this->getTemplateRepo();

        /** @var TemplateEntity $template */
        $template = $templateRepo->findEffectiveTemplateInStyle(
            $style,
            $masterTemplate->title,
            $masterTemplate->type
        )->fetchOne();

        $activeModIds = $this->filter('active_mod_ids', '?array-uint');

        /** @var TemplateModification[]|ArrayCollection $modifications */
        $modifications = $this->getTemplateModificationFinderForSvStandardLib($template->type, $template->title)->fetch();
        $filtered = $modifications->filter(function (TemplateModification $mod) use ($activeModIds)
        {
            if ($activeModIds === null)
            {
                return $mod->enabled;
            }

            return \in_array($mod->modification_id, $activeModIds, true);
        });
        $filtered = $filtered->toArray();

        /** @var TemplateModificationRepo $templateModRepo */
        $templateModRepo = $this->repository('XF:TemplateModification');
        $templateStr = $templateModRepo->applyTemplateModifications(
            $template->template,
            $filtered,
            $statuses
        );

        $statuses = \array_map(function ($status)
        {
            if (\is_numeric($status))
            {
                return \XF::phrase('svStandardLib_match_count_x', [
                    'count' => $this->app()->language()->numberFormat($status)
                ]);
            }
            return $status;
        }, $statuses);

        $viewParams = [
            'style' => $style,
            'template' => $template,

            'mods' => $modifications->toArray(),
            'activeMods' => $filtered,

            'templateStr' => $templateStr,
            'activeModIds' => $activeModIds,
            'status' => $statuses,
            '_xfWithData' => $this->filter('_xfWithData', 'bool'),
            'tab' => $this->filter('tab', 'str', 'diffs')
        ];

        switch ($viewParams['tab'])
        {
            case 'diffs':
                $diff = new Diff();
                $diffs = $diff->findDifferences($template->template, $templateStr);

                $viewParams['diffs'] = $diffs;
                break;

            case 'compiled':
                $viewParams['compiledTemplate'] = null;
                $viewParams['compilerErrors'] = null;

                try
                {
                    $viewParams['compiledTemplate'] = $this->app()->templateCompiler()->compile($templateStr);
                }
                /** @noinspection PhpRedundantCatchClauseInspection */
                catch (TemplateCompilerException $exception)
                {
                    $viewParams['compilerErrors'] = $exception->getMessages();
                }
                break;

            default:
                throw $this->exception($this->notFound());
        }

        return $this->view(
            'SV\StandardLib\XF:Template\ViewModifications',
            'svStandardLib_template_view_modifications',
            $viewParams
        );
    }

    protected function getTemplateModificationFinderForSvStandardLib(string $type, string $template) : Finder
    {
        return $this->finder('XF:TemplateModification')
            ->where('type', $type)
            ->where('template', $template)
            ->whereAddOnActive()
            ->order(\XF::$versionId >= 2010872  ? ['execution_order', 'modification_key'] : ['execution_order']);
    }
}