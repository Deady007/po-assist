<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Project extends Model
{
    use SoftDeletes, Blameable;

    protected $fillable = [
        'project_code',
        'client_id',
        'name',
        'description',
        'status_id',
        'start_date',
        'due_date',
        'priority',
        'owner_user_id',
        'is_active',
        'created_by',
        'updated_by',
        'client_name',
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(ProjectStatus::class, 'status_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function phases(): HasMany
    {
        return $this->hasMany(ProjectPhase::class);
    }

    public function currentPhase()
    {
        $incomplete = $this->phases()
            ->where('status', '!=', 'COMPLETED')
            ->orderBy('sequence_no')
            ->first();

        return $incomplete ?? $this->phases()->orderByDesc('sequence_no')->first();
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(Requirement::class);
    }

    public function dataItems(): HasMany
    {
        return $this->hasMany(DataItem::class);
    }

    public function masterDataChanges(): HasMany
    {
        return $this->hasMany(MasterDataChange::class);
    }

    public function bugs(): HasMany
    {
        return $this->hasMany(Bug::class);
    }

    public function testRuns(): HasMany
    {
        return $this->hasMany(TestRun::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }

    public function tokenWallet(): HasOne
    {
        return $this->hasOne(TokenWallet::class);
    }

    public function tokenRequests(): HasMany
    {
        return $this->hasMany(TokenRequest::class);
    }

    public function driveFolders(): HasMany
    {
        return $this->hasMany(DriveFolder::class);
    }

    public function driveFiles(): HasMany
    {
        return $this->hasMany(DriveFile::class);
    }

    public function validationReports(): HasMany
    {
        return $this->hasMany(ValidationReport::class);
    }

    public function rfpDocuments(): HasMany
    {
        return $this->hasMany(RfpDocument::class);
    }

    public function emailArtifacts(): HasMany
    {
        return $this->hasMany(EmailArtifact::class);
    }

    public function team(): HasMany
    {
        return $this->hasMany(ProjectTeam::class);
    }

    public function modules(): HasMany
    {
        return $this->hasMany(ProjectModule::class);
    }
}
