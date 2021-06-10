<?php

/*
 * Annotation For Repository Extension
 */

namespace Customize\Annotation;

use Doctrine\Common\Annotations\Annotation\Target;
use Doctrine\ORM\Mapping\Annotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class RepositoryReplace implements Annotation
{
    /**
     * @var string
     */
    public $value;
}
