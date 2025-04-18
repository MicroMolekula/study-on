<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class CourseDto
{
    private ?int $id = null;
    #[Assert\NotBlank(message: "Заполните это поле")]
    #[Assert\Length(
        max: 255,
        maxMessage: "Название не должно превышать 255 символов"
    )]
    private string $title;
    #[Assert\NotBlank(message: 'Заполните это поле')]
    #[Assert\Length(
        max: 255,
        maxMessage: "Символьный код не должен превышать 255 символов"
    )]
    private string $code;
    private string $type;
    private ?float $price;
    private string $description;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): CourseDto
    {
        $this->id = $id;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): CourseDto
    {
        $this->title = $title;
        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): CourseDto
    {
        $this->code = $code;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): CourseDto
    {
        $this->type = $type;
        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(?float $price): CourseDto
    {
        $this->price = $price;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): CourseDto
    {
        $this->description = $description;
        return $this;
    }

}