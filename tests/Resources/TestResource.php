<?php

namespace Openplain\FilamentTreeView\Tests\Resources;

use Filament\Resources\Resource;
use Openplain\FilamentTreeView\Tests\Models\Category;

class TestResource extends Resource
{
    protected static ?string $model = Category::class;

    public static function getModel(): string
    {
        return Category::class;
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return Category::query();
    }
}

