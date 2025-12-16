<?php

namespace Openplain\FilamentTreeView\Resources\Pages;

use Closure;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Openplain\FilamentTreeView\Concerns\InteractsWithTree;
use Openplain\FilamentTreeView\Contracts\HasTree;
use Openplain\FilamentTreeView\Tree;

class TreeRelationPage extends ManageRelatedRecords implements HasTree
{
    use InteractsWithTree {
        makeTree as makeBaseTree;
    }

    protected string $view = 'filament-tree-view::pages.tree-page';

    public function getBreadcrumb(): string
    {
        return static::$breadcrumb ?? static::getRelationshipTitle();
    }

    public function tree(Tree $tree): Tree
    {
        return $tree;
    }

    public function getTitle(): string|Htmlable
    {
        if (filled(static::$title)) {
            return static::$title;
        }

        $relatedResource = static::getRelatedResource();

        if ($relatedResource) {
            return $relatedResource::getTitleCasePluralModelLabel();
        }

        return parent::getTitle();
    }

    public function form(Schema $schema): Schema
    {
        $relatedResource = static::getRelatedResource();

        if (! $relatedResource) {
            return $schema;
        }

        return $relatedResource::form($schema);
    }

    public function infolist(Schema $schema): Schema
    {
        $relatedResource = static::getRelatedResource();

        if (! $relatedResource) {
            return $schema;
        }

        return $relatedResource::infolist($schema);
    }

    public function getDefaultActionSchemaResolver(Action $action): ?Closure
    {
        return match (true) {
            $action instanceof CreateAction, $action instanceof EditAction => fn (Schema $schema): Schema => $this->form($schema->columns(2)),
            $action instanceof ViewAction => fn (Schema $schema): Schema => $this->infolist($this->form($schema->columns(2))),
            default => null,
        };
    }

    public function getDefaultActionUrl(Action $action): ?string
    {
        $relatedResource = static::getRelatedResource();

        if (! $relatedResource) {
            return null;
        }

        if (
            ($action instanceof CreateAction) &&
            ($relatedResource::hasPage('create'))
        ) {
            return $relatedResource::getUrl('create', shouldGuessMissingParameters: true);
        }

        if (
            ($action instanceof EditAction) &&
            ($relatedResource::hasPage('edit'))
        ) {
            return $relatedResource::getUrl('edit', ['record' => $action->getRecord()], shouldGuessMissingParameters: true);
        }

        if (
            ($action instanceof ViewAction) &&
            ($relatedResource::hasPage('view'))
        ) {
            return $relatedResource::getUrl('view', ['record' => $action->getRecord()], shouldGuessMissingParameters: true);
        }

        return null;
    }

    protected function makeTree(): Tree
    {
        $relatedResource = static::getRelatedResource();

        $tree = $this->makeBaseTree()
            ->query(fn (): Builder|Relation => $this->getTreeQuery())
            ->when($this->getModelLabel(), fn (Tree $tree, string $modelLabel): Tree => $tree->modelLabel($modelLabel))
            ->when($this->getPluralModelLabel(), fn (Tree $tree, string $pluralModelLabel): Tree => $tree->pluralModelLabel($pluralModelLabel))
            ->recordAction(function (Model $record, Tree $tree): ?string {
                foreach (['view', 'edit'] as $action) {
                    $action = $tree->getAction($action);

                    if (! $action) {
                        continue;
                    }

                    $action->record($record);
                    $action->getGroup()?->record($record);

                    if ($action->isHidden()) {
                        continue;
                    }

                    if ($action->getUrl()) {
                        continue;
                    }

                    return $action->getName();
                }

                return null;
            })
            ->recordUrl(function (Model $record, Tree $tree): ?string {
                foreach (['view', 'edit'] as $action) {
                    $action = $tree->getAction($action);

                    if (! $action) {
                        continue;
                    }

                    $action->record($record);
                    $action->getGroup()?->record($record);

                    if ($action->isHidden()) {
                        continue;
                    }

                    $url = $action->getUrl();

                    if (! $url) {
                        continue;
                    }

                    return $url;
                }

                $relatedResource = static::getRelatedResource();

                if (! $relatedResource) {
                    return null;
                }

                foreach (['view', 'edit'] as $action) {
                    if (! $relatedResource::hasPage($action)) {
                        continue;
                    }

                    if (! $relatedResource::{'can'.ucfirst($action)}($record)) {
                        continue;
                    }

                    return $relatedResource::getUrl($action, ['record' => $record], shouldGuessMissingParameters: true);
                }

                return null;
            });

        if (! $relatedResource) {
            return $tree;
        }

        return $relatedResource::tree($tree);
    }

    public function getTreeQuery(): Builder|Relation
    {
        return $this->getRelationship();
    }

    public function getModelLabel(): ?string
    {
        $relatedResource = static::getRelatedResource();

        if (! $relatedResource) {
            return null;
        }

        return $relatedResource::getModelLabel();
    }

    public function getPluralModelLabel(): ?string
    {
        $relatedResource = static::getRelatedResource();

        if (! $relatedResource) {
            return null;
        }

        return $relatedResource::getPluralModelLabel();
    }
}
