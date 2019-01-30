<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 8/29/18
 * Time: 7:25 PM
 */

namespace Rri\Bundle\ProductBundle\Entity;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\ProductBundle\Entity\ProductImage;

/**
 * @ORM\Entity
 * @ORM\Table(name="rri_product_image_zone")
 *
 * @Config
 */
class ProductImageZone
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var ProductImage
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\ProductBundle\Entity\ProductImage")
     * @ORM\JoinColumn(name="productimage_imagezones_id", referencedColumnName="id" , nullable=true)
     */
    private $productImage;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     * @ConfigField
     */
    private $name;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     * @ConfigField
     */
    private $comment;

    /**
     * @var int
     * @ORM\Column(type="integer")
     * @ConfigField
     */
    private $rotation;

    /**
     * @var int
     * @ORM\Column(type="integer")
     * @ConfigField
     */
    private $cx;

    /**
     * @var int
     * @ORM\Column(type="integer")
     * @ConfigField
     */
    private $cy;

    /**
     * @var int
     * @ORM\Column(type="integer")
     * @ConfigField
     */
    private $height;

    /**
     * @var int
     * @ORM\Column(type="integer")
     * @ConfigField
     */
    private $width;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return ProductImage
     */
    public function getProductImage(): array
    {
        return $this->productImage;
    }

    /**
     * @param ProductImage $productImage
     */
    public function setProductImage(ProductImage $productImage): void
    {
        $this->productImage = $productImage;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getComment(): string
    {
        return $this->comment;
    }

    /**
     * @param string $comment
     */
    public function setComment(string $comment): void
    {
        $this->comment = $comment;
    }

    /**
     * @return int
     */
    public function getRotation(): int
    {
        return $this->rotation;
    }

    /**
     * @param int $rotation
     */
    public function setRotation(int $rotation): void
    {
        $this->rotation = $rotation;
    }

    /**
     * @return int
     */
    public function getCx(): int
    {
        return $this->cx;
    }

    /**
     * @param int $cx
     */
    public function setCx(int $cx): void
    {
        $this->cx = $cx;
    }

    /**
     * @return int
     */
    public function getCy(): int
    {
        return $this->cy;
    }

    /**
     * @param int $cy
     */
    public function setCy(int $cy): void
    {
        $this->cy = $cy;
    }

    /**
     * @return int
     */
    public function getHeight(): int
    {
        return $this->height;
    }

    /**
     * @param int $height
     */
    public function setHeight(int $height): void
    {
        $this->height = $height;
    }

    /**
     * @return int
     */
    public function getWidth(): int
    {
        return $this->width;
    }

    /**
     * @param int $width
     */
    public function setWidth(int $width): void
    {
        $this->width = $width;
    }


}
