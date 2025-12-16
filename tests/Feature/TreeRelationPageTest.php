<?php

use Illuminate\Database\Eloquent\Relations\Relation;
use Mockery;
use Openplain\FilamentTreeView\Resources\Pages\TreeRelationPage;
use Openplain\FilamentTreeView\Tests\Models\Category;
use Openplain\FilamentTreeView\Tests\Resources\TestResource;

beforeEach(function () {
    // Create test data
    $this->parentCategory = Category::factory()->create(['name' => 'Parent Category']);
    $this->child1 = Category::factory()->withParent($this->parentCategory)->create(['name' => 'Child 1']);
    $this->child2 = Category::factory()->withParent($this->parentCategory)->create(['name' => 'Child 2']);
});

it('uses relationship query instead of resource query', function () {
    $page = new class($this->parentCategory) extends TreeRelationPage
    {
        protected static string $resource = TestResource::class;

        protected static string $relationship = 'children';

        protected static ?string $relatedResource = null;

        public function mount(int|string $record): void
        {
            $this->record = Category::find($record);
            parent::mount($record);
        }
    };

    $page->mount($this->parentCategory->id);

    $query = $page->getTreeQuery();
    expect($query)->toBeInstanceOf(Relation::class);

    // Verify it's scoped to the relationship
    $records = $query->get();
    expect($records)->toHaveCount(2);
    expect($records->pluck('id'))->toContain($this->child1->id, $this->child2->id);
    expect($records->pluck('id'))->not->toContain($this->parentCategory->id);
});

it('returns null for model labels when no related resource is set', function () {
    $page = new class($this->parentCategory) extends TreeRelationPage
    {
        protected static string $resource = TestResource::class;

        protected static string $relationship = 'children';

        protected static ?string $relatedResource = null;

        public function mount(int|string $record): void
        {
            $this->record = Category::find($record);
            parent::mount($record);
        }
    };

    $page->mount($this->parentCategory->id);

    expect($page->getModelLabel())->toBeNull();
    expect($page->getPluralModelLabel())->toBeNull();
});

it('returns schema when no related resource is set', function () {
    $page = new class($this->parentCategory) extends TreeRelationPage
    {
        protected static string $resource = TestResource::class;

        protected static string $relationship = 'children';

        protected static ?string $relatedResource = null;

        public function mount(int|string $record): void
        {
            $this->record = Category::find($record);
            parent::mount($record);
        }
    };

    $page->mount($this->parentCategory->id);

    $schema = Mockery::mock('Filament\Schemas\Schema');
    $formResult = $page->form($schema);
    $infolistResult = $page->infolist($schema);

    expect($formResult)->toBe($schema);
    expect($infolistResult)->toBe($schema);
});

it('extends ManageRelatedRecords', function () {
    $page = new class($this->parentCategory) extends TreeRelationPage
    {
        protected static string $resource = TestResource::class;

        protected static string $relationship = 'children';

        protected static ?string $relatedResource = null;
    };

    expect($page)->toBeInstanceOf(\Filament\Resources\Pages\ManageRelatedRecords::class);
    expect($page)->toBeInstanceOf(\Openplain\FilamentTreeView\Contracts\HasTree::class);
});

it('uses the same view as TreePage', function () {
    $page = new class($this->parentCategory) extends TreeRelationPage
    {
        protected static string $resource = TestResource::class;

        protected static string $relationship = 'children';

        protected static ?string $relatedResource = null;
    };

    $reflection = new ReflectionClass($page);
    $viewProperty = $reflection->getProperty('view');
    $viewProperty->setAccessible(true);
    $view = $viewProperty->getValue($page);

    expect($view)->toBe('filament-tree-view::pages.tree-page');
});
