<?php

namespace App\Http\Controllers\Member;

use App\Models\Claim;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** SOW: My Claims — messages to the adjuster and outstanding document uploads. */
class ClaimController extends MemberController
{
    public function message(Request $request, Claim $claim): JsonResponse
    {
        $user = $this->ownClaim($request, $claim);
        $data = $request->validate(['body' => ['required', 'string', 'max:3000']]);

        $claim->messages()->create(['user_id' => $user->id, 'author_name' => $user->name, 'from_member' => true, 'body' => $data['body']]);
        $claim->touch();

        return response()->json(['ok' => true], 201);
    }

    public function upload(Request $request, Claim $claim): JsonResponse
    {
        $user = $this->ownClaim($request, $claim);
        $request->validate(['file' => ['required', 'file', 'max:'.config('cospark.upload_max_kb'), 'mimes:'.config('cospark.upload_mimes')]]);
        $file = $request->file('file');

        DB::transaction(function () use ($claim, $user, $file) {
            $claim->storeAttachments(['member_upload' => [$file]], 'claims');
            $claim->messages()->create(['user_id' => $user->id, 'author_name' => $user->name, 'from_member' => true, 'body' => 'Uploaded: '.$file->getClientOriginalName()]);
            // The adjuster reviews uploads; the request is considered answered for now.
            $claim->update(['outstanding_documents' => [], 'required_action' => null]);
        });

        return response()->json(['ok' => true], 201);
    }

    private function ownClaim(Request $request, Claim $claim): User
    {
        $user = $this->primary($request);
        abort_unless($claim->user_id === $user->id || ($claim->user_id === null && $claim->email === $user->email), 404);

        return $user;
    }
}
