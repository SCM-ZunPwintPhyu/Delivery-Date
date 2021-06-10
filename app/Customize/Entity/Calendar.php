<?php

namespace Customize\Entity;
use Doctrine\ORM\Mapping as ORM;


/**
 * Calendar
 *
 * @ORM\Table(name="mtb_calendar", uniqueConstraints={@ORM\UniqueConstraint(name="date", columns={"date"})})
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="Customize\Repository\CalendarRepository")
 */
class Calendar extends \Eccube\Entity\AbstractEntity
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="date", type="date")
     */
    private $date;

    /**
     * @var integer
     *
     * @ORM\Column(name="week", type="integer")
     */
    private $week;


    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set date.
     *
     * @param \DateTime|null $date
     *
     * @return Order
     */
    public function setDate($date = null)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date.
     *
     * @return \DateTime|null
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set week.
     *
     * @param int|null $week
     *
     * @return Calendar
     */
    public function setWeek($week)
    {
        $this->week = $week;

        return $this;
    }

    /**
     * Get week.
     *
     * @return int|null
     */
    public function getWeek()
    {
        return $this->week;
    }
   
}