<?php

declare(strict_types=1);

namespace App\Pixury\Templates;

class TemplateService
{
    public function __construct(
        protected TemplateRepository $repository
    ) {}

    public function getAllTemplates(): array
    {
        return $this->repository->getAll();
    }

    public function getTemplatesByCategory(string $category): array
    {
        return $this->repository->getByCategory($category);
    }

    public function getCategories(): array
    {
        return $this->repository->getCategories();
    }

    public function getTemplate(string $id): ?array
    {
        return $this->repository->findById($id);
    }

    public function getFeaturedTemplates(int $limit = 4): array
    {
        return array_slice($this->getAllTemplates(), 0, $limit);
    }
}