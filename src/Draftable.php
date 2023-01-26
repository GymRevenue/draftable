<?php

declare(strict_types=1);

namespace CapeAndBay\Draftable;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Collection;

/**
 * @property string              $id
 * @property Carbon              $published_at
 * @property array               $draftable_data
 * @property array               $data
 * @property class-string<Model> $draftable_model
 * @property int                 $draftable_id
 * @property string              $owner_model
 */
class Draftable extends Model
{
    use HasUuids;

    protected $table = 'draftables';

    protected $dates = [
        'created_at',
        'updated_at',
        'published_at',
    ];

    protected $casts = [
        'draftable_data' => 'array',
        'data' => 'array',
    ];

    protected $fillable = [
        'id',
        'draftable_id',
        'draftable_data',
        'draftable_model',
        'published_at',
        'owner_model',
        'owner_id',
        'data',
    ];

    /**
     * Unpublished Drafts Scope
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeUnPublished(Builder $query): Builder
    {
        return $query->where('published_at', null);
    }

    /**
     * Published Drafts Scope
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('published_at', '!=', null);
    }

    /**
     * Publish Method to publish the draft
     *
     * @return static
     * @throws Exception
     */
    public function publish(): static
    {
        try {
            $new_class = $this->draftable_model::create($this->draftable_data);
            $this->published_at = Carbon::now();
            $this->draftable_id = $new_class->id;
            $this->save();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        return $this;
    }

    /**
     * Restore Method for old draft
     *
     * @return static
     * @throws Exception
     */
    public function restore(): static
    {
        try {
            $new_class = $this->draftable_model::where('id', $this->draftable_id)->first();
            if (empty($new_class)) {
                throw new Exception('Cant Find Resource for ' . $this->draftable_model . ' with id ' . $this->draftable_id);
            }

            $new_class->update($this->draftable_data);
            $this->published_at = Carbon::now();
            $this->draftable_id = $new_class->id;
            $this->save();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        return $this;
    }

    /**
     * Build the model for current draft
     *
     * @return static
     * @throws Exception
     */
    public function model(): Model
    {
        try {
            $new_class = new $this->draftable_model();
            $new_class->forceFill($this->draftable_data);
            $new_class->published_at = $this->published_at;

            return $new_class;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Default draft owner.
     *
     * @return BelongsTo
     */
    public function draftOwner(): BelongsTo
    {
        return $this->belongsTo($this->owner_model, 'owner_id');
    }

    /**
     * Set Additional data for the draft.
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setData(string $key, mixed $value): static
    {
        $data = $this->data;
        $data[$key] = $value;
        $this->data = $data;
        $this->save();

        return $this;
    }

    /**
     * get data of draft
     */
    public function getData(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }
}
