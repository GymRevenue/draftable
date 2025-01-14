<?php

declare(strict_types=1);

namespace CapeAndBay\Draftable\Traits;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use CapeAndBay\Draftable\Draftable;
use Exception;

/**
 * @property Draftable $draft
 * @extends Draftable
 */
trait DraftableModel
{
    private static ?Model $owner = null;

    /**
     * get All Drafts Collection for model
     * @param bool $unfillable
     * @return Collection
     */
    public static function getAllDrafts(bool $unfillable = false, bool $hydrate = true): Collection
    {
        return static::getDraftsCollection(static::draftsQuery()->get(), $unfillable, $hydrate);
    }

    /**
     * get All published Drafts Collection for model
     * @param bool $unfillable
     * @return Collection
     */
    public static function getPublishedDraft(bool $unfillable = false, bool $hydrate = true): Collection
    {
        return static::getDraftsCollection(static::draftsQuery()->published()->get(), $unfillable, $hydrate);
    }

    /**
     * get All Unpublished Drafts Collection for model
     * @param bool $unfillable
     * @return Collection
     */
    public static function getUnpublishedDraft(bool $unfillable = false, bool $hydrate = true): Collection
    {
        return static::getDraftsCollection(static::draftsQuery()->unpublished()->get(), $unfillable, $hydrate);
    }

    /**
     * Get Drafts Collection
     *
     * @param Collection $entries
     * @param bool       $unfillable
     *
     * @return Collection
     */
    private static function getDraftsCollection(Collection $entries, bool $unfillable, bool $hydrate = true): Collection
    {
        return static::buildCollection($entries, $unfillable, $hydrate);
    }

    /**
     * Save model as draft
     *
     * @param Model|null  $owner
     * @param string|null $id
     *
     * @return $this
     * @throws Exception
     */
    public function saveAsDraft(?Model $owner = null, ?string $id = null): static
    {
        try {
            [$owner_model, $owner_id] = self::parseOwner($owner);
            $this->draft = Draftable::create([
                'id' => $id,
                'draftable_id' => $this->id,
                'draftable_data' => $this->toArray(),
                'draftable_model' => static::class,
                'published_at' => null,
                'owner_model' => $owner_model,
                'owner_id' => $owner_id,
                'data' => [],
            ]);
        } catch (Exception $e) {
            throw new  Exception($e->getMessage());
        }

        return $this;
    }

    /**
     * Save model with draft
     *
     * @param Model|null  $owner
     * @param string|null $id
     *
     * @return $this
     * @throws Exception
     */
    public function saveWithDraft(?Model $owner = null, ?string $id = null): static
    {
        if (is_a($this, 'Spatie\EventSourcing\Projections\Projection')) {
            $this->writable();
        }

        $this->save();

        try {
            [$owner_model, $owner_id] = self::parseOwner($owner);
            $this->draft = Draftable::create([
                'id' => $id,
                'draftable_id' => $this->id,
                'draftable_data' => $this->toArray(),
                'draftable_model' => static::class,
                'published_at' => Carbon::now(),
                'owner_model' => $owner_model,
                'owner_id' => $owner_id,
                'data' => [],
            ]);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        return $this;
    }

    /**
     * Build Collection for model
     *
     * @param Collection<Draftable> $entries
     * @param bool                  $unfillable
     *
     * @return Collection
     */
    private static function buildCollection(Collection $entries, bool $unfillable, bool $hydrate): Collection
    {
        if ($unfillable) {
            return $entries;
        }

        $collection = new Collection();
        /** @var Draftable $entery */
        foreach ($entries as $entery) {
            $new_model = $entery->model($hydrate);
            $new_model->draft = $entery;
            $collection->push($new_model);
        }

        return $collection;
    }

    /**
     * Drafts Main Query
     * @return Builder
     */
    private static function draftsQuery(): Builder
    {
        $condition = ['draftable_model' => static::class];
        [$condition['owner_model'], $condition['owner_id']] = self::parseOwner();

        return Draftable::where($condition);
    }

    /**
     * Publish unpublished draft
     * @return $this
     * @throws Exception
     */
    public function publish(): static
    {
        if ($this->published_at === null) {
            $this->draft->publish();
        }

        return $this;
    }

    /**
     * Drafts morph relation
     * @return mixed
     */
    public function drafts(): MorphMany
    {
        return $this->morphMany(Draftable::class, 'draftable', 'draftable_model', 'draftable_id');
    }


    /**
     * get draft by id
     * @param int $id
     * @return mixed
     */
    public function getDraft(int $id): Draftable
    {
        return static::draftsQuery()->where('id', $id)->first();
    }

    /**
     * Set user for draft ( the creator of draft )
     *
     * @param Model $owner
     *
     * @return DraftableModel
     */
    public static function setOwner(Model $owner): static
    {
        static::$owner = $owner;

        return new static();
    }

    /**
     * @return null[]|string[]
     */
    private static function parseOwner(?Model $model = null): array
    {
        $model ??= self::$owner;
        if ($model === null) {
            return [null, null];
        }

        return [$model::class, $model->{$model->getKeyName()}];
    }
}
