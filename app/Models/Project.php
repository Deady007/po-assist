<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Project extends Model
{
    protected $fillable = [
        'name',
        'client_name',
    ];

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
}
