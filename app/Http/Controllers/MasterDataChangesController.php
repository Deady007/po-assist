<?php

namespace App\Http\Controllers;

use App\Http\Requests\MasterDataChangeStoreRequest;
use App\Http\Requests\MasterDataChangeUpdateRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class MasterDataChangesController extends ApiController
{
    public function index(int $projectId): JsonResponse
    {
        $items = DB::table('master_data_changes')
            ->where('project_id', $projectId)
            ->orderBy('id')
            ->get();

        return $this->success(['items' => $items]);
    }

    public function store(MasterDataChangeStoreRequest $request, int $projectId): JsonResponse
    {
        $data = $request->validated();
        $data['project_id'] = $projectId;

        if (!empty($data['requirement_id'])) {
            $reqProjectId = \App\Models\Requirement::where('id', $data['requirement_id'])->value('project_id');
            if ($reqProjectId && (int) $reqProjectId !== (int) $projectId) {
                return $this->failure([['code' => 'PROJECT_MISMATCH', 'message' => 'Requirement not in project']], 422);
            }
        }

        $id = DB::table('master_data_changes')->insertGetId($data);
        $record = DB::table('master_data_changes')->find($id);

        return $this->success(['item' => $record]);
    }

    public function update(MasterDataChangeUpdateRequest $request, int $projectId, int $id): JsonResponse
    {
        $exists = DB::table('master_data_changes')->where('project_id', $projectId)->where('id', $id)->exists();
        if (!$exists) {
            return $this->failure([['code' => 'NOT_FOUND', 'message' => 'Record not found']], 404);
        }

        DB::table('master_data_changes')->where('id', $id)->update($request->validated());
        $record = DB::table('master_data_changes')->find($id);

        return $this->success(['item' => $record]);
    }

    public function destroy(int $projectId, int $id): JsonResponse
    {
        $exists = DB::table('master_data_changes')->where('project_id', $projectId)->where('id', $id)->exists();
        if (!$exists) {
            return $this->failure([['code' => 'NOT_FOUND', 'message' => 'Record not found']], 404);
        }

        DB::table('master_data_changes')->where('id', $id)->delete();
        return $this->success(['deleted' => true]);
    }
}
