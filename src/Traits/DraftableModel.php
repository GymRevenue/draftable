<?php

declare(strict_types=1);

namespace CapeAndBay\Draftable\Traits;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Collection;
use CapeAndBay\Draftable\Draftable;
use Exception;

/**
 * @property Draftable $draft
 * @extends Draftable
 */
trait DraftableModel
{
    private static ?string $owner_id = null;

    /**
     * get All Drafts Collection for model
     * @param bool $unfillable
     * @return Collection
     */
    public static function getAllDrafts(bool $unfillable = false): Collection
    {
        return static::getDraftsCollection(static::draftsQuery()->get(), $unfillable);
    }

    /**
     * get All published Drafts Collection for model
     * @param bool $unfillable
     * @return Collection
     */
    public static function getPublishedDraft(bool $unfillable = false): Collection
    {
        return static::getDraftsCollection(static::draftsQuery()->published()->get(), $unfillable);
    }

    /**
     * get All Unpublished Drafts Collection for model
     * @param bool $unfillable
     * @return Collection
     */
    public static function getUnpublishedDraft(bool $unfillable = false): Collection
    {
        return static::getDraftsCollection(static::draftsQuery()->unpublished()->get(), $unfillable);
    }

    /**
     * Get Drafts Collection
     * @param Collection $draft_entries
     * @param bool $unfillable
     * @return Collection
     */
    private static function getDraftsCollection(Collection $draft_entries, bool $unfillable): Collection
    {
        return static::buildCollection($draft_entries, $unfillable);
    }

    /**
     * Save model as draft
     * @param Model|null $owner
     * @return $this
     * @throws Exception
     */
    public function saveAsDraft(?Model $owner = null): static
    {
        try {
            $this->draft = Draftable::create([
                'draftable_id' => $this->id,
                'draftable_data' => $this->toArray(),
                'draftable_model' => static::class,
                'published_at' => null,
                'owner_id' => static::getIdentifierFromModel($owner, static::$owner_id),
                'data' => []
            ]);
        } catch (Exception $e) {
            throw new  Exception($e->getMessage());
        }

        return $this;
    }

    /**
     * Save model with draft
     * @param Model|null $owner
     * @return $this
     * @throws Exception
     */
    public function saveWithDraft(?Model $owner = null): static
    {
        $this->save();
        $drafted_array = $this->toArray();
        unset($drafted_array['id']);

        try {
            $this->draft = Draftable::create([
                'draftable_id' => $this->id,
                'draftable_data' => $drafted_array,
                'draftable_model' => static::class,
                'published_at' => Carbon::now(),
                'owner_id' => static::getIdentifierFromModel($owner, static::$owner_id),
                'data' => []
            ]);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        return $this;
    }

    /**
     * Build Collection for model
     * @param Collection<Draftable> $draftable_entries
     * @param bool $unfillable
     * @return Collection
     */
    private static function buildCollection(Collection $draftable_entries, bool $unfillable = false): Collection
    {
        if ($unfillable) {
            return $draftable_entries;
        }

        $collection = new Collection();
        foreach ($draftable_entries as $entery) {
            $new_class = new static();
            $new_class->forceFill($entery->draftable_data);
            $new_class->published_at = $entery->published_at;
            $new_class->draft = $entery;
            $collection->push($new_class);
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
        if (static::$owner_id !== null) {
            $condition['owner_id'] = static::$owner_id;
        }

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
     * @param Model|string $owner
     *
     * @return DraftableModel
     */
    public static function setOwner(Model|string $owner): static
    {
        static::$owner_id = is_string($owner) ? $owner : self::getIdentifierFromModel($owner);

        return new static();
    }

    private static function getIdentifierFromModel(?Model $model = null, ?string $default = null): ?string
    {
        if ($model === null) {
            return $default;
        }

        return $model->{$model->getKeyName()};
    }
}
