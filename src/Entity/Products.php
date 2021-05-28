<?php

namespace App\Entity;

use App\Repository\ProductsRepository;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Hateoas\Configuration\Annotation as Hateoas;

/**
 * @ORM\Entity(repositoryClass=ProductsRepository::class)
 *
 * @Serializer\ExclusionPolicy("all")
 *
 * @Hateoas\Relation(
 *      "self",
 *      href = @Hateoas\Route(
 *          "products_one",
 *          parameters = { "id" = "expr(object.getId())" },
 *          absolute = true
 *      )
 * )
 * @Hateoas\Relation(
 *      "all",
 *      href = @Hateoas\Route(
 *          "products_all",
 *          absolute = true
 *      )
 * )
 */
class Products
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     *
     * @Serializer\Expose()
     */
    private int $id;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @Serializer\Expose()
     */
    private string $brand;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @Serializer\Expose()
     */
    private string $model;

    /**
     * @ORM\Column(type="integer")
     *
     * @Serializer\Expose()
     */
    private int $release_year;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @Serializer\Expose()
     */
    private string $operating_system;

    /**
     * @ORM\Column(type="text")
     *
     * @Serializer\Expose()
     */
    private string $description;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created_at;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updated_at;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function setBrand(string $brand): self
    {
        $this->brand = $brand;

        return $this;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(string $model): self
    {
        $this->model = $model;

        return $this;
    }

    public function getReleaseYear(): ?int
    {
        return $this->release_year;
    }

    public function setReleaseYear(int $release_year): self
    {
        $this->release_year = $release_year;

        return $this;
    }

    public function getOperatingSystem(): ?string
    {
        return $this->operating_system;
    }

    public function setOperatingSystem(string $operating_system): self
    {
        $this->operating_system = $operating_system;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeInterface $created_at): self
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?\DateTimeInterface $updated_at): self
    {
        $this->updated_at = $updated_at;

        return $this;
    }
}
