<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use N3XT0R\LaravelWebdavServer\DTO\Auth\PathResourceDto;
use N3XT0R\LaravelWebdavServer\Policies\PathPolicy;

final class WebDavPathPolicy
{
    public function __construct(private PathPolicy $inner)
    {
    }

    public function read(Authenticatable $user, PathResourceDto $resource): bool
    {
        return $this->inner->read($user, $resource);
    }

    public function write(Authenticatable $user, PathResourceDto $resource): bool
    {
        return $this->inner->write($user, $resource) && $this->isZip($resource);
    }

    public function delete(Authenticatable $user, PathResourceDto $resource): bool
    {
        return $this->inner->delete($user, $resource);
    }

    public function createDirectory(Authenticatable $user, PathResourceDto $resource): bool
    {
        return $this->inner->createDirectory($user, $resource);
    }

    public function createFile(Authenticatable $user, PathResourceDto $resource): bool
    {
        return $this->inner->createFile($user, $resource) && $this->isZip($resource);
    }

    private function isZip(PathResourceDto $resource): bool
    {
        return str_ends_with(strtolower(trim($resource->path)), '.zip');
    }
}