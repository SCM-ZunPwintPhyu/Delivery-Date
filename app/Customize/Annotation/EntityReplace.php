<?php

/*
 * Annotation For Entity Extension
 */

namespace Customize\Annotation;

use Doctrine\Common\Annotations\Annotation\Target;
use Doctrine\ORM\Mapping\Annotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class EntityReplace implements Annotation
{
    /**
     * @var string
     */
    public $value;
}
