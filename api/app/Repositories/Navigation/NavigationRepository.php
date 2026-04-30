<?php

namespace App\Repositories\Navigation;

use App\Models\Navigation;

class NavigationRepository implements NavigationRepositoryInterface
{
    protected $model;

    public function __construct(Navigation $model)
    {
        $this->model = $model;
    }

    public function all()
    {
        return $this->model->orderBy('order')->get();
    }

    public function find($id)
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update($id, array $data)
    {
        $navigation = $this->find($id);
        $navigation->update($data);
        return $navigation;
    }

    public function delete($id)
    {
        return $this->model->destroy($id);
    }

    public function getActiveNavigation()
    {
        return $this->model
            ->where('is_active', true)
            ->where('is_hidden', false)
            ->orderBy('order')
            ->get();
    }
} 