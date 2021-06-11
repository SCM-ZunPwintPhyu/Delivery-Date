<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Customize\EntityReplace;

use Doctrine\ORM\Mapping as ORM;

/**
 * Csv
 *
 * @ORM\Table(name="mtb_csv_type")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="Eccube\Repository\Master\CsvTypeRepository")
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
 */
class Csv extends \Eccube\Entity\Master\AbstractMasterEntity
{
    /**
     * @var integer
     */
    const CSV_TYPE_PRODUCT = 1;

    /**
     * @var integer
     */
    const CSV_TYPE_CUSTOMER = 2;

    /**
     * @var integer
     */
    const CSV_TYPE_ORDER = 3;

    /**
     * @var integer
     */
    const CSV_TYPE_SHIPPING = 4;

    /**
     * @var integer
     */
    const CSV_TYPE_CATEGORY = 5;

     /**
     * @var integer
     */
    const CSV_TYPE_ORDER_CSV = 6;
}
