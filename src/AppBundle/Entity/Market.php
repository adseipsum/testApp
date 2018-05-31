<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Market
 *
 * @ORM\Table(name="market")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\MarketRepository")
 */
class Market
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var float
     *
     * @ORM\Column(name="value", type="float")
     */
    private $value;

    /**
     * @var string
     *
     * @ORM\Column(name="market_index", type="string", length=50)
     */
    private $marketIndex;


    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Market
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set value
     *
     * @param float $value
     *
     * @return Market
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return float
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set marketIndex
     *
     * @param string $marketIndex
     *
     * @return Market
     */
    public function setMarketIndex($marketIndex)
    {
        $this->marketIndex = $marketIndex;

        return $this;
    }

    /**
     * Get marketIndex
     *
     * @return string
     */
    public function getMarketIndex()
    {
        return $this->marketIndex;
    }
}

