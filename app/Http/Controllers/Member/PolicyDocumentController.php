<?php

namespace App\Http\Controllers\Member;

use App\Models\Policy;
use App\Models\PolicyDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** SOW: My Policies — view / download policy documents (private storage). */
class PolicyDocumentController extends MemberController
{
    public function __invoke(Request $request, Policy $policy, PolicyDocument $document): StreamedResponse
    {
        $user = $request->user();
        $owner = $user->isSubAccount() ? $user->parent_id : $user->id;
        abort_unless($policy->user_id === $owner && $document->policy_id === $policy->id, 404);
        abort_unless($user->hasPermission('View policies'), 403);

        $disposition = $request->boolean('download') ? 'attachment' : 'inline';

        return Storage::response($document->path, $document->name.'.'.pathinfo($document->path, PATHINFO_EXTENSION), [], $disposition);
    }
}
