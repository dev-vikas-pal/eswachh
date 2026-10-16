<?php

namespace App\Http\Livewire;

use App\Models\User;
use App\Services\SectorService;
use Livewire\Component;
use Livewire\WithPagination;

class UsersIndex extends Component
{
    use WithPagination;

    public $searchTerm;

    /** Sector id chosen in the franchise dropdown. Empty means every sector this user may see. */
    public $franchiseId = '';

    protected $paginationTheme = 'bootstrap';

    public function updatingSearchTerm()
    {
        $this->resetPage();
    }

    public function updatingFranchiseId()
    {
        $this->resetPage();
    }

    public function render()
    {
        $searchTerm = '%'.($this->searchTerm ?? '').'%';
        $sectorIds = SectorService::selectedSectorIds($this->franchiseId !== '' ? $this->franchiseId : null);

        $users = User::query()
            ->where(function ($query) use ($searchTerm) {
                $query->where('name', 'like', $searchTerm)
                    ->orWhere('email', 'like', $searchTerm);
            });

        $users = $this->limitToFranchises($users, $sectorIds);

        $users = $users->orderBy('id', 'desc')
            ->with(['permissions', 'roles', 'providers'])
            ->paginate();

        $franchises = SectorService::sectorOptions();
        $franchiseAllLabel = SectorService::isFranchiseOwner() ? __('All My Franchises') : __('All Franchises');

        return view('livewire.users-index', compact('users', 'franchises', 'franchiseAllLabel'));
    }

    /**
     * Customers and cleaners carry their franchise on the profile. Franchise
     * owners carry it on the sector pivot, so both have to be included or a
     * franchise filter would hide the franchise user themselves.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  array<int, int>|null  $sectorIds
     */
    private function limitToFranchises($query, ?array $sectorIds)
    {
        if ($sectorIds === null) {
            return $query;
        }

        return $query->where(function ($outer) use ($sectorIds) {
            $outer->whereIn('users.id', function ($sub) use ($sectorIds) {
                $sub->select('user_id')
                    ->from('userprofiles')
                    ->whereIn('sector_id', $sectorIds);
            })->orWhereIn('users.id', function ($sub) use ($sectorIds) {
                $sub->select('user_id')
                    ->from('sector_user')
                    ->whereIn('sector_id', $sectorIds);
            });
        });
    }
}
