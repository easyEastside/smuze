<?php

namespace App\Modules\Profile\Controllers;

use App\Modules\Profile\Actions\DeleteAccount;
use App\Modules\Profile\Requests\DeleteAccountRequest;
use Illuminate\Http\RedirectResponse;

class ProfileAccountController
{
    public function destroy(DeleteAccountRequest $request, DeleteAccount $deleteAccount): RedirectResponse
    {
        $deleteAccount->handle($request);

        return redirect('/');
    }
}
