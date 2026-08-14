<?php

namespace App\Models\Presenters;

/**
 * Presenter Class for Book Module.
 */
trait UserPresenter
{
    /**
     * Get Status Label.
     *
     * @return [type] [description]
     */
    public function getStatusLabelAttribute()
    {
        switch ($this->status) {
            case '1':
                return '<span class="badge bg-success">Active</span>';
                break;
            case '2':
                return '<span class="badge bg-warning text-dark">Blocked</span>';
                break;

            default:
                return '<span class="badge bg-primary">Status:'.$this->status.'</span>';
                break;
        }
    }

    /**
     * Get Status Label.
     *
     * @return [type] [description]
     */
    public function getConfirmedLabelAttribute()
    {
        if ($this->email_verified_at != null) {
            return '<span class="badge bg-success">Confirmed</span>';
        } else {
            return '<span class="badge bg-danger">Not Confirmed</span>';
        }
    }

    /*
     * There were getRolesAttribute() and getPermissionsAttribute() accessors
     * here. They shadowed Spatie's roles and permissions relations and held
     * every assignment in the cache with rememberForever, filtering it per
     * user.
     *
     * Because everything that asks "can this user..." reads through those
     * relations - hasRole(), can(), @can, the can: middleware - a newly
     * created user appeared to have no roles at all, and any change to
     * anybody's roles stayed invisible, until someone cleared the cache by
     * hand. That is what produced 403s for freshly created accounts.
     *
     * They are gone: the real relations are correct and eager loadable. Screens
     * that list many users load roles with ->with('roles') instead, which is
     * one query for the page rather than one per row.
     */
}
