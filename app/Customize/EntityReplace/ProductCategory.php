<?php

namespace Customize\EntityReplace;

use Doctrine\ORM\Mapping as ORM;
use Customize\Annotation\EntityReplace;

/**
 * ProductCategory
 *
 * @ORM\Table(name="dtb_product_category")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="Eccube\Repository\ProductCategoryRepository")
 * @EntityReplace("Eccube\Entity\ProductCategory")
 */
class ProductCategory extends \Eccube\Entity\AbstractEntity
{
    /**
     * @var string
     *
     * @ORM\Column(name="product_id", type="string", length=60, options={"unsigned":true})
     * @ORM\Id        
     */
    private $product_id;

    /**
     * @var int
     *
     * @ORM\Column(name="category_id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $category_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="show_order", type="integer")
     */
    private $show_order = 1;

    /**
     * @var \Eccube\Entity\Product
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Product", inversedBy="ProductCategories")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="product_id", referencedColumnName="id")
     * })
     */
    private $Product;

    /**
     * @var \Eccube\Entity\Category
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Category", inversedBy="ProductCategories")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="category_id", referencedColumnName="id")
     * })
     */
    private $Category;

    /**
     * Set productId.
     *
     * @param int $productId
     *
     * @return ProductCategory
     */
    public function setProductId($productId)
    {
        $this->product_id = $productId;

        return $this;
    }

    /**
     * Get productId.
     *
     * @return int
     */
    public function getProductId()
    {
        return $this->product_id;
    }

    /**
     * Set categoryId.
     *
     * @param int $categoryId
     *
     * @return ProductCategory
     */
    public function setCategoryId($categoryId)
    {
        $this->category_id = $categoryId;

        return $this;
    }

    /**
     * Get categoryId.
     *
     * @return int
     */
    public function getCategoryId()
    {
        return $this->category_id;
    }

    /**
     * Set show_order.
     *
     * @param int $show_order
     *
     * @return ProductCategory
     */
    public function setShowOrder($show_order)
    {
        $this->show_order = $show_order;

        return $this;
    }

    /**
     * Get show_order.
     *
     * @return int
     */
    public function getShowOrder()
    {
        return $this->show_order;
    }

    /**
     * Set product.
     *
     * @param \Eccube\Entity\Product|null $product
     *
     * @return ProductCategory
     */
    public function setProduct(\Eccube\Entity\Product $product = null)
    {
        $this->Product = $product;

        return $this;
    }

    /**
     * Get product.
     *
     * @return \Eccube\Entity\Product|null
     */
    public function getProduct()
    {
        return $this->Product;
    }

    /**
     * Set category.
     *
     * @param \Eccube\Entity\Category|null $category
     *
     * @return ProductCategory
     */
    public function setCategory(\Eccube\Entity\Category $category = null)
    {
        $this->Category = $category;

        return $this;
    }

    /**
     * Get category.
     *
     * @return \Eccube\Entity\Category|null
     */
    public function getCategory()
    {
        return $this->Category;
    }
}

