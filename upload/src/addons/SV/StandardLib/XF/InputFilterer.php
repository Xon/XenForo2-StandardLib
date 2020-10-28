<?php

namespace SV\StandardLib\XF;

use XF\App as BaseApp;
use XF\Mvc\Entity\Finder;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Repository;
use XF\Service\AbstractService;
use XF\Mvc\Entity\Manager as EntityManager;
use XF\Job\Manager as JobManager;

class InputFilterer extends XFCP_InputFilterer
{
    /**
     * @param mixed $value
     * @param string $type
     * @param array $options
     *
     * @return mixed
     */
    protected function cleanInternal($value, $type, array $options)
    {
        switch ($type)
        {
            case 'sv-date-time':
                // @todo: actually parse
                throw new \LogicException('Not implemented.');

                break;
        }

        return parent::cleanInternal($value, $type, $options);
    }
}